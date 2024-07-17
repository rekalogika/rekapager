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

namespace Rekalogika\Rekapager\Tests\App\Controller;

use Rekalogika\Rekapager\Bundle\Contracts\PagerFactoryInterface;
use Rekalogika\Rekapager\Bundle\PagerOptions;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Doctrine\SqlLogger;
use Rekalogika\Rekapager\Tests\App\Form\PagerParameters;
use Rekalogika\Rekapager\Tests\App\Form\PagerParametersType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** @psalm-suppress PropertyNotSetInConstructor */
class DemoController extends AbstractController
{
    /**
     * @param iterable<PageableGeneratorInterface<array-key,mixed>> $pageableGenerators
     * @psalm-suppress DeprecatedClass
     */
    public function __construct(
        #[TaggedIterator('rekalogika.rekapager.pageable_generator', defaultIndexMethod: 'getKey')]
        private readonly iterable $pageableGenerators,
    ) {
    }

    /**
     * @param PagerFactoryInterface<PagerOptions> $pagerFactory
     */
    #[Route('/{key?}', name: 'rekapager')]
    public function index(
        Request $request,
        SqlLogger $logger,
        PagerFactoryInterface $pagerFactory,
        ?string $key,
    ): Response {
        $form = $this->createForm(PagerParametersType::class, new PagerParameters());
        $form->handleRequest($request);
        /** @var PagerParameters */
        $pagerParameters = $form->getData();


        /** @psalm-suppress InvalidArgument */
        $pageableGenerators = iterator_to_array($this->pageableGenerators);

        /** @var array<string,PageableGeneratorInterface<array-key,mixed>> $pageableGenerators */

        if ($key === null) {
            foreach ($pageableGenerators as $pageableGenerator) {
                $key = $pageableGenerator::getKey();
                break;
            }
            \assert($key !== null);
            $pageableGenerator = $pageableGenerators[$key];
        } else {
            $pageableGenerator = $pageableGenerators[$key] ?? null;
        }

        if ($pageableGenerator === null) {
            throw $this->createNotFoundException();
        }

        $pageable = $pageableGenerator->generatePageable(
            itemsPerPage: $pagerParameters->itemsPerPage,
            count: $pagerParameters->count,
            setName: $pagerParameters->set,
            pageLimit: $pagerParameters->adapterPageLimit,
        );

        $pager = $pagerFactory->createPager(
            pageable: $pageable,
            request: $request,
            options: new PagerOptions(
                proximity: $pagerParameters->proximity,
                pageLimit: $pagerParameters->pagerPageLimit,
            )
        );

        $title = $pageableGenerator->getTitle();

        return $this->render('app/index.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'sql' => $logger,
            'pageable_generators' => $pageableGenerators,
            'source_code' => $this->getSourceCode($pageableGenerator::class),
            'form' => $form->createView(),
            'template' => $pagerParameters->template,
            'locale' => $pagerParameters->locale,
            'proximity' => $pagerParameters->viewProximity,
        ]);
    }

    /**
     * @param class-string $class
     * @return string
     */
    private function getSourceCode(string $class): string
    {
        $reflectionClass = new \ReflectionClass($class);
        $file = $reflectionClass->getFileName();

        if ($file === false) {
            throw new \RuntimeException('Failed to get file name');
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new \RuntimeException('Failed to read file');
        }

        $contents = preg_replace(
            '|^.*//\s+@highlight-start\n|s',
            '',
            $contents,
        ) ?? throw new \RuntimeException('Regex fail');

        $contents = preg_replace(
            '|\n\s+//\s+@highlight-end.*\n$|s',
            '',
            $contents,
        ) ?? throw new \RuntimeException('Regex fail');

        $contents = $this->unindent($contents);

        return $contents;
    }

    private function unindent(string $text): string
    {
        if (preg_match('{\A[\r\n]*(\h+)[^\r\n]*+(?:[\r\n]++(?>\1[^\r\n]*+(?:[\r\n]+|\z)|[\r\n]+)+)?\z}', rtrim($text), $match)) {
            $text = preg_replace('{^' . $match[1] . '}m', '', $text);
        }

        return $text ?? '';
    }
}
