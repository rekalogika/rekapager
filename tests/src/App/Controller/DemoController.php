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

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Rekapager\Adapter\Common\IndexResolver;
use Rekalogika\Rekapager\Bundle\Contracts\PagerFactoryInterface;
use Rekalogika\Rekapager\Bundle\PagerOptions;
use Rekalogika\Rekapager\Tests\App\Contracts\PageableGeneratorInterface;
use Rekalogika\Rekapager\Tests\App\Doctrine\SqlLogger;
use Rekalogika\Rekapager\Tests\App\Entity\Post;
use Rekalogika\Rekapager\Tests\App\Form\PagerParameters;
use Rekalogika\Rekapager\Tests\App\Form\PagerParametersType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/** @psalm-suppress PropertyNotSetInConstructor */
class DemoController extends AbstractController
{
    public function __construct(
        private PageableGenerators $pageableGenerators,
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('app/index.html.twig');
    }

    /**
     * @param PagerFactoryInterface<PagerOptions> $pagerFactory
     */
    #[Route('/page/{key?}', name: 'page')]
    public function page(
        Request $request,
        SqlLogger $logger,
        PagerFactoryInterface $pagerFactory,
        ?string $key,
    ): Response {
        $form = $this->createForm(PagerParametersType::class, new PagerParameters());
        $form->handleRequest($request);
        /** @var PagerParameters */
        $pagerParameters = $form->getData();

        $pageableGenerator = $this->pageableGenerators->getPageableGenerators()[$key] ?? throw $this->createNotFoundException();

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
            ),
        );

        $title = $pageableGenerator->getTitle();

        $pageIdentifier = $pager->getCurrentPage()->getPageIdentifier();
        $cloner = new VarCloner();
        $dumper = new HtmlDumper();

        $dumper->setTheme('light');

        $output = fopen('php://memory', 'r+b') ?: throw new \RuntimeException('Failed to open memory stream');
        $dumper->dump($cloner->cloneVar($pageIdentifier), $output);
        $output = stream_get_contents($output, -1, 0);

        return $this->render('app/page.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'sql' => $logger,
            'page_identifier' => $output,
            'source_code' => $this->getSourceCode($pageableGenerator::class),
            'form' => $form->createView(),
            'template' => $pagerParameters->template,
            'locale' => $pagerParameters->locale,
            'proximity' => $pagerParameters->viewProximity,
            'controller' => $this,
        ]);
    }

    #[Route('/batch/{key?}', name: 'batch')]
    public function batch(
        SqlLogger $logger,
        EntityManagerInterface $entityManager,
        ?string $key,
    ): Response {
        $pageableGenerator = $this->pageableGenerators->getPageableGenerators()[$key] ?? throw $this->createNotFoundException();

        $pageable = $pageableGenerator->generatePageable(
            itemsPerPage: 5,
            count: false,
            setName: 'medium',
        );

        // @highlight-start

        $output = '<ul>';

        foreach ($pageable->withItemsPerPage(5)->getPages() as $page) {
            $output .= '<li>';
            $output .= \sprintf('Processing page %d', $page->getPageNumber() ?? 'null');

            $output .= '<ul>';

            /** @var mixed $item */
            foreach ($page as $item) {
                if (\is_array($item)) {
                    // used by DBAL adapter
                    $id = IndexResolver::resolveIndex($item, 'id');
                    $date = IndexResolver::resolveIndex($item, 'date');
                    $title = IndexResolver::resolveIndex($item, 'title');
                    $category = IndexResolver::resolveIndex($item, 'category');

                    $output .= \sprintf(
                        '<li>Processing item id %s, date %s, title %s, category %s</li>',
                        $id,
                        $date,
                        $title,
                        $category,
                    );
                } else {
                    // used by other adapters
                    \assert($item instanceof Post);
                    $output .= \sprintf(
                        '<li>Processing item id %s, date %s, title %s, category %s</li>',
                        $item->getId(),
                        $item->getDate()?->format('Y-m-d') ?? 'null',
                        $item->getTitle() ?? 'null',
                        $item->getCategory()->value,
                    );
                }
            }

            $output .= '</ul>';
            $output .= '</li>';

            $entityManager->clear();
        }

        $output .= '</ul>';
        // @highlight-end

        $title = $pageableGenerator->getTitle();

        return $this->render('app/batch.html.twig', [
            'title' => $title,
            'sql' => $logger,
            'source_code' => $this->getSourceCode($pageableGenerator::class) . "\n" .
                $this->unindent($this->getSourceCode(self::class)),
            'output' => $output,
            'controller' => $this,
        ]);
    }

    #[Route('/console', name: 'console')]
    public function console(): Response
    {
        return $this->render('app/console.html.twig');
    }

    /**
     * @param class-string $class
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

        return $this->unindent($contents);
    }

    public function unindent(string $text): string
    {
        if (preg_match('{\A[\r\n]*(\h+)[^\r\n]*+(?:[\r\n]++(?>\1[^\r\n]*+(?:[\r\n]+|\z)|[\r\n]+)+)?\z}', rtrim($text), $match)) {
            $text = preg_replace('{^' . $match[1] . '}m', '', $text);
        }

        return $text ?? '';
    }
}
