<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/rekapager package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Rekapager\Doctrine\ORM;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Rekalogika\Contracts\Rekapager\Exception\LogicException;
use Rekalogika\Rekapager\Doctrine\ORM\Exception\UnsupportedQueryBuilderException;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryBuilderKeysetItem;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\QueryCounter;
use Rekalogika\Rekapager\Doctrine\ORM\Internal\Utils;
use Rekalogika\Rekapager\Keyset\Contracts\BoundaryType;
use Rekalogika\Rekapager\Keyset\KeysetPaginationAdapterInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * @template TKey of array-key
 * @template T
 * @implements KeysetPaginationAdapterInterface<TKey,T>
 */
final class QueryBuilderAdapter implements KeysetPaginationAdapterInterface
{
    /**
     * @param array<string,ParameterType|ArrayParameterType|string|int> $typeMapping
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly array $typeMapping = [],
        private readonly bool|null $useOutputWalkers = null,
        private readonly string|null $indexBy = null,
    ) {
        if ($queryBuilder->getFirstResult() !== 0 || $queryBuilder->getMaxResults() !== null) {
            throw new UnsupportedQueryBuilderException();
        }
    }

    public function countItems(): ?int
    {
        $paginator = new Paginator($this->queryBuilder, true);

        $result = $paginator->count();

        if ($result < 0) {
            return null;
        }

        return $result;
    }

    /**
     * @param null|array<string,mixed> $boundaryValues
     */
    private function getQueryBuilder(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): QueryBuilder {
        $queryBuilder = (clone $this->queryBuilder)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // if upper bound, reverse the sort order

        if ($boundaryType === BoundaryType::Upper) {
            $orderings = $this->getReversedSortOrder();

            $first = true;
            foreach ($orderings as $field => $direction) {
                if ($first) {
                    $queryBuilder->orderBy($field, $direction);
                    $first = false;
                } else {
                    $queryBuilder->addOrderBy($field, $direction);
                }
            }
        } else {
            $orderings = $this->getSortOrder();
        }

        // construct the metadata for the next step

        /** @var array<int,array{property:string,value:string,order:'ASC'|'DESC'}> */
        $properties = [];

        foreach ($orderings as $property => $order) {
            /** @var mixed */
            $value = $boundaryValues[$property] ?? null;

            if ($value === null) {
                continue;
            }

            $properties[] = [
                'property' => $property,
                'value' => $value,
                'order' => $order,
            ];
        }

        // build where expression

        $i = 0;
        $expressions = [];
        $z = 1;

        foreach ($properties as $property) {
            if ($i === 0) {
                if (\count($properties) === 1) {
                    if ($property['order'] === 'ASC') {
                        $expressions[] = $queryBuilder->expr()->gt(
                            $property['property'],
                            ":rekapager_where_{$z}"
                        );
                    } else {
                        $expressions[] = $queryBuilder->expr()->lt(
                            $property['property'],
                            ":rekapager_where_{$z}"
                        );
                    }

                    $queryBuilder->setParameter(
                        "rekapager_where_{$z}",
                        $property['value'],
                        // @phpstan-ignore-next-line
                        $this->getType($property['property'], $property['value'])
                    );

                    $i++;
                    continue;
                }


                if ($property['order'] === 'ASC') {
                    $expressions[] = $queryBuilder->expr()->gte(
                        $property['property'],
                        ":rekapager_where_{$z}"
                    );
                } else {
                    $expressions[] = $queryBuilder->expr()->lte(
                        $property['property'],
                        ":rekapager_where_{$z}"
                        // $property['value']
                    );
                }

                $queryBuilder->setParameter(
                    "rekapager_where_{$z}",
                    $property['value'],
                    // @phpstan-ignore-next-line
                    $this->getType($property['property'], $property['value'])
                );

                $i++;
                continue;
            }

            $subExpressions = [];

            foreach (\array_slice($properties, 0, $i) as $equalProperty) {
                $subExpressions[] = $queryBuilder->expr()->eq(
                    $equalProperty['property'],
                    ":rekapager_where_{$z}"
                );

                $queryBuilder->setParameter(
                    "rekapager_where_{$z}",
                    $equalProperty['value'],
                    // @phpstan-ignore-next-line
                    $this->getType($equalProperty['property'], $equalProperty['value'])
                );

                $z++;
            }

            if ($property['order'] === 'ASC') {
                $subExpressions[] = $queryBuilder->expr()->lte(
                    $property['property'],
                    ":rekapager_where_{$z}"
                );

                $queryBuilder->setParameter(
                    "rekapager_where_{$z}",
                    $property['value'],
                    // @phpstan-ignore-next-line
                    $this->getType($property['property'], $property['value'])
                );

                $z++;
            } else {
                $subExpressions[] = $queryBuilder->expr()->gte(
                    $property['property'],
                    ":rekapager_where_{$z}"
                );

                $queryBuilder->setParameter(
                    "rekapager_where_{$z}",
                    $property['value'],
                    // @phpstan-ignore-next-line
                    $this->getType($property['property'], $property['value'])
                );

                $z++;
            }

            $subExpression = $queryBuilder->expr()->not(
                $queryBuilder->expr()->andX(...$subExpressions)
            );

            $expressions[] = $subExpression;

            $i++;
        }

        if (\count($expressions) > 0) {
            $queryBuilder->andWhere($queryBuilder->expr()->andX(...$expressions));
        }

        // adds the boundary values to the select statement

        $i = 1;
        foreach ($this->getBoundaryFieldNames() as $field) {
            $queryBuilder->addSelect(sprintf('%s AS rekapager_boundary_%s', $field, $i));
            $i++;
        }

        return $queryBuilder;
    }

