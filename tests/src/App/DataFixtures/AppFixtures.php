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

namespace Rekalogika\Rekapager\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Rekalogika\Rekapager\Tests\App\Factory\PostFactory;
use Rekalogika\Rekapager\Tests\App\Factory\UserFactory;

final class AppFixtures extends Fixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $user = UserFactory::createOne();

        PostFactory::createMany(3, [
            'user' => $user,
            'setName' => 'tiny',
        ]);

        PostFactory::createMany(10, [
            'user' => $user,
            'setName' => 'small',
        ]);

        PostFactory::createMany(103, [
            'user' => $user,
            'setName' => 'medium',
        ]);

        PostFactory::createMany(1003, [
            'user' => $user,
            'setName' => 'large',
        ]);

        // PostFactory::createMany(10003, [
        //     'user' => $user,
        //     'setName' => 'huge'
        // ]);

        $manager->flush();
    }
}
