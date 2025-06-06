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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Borrowed from the class of the same name in Doctrine ORM.
 *
 * @phpstan-import-type QueryComponent from Parser
 */
final class CountOutputWalker2 extends SqlWalker
{
    private readonly AbstractPlatform $platform;
    private readonly ResultSetMapping $rsm;

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->platform = $query->getEntityManager()->getConnection()->getDatabasePlatform();
        $this->rsm      = $parserResult->getResultSetMapping();

        parent::__construct($query, $parserResult, $queryComponents);
    }

    public function walkSelectStatement(SelectStatement $AST)
    {
        if ($this->platform instanceof SQLServerPlatform) {
            $AST->orderByClause = null;
        }

        $sql = parent::walkSelectStatement($AST);

        // modification start
        $maxResults = $this->getQuery()->getHint('maxResults');
        \assert(\is_int($maxResults));
        $firstResult = $this->getQuery()->getHint('firstResult');
        \assert(\is_int($firstResult));
        $sql = $this->platform->modifyLimitQuery($sql, $maxResults, $firstResult);
        // modification end

        if ($AST->groupByClause) {
            return \sprintf(
                'SELECT COUNT(*) AS dctrn_count FROM (%s) dctrn_table',
                $sql,
            );
        }

        // Find out the SQL alias of the identifier column of the root entity
        // It may be possible to make this work with multiple root entities but that
        // would probably require issuing multiple queries or doing a UNION SELECT
        // so for now, It's not supported.

        // Get the root entity and alias from the AST fromClause
        $from = $AST->fromClause->identificationVariableDeclarations;
        if (\count($from) > 1) {
            throw new RuntimeException('Cannot count query which selects two FROM components, cannot make distinction');
        }

        $fromRoot       = reset($from);
        $rootAlias      = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass      = $this->getMetadataForDqlAlias($rootAlias);
        $rootIdentifier = $rootClass->identifier;

        // For every identifier, find out the SQL alias by combing through the ResultSetMapping
        $sqlIdentifier = [];
        foreach ($rootIdentifier as $property) {
            if (isset($rootClass->fieldMappings[$property])) {
                foreach (array_keys($this->rsm->fieldMappings, $property, true) as $alias) {
                    if ($this->rsm->columnOwnerMap[$alias] === $rootAlias) {
                        $sqlIdentifier[$property] = $alias;
                    }
                }
            }

            if (isset($rootClass->associationMappings[$property])) {
                $joinColumn = $rootClass->associationMappings[$property]['joinColumns'][0]['name'];

                foreach (array_keys($this->rsm->metaMappings, $joinColumn, true) as $alias) {
                    if ($this->rsm->columnOwnerMap[$alias] === $rootAlias) {
                        $sqlIdentifier[$property] = $alias;
                    }
                }
            }
        }

        if (\count($rootIdentifier) !== \count($sqlIdentifier)) {
            throw new RuntimeException(\sprintf(
                'Not all identifier properties can be found in the ResultSetMapping: %s',
                implode(', ', array_diff($rootIdentifier, array_keys($sqlIdentifier))),
            ));
        }

        // Build the counter query
        return \sprintf(
            'SELECT COUNT(*) AS dctrn_count FROM (SELECT DISTINCT %s FROM (%s) dctrn_result) dctrn_table',
            implode(', ', $sqlIdentifier),
            $sql,
        );
    }
}
