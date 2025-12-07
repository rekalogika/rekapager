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

namespace Rekalogika\Rekapager\Tests\App\Factory;

use Rekalogika\Rekapager\Tests\App\Entity\Comment;
use Rekalogika\Rekapager\Tests\App\Repository\CommentRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Comment>
 *
 * @method        Comment|Proxy create(array|callable $attributes = [])
 * @method static Comment|Proxy createOne(array $attributes = [])
 * @method static Comment|Proxy find(object|array|mixed $criteria)
 * @method static Comment|Proxy findOrCreate(array $attributes)
 * @method static Comment|Proxy first(string $sortedField = 'id')
 * @method static Comment|Proxy last(string $sortedField = 'id')
 * @method static Comment|Proxy random(array $attributes = [])
 * @method static Comment|Proxy randomOrCreate(array $attributes = [])
 * @method static CommentRepository|RepositoryProxy repository()
 * @method static Comment[]|Proxy[] all()
 * @method static Comment[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Comment[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static Comment[]|Proxy[] findBy(array $attributes)
 * @method static Comment[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Comment[]|Proxy[] randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Comment> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Comment> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Comment> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Comment> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Comment> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Comment> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Comment> random(array $attributes = [])
 * @phpstan-method static Proxy<Comment> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<Comment> repository()
 * @phpstan-method static list<Proxy<Comment>> all()
 * @phpstan-method static list<Proxy<Comment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Comment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<Comment>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Comment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Comment>> randomSet(int $number, array $attributes = [])
 */
final class CommentFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function getDefaults(): array
    {
        return [
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Comment $comment): void {})
        ;
    }

    #[\Override]
    protected static function getClass(): string
    {
        return Comment::class;
    }
}
