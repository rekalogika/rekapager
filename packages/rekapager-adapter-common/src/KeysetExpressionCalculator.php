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
use Doctrine\Common\Collections\Order;

/**
 * @internal
 */
final class KeysetExpressionCalculator
{
    private function __construct()
    {
    }

    /**
     * @param array<string,Order> $orderBy
     * @param null|array<string,mixed> $boundaryValues Key is the property name, value is the bound value. Null if unbounded.
     */
    public static function calculate(
        array $orderBy,
        null|array $boundaryValues,
    ): ?Expression {
        /** @var array<int,array{property:string,value:string,order:Order}> */
        $properties = [];

        foreach ($orderBy as $property => $order) {
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

        foreach ($properties as $property) {
            if ($i === 0) {
                if (\count($properties) === 1) {
                    if ($property['order'] === Order::Ascending) {
                        $expressions[] = Criteria::expr()->gt(
                            $property['property'],
                            $property['value']
                        );
                    } else {
                        $expressions[] = Criteria::expr()->lt(
                            $property['property'],
                            $property['value']
                        );
                    }

                    $i++;
                    continue;
                }

                if ($property['order'] === Order::Ascending) {
                    $expressions[] = Criteria::expr()->gte(
                        $property['property'],
                        $property['value']
                    );
                } else {
                    $expressions[] = Criteria::expr()->lte(
                        $property['property'],
                        $property['value']
                    );
                }

                $i++;
                continue;
            }

            $subExpressions = [];

            foreach (\array_slice($properties, 0, $i) as $equalProperty) {
                $subExpressions[] = Criteria::expr()->eq(
                    $equalProperty['property'],
                    $equalProperty['value']
                );
            }

            if ($property['order'] === Order::Ascending) {
                $subExpressions[] = Criteria::expr()->lte(
                    $property['property'],
                    $property['value']
                );
            } else {
                $subExpressions[] = Criteria::expr()->gte(
                    $property['property'],
                    $property['value']
                );
            }

            $subExpression = Criteria::expr()->not(
                Criteria::expr()->andX(...$subExpressions)
            );

            $expressions[] = $subExpression;

            $i++;
        }

        return $expressions === [] ? null : Criteria::expr()->andX(...$expressions);
    }
}
