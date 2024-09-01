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

namespace Rekalogika\Rekapager\Adapter\Common;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;

/**
 * @internal
 */
final class KeysetExpressionCalculator
{
    private function __construct()
    {
    }

    /**
     * @param non-empty-list<Field> $fields
     */
    public static function calculate(array $fields): Expression
    {
        $i = 0;
        $expressions = [];

        foreach ($fields as $field) {
            if ($i === 0) {
                if (\count($fields) === 1) {
                    if ($field->isAscending()) {
                        $expressions[] = Criteria::expr()->gt(
                            $field->getName(),
                            $field->getValue()
                        );
                    } else {
                        $expressions[] = Criteria::expr()->lt(
                            $field->getName(),
                            $field->getValue()
                        );
                    }

                    $i++;
                    continue;
                }

                if ($field->isAscending()) {
                    $expressions[] = Criteria::expr()->gte(
                        $field->getName(),
                        $field->getValue()
                    );
                } else {
                    $expressions[] = Criteria::expr()->lte(
                        $field->getName(),
                        $field->getValue()
                    );
                }

                $i++;
                continue;
            }

            $subExpressions = [];

            foreach (\array_slice($fields, 0, $i) as $equalField) {
                $subExpressions[] = Criteria::expr()->eq(
                    $equalField->getName(),
                    $equalField->getValue()
                );
            }

            if ($field->isAscending()) {
                if ($i === \count($fields) - 1) {
                    $subExpressions[] = Criteria::expr()->lte(
                        $field->getName(),
                        $field->getValue()
                    );
                } else {
                    $subExpressions[] = Criteria::expr()->lt(
                        $field->getName(),
                        $field->getValue()
                    );
                }
            } else {
                if ($i === \count($fields) - 1) {
                    $subExpressions[] = Criteria::expr()->gte(
                        $field->getName(),
                        $field->getValue()
                    );
                } else {
                    $subExpressions[] = Criteria::expr()->gt(
                        $field->getName(),
                        $field->getValue()
                    );
                }
            }

            $subExpression = Criteria::expr()->not(
                Criteria::expr()->andX(...$subExpressions)
            );

            $expressions[] = $subExpression;

            $i++;
        }

        return Criteria::expr()->andX(...$expressions);
    }
}
