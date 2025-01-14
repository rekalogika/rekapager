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

namespace Rekalogika\Rekapager\Doctrine\ORM\Internal;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\SqlOutputWalker;
use Doctrine\ORM\Tools\Pagination\CountWalker;

/**
 * @see Paginator
 * @internal
 */
final class QueryCounter implements \Countable
{
    use SQLResultCasing;

    /**
     * @var int<0,max>|null
     */
    private int|null $count = null;

    public function __construct(
        private readonly Query $query,
        private readonly null|bool $useOutputWalkers = null,
    ) {}

    #[\Override]
    public function count(): int
    {
        if ($this->count === null) {
            try {
                /**
                 * @psalm-suppress PropertyTypeCoercion
                 * @psalm-suppress MixedArgument
                 * @phpstan-ignore-next-line
                 */
                $this->count = (int) array_sum(array_map(current(...), $this->getCountQuery()->getScalarResult()));
            } catch (NoResultException) {
                $this->count = 0;
            }
        }

        \assert($this->count >= 0);

        return $this->count;
    }

    /**
     * Returns Query prepared to count.
     */
    private function getCountQuery(): Query
    {
        $countQuery = $this->cloneQuery($this->query);

        if (!$countQuery->hasHint(CountWalker::HINT_DISTINCT)) {
            $countQuery->setHint(CountWalker::HINT_DISTINCT, true);
        }

        if ($this->useOutputWalker($countQuery)) {
            $platform = $countQuery->getEntityManager()->getConnection()->getDatabasePlatform(); // law of demeter win

            $rsm = new ResultSetMapping();
            $rsm->addScalarResult($this->getSQLResultCasing($platform, 'dctrn_count'), 'count');

            if (class_exists(SqlOutputWalker::class)) {
                $outputWalker = CountOutputWalker::class;
            } else {
                $outputWalker = CountOutputWalker2::class;
            }

            $countQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, $outputWalker);
            $countQuery->setHint('maxResults', $this->query->getMaxResults());
            $countQuery->setHint('firstResult', $this->query->getFirstResult());
            $countQuery->setResultSetMapping($rsm);
        } else {
            $this->appendTreeWalker($countQuery, CountWalker::class);
            $this->unbindUnusedQueryParams($countQuery);
        }

        $countQuery->setFirstResult(0)->setMaxResults(null);

        return $countQuery;
    }

    private function cloneQuery(Query $query): Query
    {
        $cloneQuery = clone $query;

        $cloneQuery->setParameters(clone $query->getParameters());
        $cloneQuery->setCacheable(false);

        /** @var mixed $value */
        foreach ($query->getHints() as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        return $cloneQuery;
    }

    /**
     * Determines whether to use an output walker for the query.
     */
    private function useOutputWalker(Query $query): bool
    {
        if ($this->useOutputWalkers === null) {
            return (bool) $query->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER) === false;
        }

        return $this->useOutputWalkers;
    }

    /**
     * Appends a custom tree walker to the tree walkers hint.
     *
     * @psalm-param class-string $walkerClass
     */
    private function appendTreeWalker(Query $query, string $walkerClass): void
    {
        /** @var mixed */
        $hints = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if ($hints === false) {
            $hints = [];
        }

        /** @var array<string,mixed> $hints */

        $hints[] = $walkerClass;
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $hints);
    }

    private function unbindUnusedQueryParams(Query $query): void
    {
        $parser            = new Parser($query);
        $parameterMappings = $parser->parse()->getParameterMappings();
        /** @var Collection<int,mixed>|Parameter[] $parameters */
        $parameters = $query->getParameters();

        /** @var Parameter $parameter */
        foreach ($parameters as $key => $parameter) {
            $parameterName = $parameter->getName();

            if (!isset($parameterMappings[$parameterName]) && !\array_key_exists($parameterName, $parameterMappings)) {
                /** @psalm-suppress MixedArgumentTypeCoercion */
                unset($parameters[$key]);
            }
        }

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @phpstan-ignore-next-line
         */
        $query->setParameters($parameters);
    }
}
