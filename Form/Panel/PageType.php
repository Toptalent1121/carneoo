<?php

namespace App\Form\Panel;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use App\Entity\Page;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;

class PageType extends AbstractType
{
    protected $translator;
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator = null)
    {
        $this->translator   = $translator;
        $this->tokenStorage = $tokenStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $readonly = false;

        $builder
            ->add('name', TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length([
                            'min' => 3,
                            'max' => 128,
                            ]),
                    ],
                    "label" => "form.elements.name",
                    "attr" => [
                        "placeholder" => "form.elements.namePlaceholder",
                        "readonly" => $readonly,
                    ],
                    "required" => false,
                    'translation_domain' => 'page',
            ])
            ->add('parent', EntityType::class,
                [
                    'constraints' => [],
                    'required' => false,
                    'label' => 'form.elements.parent',
                    'placeholder' => 'form.elements.parentPlaceholder',
                    'attr' => [
                        'novalidate' => 'novalidate',
                        "readonly" => $readonly,
                        "disabled" => $readonly,
                    ],
                    'class' => Page::class,
                    'query_builder' => function (EntityRepository $er) use ($builder) {
                        $queryBuilder = $er->createQueryBuilder('q');
                        $expr         = $queryBuilder->expr();
                        $query        = $queryBuilder
                            ->select('p')
                            ->from('App\Entity\Page', 'p');

                        if (!empty($builder->getData()->getId())) {
                            $query
                            ->where($expr->neq('p.id',
                                    $builder->getData()->getId()));
                        }

                        $query->orderBy('p.name', 'ASC');
                        return $query;
                    },
                    'translation_domain' => 'page',
                    'choice_label' => 'name',
                    'choice_value' => function (Page $entity = null) {
                        return $entity ? $entity->getId() : '';
                    },
            ])
            ->add('active', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.active',
                    'translation_domain' => 'page',
                    'attr' => [
                        'class' => 'custom-control-input',
                        "readonly" => $readonly,
                    ]
            ])
            ->add('menu', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.menu',
                    'translation_domain' => 'page',
                    'attr' => [
                        'class' => 'custom-control-input',
                        "readonly" => $readonly,
                    ]
                ])
            ->add('submit', SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3',
                        "disabled" => $readonly,
                    ],
                    'label' => 'list.buttons.submit',
					'translation_domain' => 'menu'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [
            ],
            'data_class' => Page::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ]);
    }
}