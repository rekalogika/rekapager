<?php

namespace Rekalogika\Rekapager\Tests\App\Factory;

use Rekalogika\Rekapager\Tests\App\Entity\Category;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Post>
 */
final class PostFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct() {}

    #[\Override]
    public static function class(): string
    {
        return Post::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->text(20),
            'content' => self::faker()->text(200),
            'date' => self::faker()->dateTimeBetween('-1 month', 'now'),
            'category' => self::faker()->randomElement([
                Category::Animalia,
                Category::Plantae,
                Category::Fungi,
                Category::Bacteria,
            ]),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Post $post): void {})
        ;
    }
}
