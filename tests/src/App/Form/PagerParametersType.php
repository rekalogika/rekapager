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

namespace Rekalogika\Rekapager\Tests\App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PagerParameters>
 */
class PagerParametersType extends AbstractType // @phpstan-ignore-line
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('set', ChoiceType::class, [
                'label' => 'Data set',
                'choices' => [
                    'empty (0)' => 'empty',
                    'tiny (3)' => 'tiny',
                    'small (10)' => 'small',
                    'medium (103)' => 'medium',
                    'large (1003)' => 'large',
                ],
            ])
            ->add('count', ChoiceType::class, [
                'label' => 'Count strategy',
                'choices' => [
                    'never, and assume the count is unknown' => false,
                    'get the count from the underlying adapter' => true,
                    'forced at 10' => 10,
                    'forced at 50' => 50,
                    'forced at 1000' => 1000,
                ],
            ])
            ->add('itemsPerPage', ChoiceType::class, [
                'choices' => [
                    '3' => 3,
                    '5' => 5,
                    '10' => 10,
                    '20' => 20,
                    '50' => 50,
                ],
            ])
            ->add('proximity', ChoiceType::class, [
                'choices' => [
                    '0' => 0,
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                ],
            ])
            ->add('adapterPageLimit', ChoiceType::class, [
                'label' => 'Page limit imposed by offset pageable',
                'help' => 'offset pagination only, does not affect keyset pagination',
                'choices' => [
                    'Unlimited' => null,
                    '5' => 5,
                    '10' => 10,
                    '20' => 20,
                    '50' => 50,
                    '100' => 100,
                    '200' => 200,
                ],
            ])
            ->add('pagerPageLimit', ChoiceType::class, [
                'label' => 'Page limit imposed by pager',
                'choices' => [
                    'Unlimited' => null,
                    '5' => 5,
                    '10' => 10,
                    '20' => 20,
                    '50' => 50,
                    '100' => 100,
                    '200' => 200,
                ],
            ])
            ->add('template', ChoiceType::class, [
                'label' => 'Template',
                'choices' => [
                    '@RekalogikaRekapager/base.html.twig' => '@RekalogikaRekapager/base.html.twig',
                    '@RekalogikaRekapager/default.html.twig' => '@RekalogikaRekapager/default.html.twig',
                    '@RekalogikaRekapager/bootstrap5.html.twig' => '@RekalogikaRekapager/bootstrap5.html.twig',
                ],
            ])
            ->add('locale', ChoiceType::class, [
                'label' => 'Locale',
                'choices' => [
                    'Default' => null,
                    'en' => 'en',
                    'id' => 'id',
                ],
            ])
            ->add('viewProximity', ChoiceType::class, [
                'choices' => [
                    'No change' => null,
                    '0' => 0,
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'data_class' => PagerParameters::class,
            'csrf_protection' => false,
        ]);
    }
}