    /** @psalm-suppress InvalidReturnType */
    public function getKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): array {
        $queryBuilder = $this->getQueryBuilder($offset, $limit, $boundaryValues, $boundaryType);

        /** @var array<int,array<int,mixed>> */
        $result = $queryBuilder->getQuery()->getResult();

        if ($boundaryType === BoundaryType::Upper) {
            $result = array_reverse($result);
        }

        $boundaryFieldNames = $this->getBoundaryFieldNames();
        $results = [];

        foreach ($result as $key => $row) {
            /** @var array<string,mixed> */
            $boundaryValues = [];
            foreach (array_reverse($boundaryFieldNames) as $field) {
                /** @var mixed */
                $value = array_pop($row);
                /** @psalm-suppress MixedAssignment */
                $boundaryValues[$field] = $value;
            }

            if (\count($row) === 1) {
                /** @var mixed */
                $row = array_pop($row);
            }

            if ($this->indexBy !== null) {
                $key = Utils::resolveIndex($row, $this->indexBy);
            }

            $results[] = new QueryBuilderKeysetItem($key, $row, $boundaryValues);
        }

        /**
         * @psalm-suppress InvalidReturnStatement
         * @phpstan-ignore-next-line
         */
        return $results;
    }

    public function countKeysetItems(
        int $offset,
        int $limit,
        null|array $boundaryValues,
        BoundaryType $boundaryType,
    ): int {
        $queryBuilder = $this->getQueryBuilder($offset, $limit, $boundaryValues, $boundaryType);
        $paginator = new QueryCounter($queryBuilder->getQuery(), $this->useOutputWalkers);

        $result = $paginator->count();

        if ($result < 0) {
            throw new \RuntimeException('Counting keyset items failed');
        }

        return $result;
    }

    /**
     * @var array<string,'ASC'|'DESC'>
     */
    private null|array $sortOrderCache = null;

    /**
     * @return array<string,'ASC'|'DESC'>
     */
    private function getSortOrder(): array
    {
        if ($this->sortOrderCache !== null) {
            return $this->sortOrderCache;
        }

        /** @var array<string,'ASC'|'DESC'> */
        $result = [];

        /** @var array<int,OrderBy> */
        $orderBys = $this->queryBuilder->getDQLPart('orderBy');

        foreach ($orderBys as $orderBy) {
            if (!$orderBy instanceof OrderBy) {
                continue;
            }

            foreach ($orderBy->getParts() as $part) {
                $exploded = explode(' ', $part);
                $field = $exploded[0];
                $direction = $exploded[1] ?? 'ASC';
                $direction = strtoupper($direction);

                if (!\in_array($direction, ['ASC', 'DESC'], true)) {
                    throw new LogicException('Invalid direction');
                }

                if (isset($result[$field])) {
                    throw new LogicException(sprintf('The field "%s" appears multiple times in the ORDER BY clause.', $field));
                }

                $result[$field] = $direction;
            }
        }

        if (\count($result) === 0) {
            throw new LogicException('The QueryBuilder does not have any ORDER BY clause.');
        }

        return $this->sortOrderCache = $result;
    }

    /**
     * @return array<int,string>
     */
    private function getBoundaryFieldNames(): array
    {
        return array_keys($this->getSortOrder());
    }

    /**
     * @return array<string,'ASC'|'DESC'>
     */
    private function getReversedSortOrder(): array
    {
        $result = [];

        foreach ($this->getSortOrder() as $field => $direction) {
            $result[$field] = $direction === 'ASC' ? 'DESC' : 'ASC';
        }

        return $result;
    }

    private function getType(
        string $fieldName,
        mixed $value
    ): ParameterType|ArrayParameterType|string|int|null {
        $type = $this->typeMapping[$fieldName] ?? null;

        // if type is defined in mapping, return it
        if ($type !== null) {
            return $type;
        }

        // if type is null and value is not object, just return as null
        if (!\is_object($value)) {
            return null;
        }

        // if it is an object, we start looking for the type in the class
        // metadata
        $type = $this->detectTypeFromMetadata($fieldName);

        if ($type !== null) {
            return $type;
        }

        // if not found, use heuristics to detect the type
        return $this->detectTypeByHeuristics($value);
    }

    private function detectTypeByHeuristics(object $value): string|null
    {
        if ($value instanceof \DateTime) {
            return Types::DATETIME_MUTABLE;
        } elseif ($value instanceof \DateTimeImmutable) {
            return Types::DATETIME_IMMUTABLE;
        } elseif ($value instanceof Uuid) {
            return UuidType::NAME;
        } elseif ($value instanceof Ulid) {
            return UlidType::NAME;
        }

        return null;
    }

    private function detectTypeFromMetadata(
        string $fieldName
    ): string|null {
        [$alias, $property] = explode('.', $fieldName);
        $class = $this->getClassFromAlias($alias);

        if ($class === null) {
            return null;
        }

        $manager = $this->queryBuilder->getEntityManager();
        $metadata = $manager->getClassMetadata($class);

        return $metadata->getTypeOfField($property);
    }

    /**
     * @return class-string|null
     */
    private function getClassFromAlias(string $alias): ?string
    {
        $dqlParts = $this->queryBuilder->getDQLParts();
        $from = $dqlParts['from'] ?? [];

        if (!\is_array($from)) {
            throw new LogicException('FROM clause is not an array');
        }

        foreach ($from as $fromItem) {
            if (!$fromItem instanceof From) {
                throw new LogicException('FROM clause is not an instance of From');
            }

            if ($fromItem->getAlias() === $alias) {
                return $fromItem->getFrom();
            }
        }

        return null;
    }
}
