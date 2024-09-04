<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

/*
 * This file is part of rekalogika/rekapager package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Rekapager\ApiPlatform\Implementation;

use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;

/**
 * Some code borrowed from ApiPlatform's PaginationExtension
 *
 * @template T of object
 * @implements QueryResultCollectionExtensionInterface<T>
 */
final readonly class RekapagerExtension implements QueryResultCollectionExtensionInterface
{
    public function __construct(
        private PagerFactory $pagerFactory,
        private Pagination $pagination,
    ) {}

    /**
     * @param array<array-key,mixed> $context
     */
    #[\Override]
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {}

    /**
     * @param array<array-key,mixed> $context
     */
    #[\Override]
    public function supportsResult(
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): bool {
        /** @psalm-suppress InternalMethod */
        $extraProperties = $operation?->getExtraProperties() ?? [];
        /** @var bool */
        $isEnabled = $extraProperties['rekapager_orm_enabled'] ?? false;

        if ($isEnabled === false) {
            return false;
        }

        if ((bool) ($context['graphql_operation_name'] ?? false)) {
            return $this->pagination->isGraphQlEnabled($operation, $context);
        }

        return $this->pagination->isEnabled($operation, $context);
    }

    /**
     * @param array<array-key,mixed> $context
     * @return iterable<array-key,mixed>
     */
    #[\Override]
    public function getResult(
        QueryBuilder $queryBuilder,
        ?string $resourceClass = null,
        ?Operation $operation = null,
        array $context = [],
    ): iterable {
        $pageable = new KeysetPageable(new QueryBuilderAdapter($queryBuilder));

        return $this->pagerFactory->createPager($pageable, $operation, $context);
    }
}
