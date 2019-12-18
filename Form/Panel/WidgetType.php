<?php

namespace App\Form\Panel;

use App\Entity\Banner;
use App\Repository\WidgetRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use App\Entity\Widget;
use App\Entity\Page;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;
use App\Validator\Constraints\Unique;

class WidgetType extends AbstractType
{
    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @var TokenStorageInterface $tokenStorage
     */
    protected $tokenStorage;

    /**
     * @var WidgetRepository $widgetRepository
     */
    private $widgetRepository;

    /**
     * WidgetType constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface|null $translator
     * @param WidgetRepository $widgetRepository
     */
    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator = null, WidgetRepository $widgetRepository)
    {
        $this->translator   = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->widgetRepository = $widgetRepository;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
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
                        new Unique([
                            'entity' => 'App\Entity\Widget',
                            'field' => 'name',
                            'message' => $this->translator->trans('form.elements.validation.notUniqueValue',
                                [], 'widget'),
                            ]),
                    ],
                    "label" => "form.elements.name",
                    "attr" => [
                        "placeholder" => "form.elements.namePlaceholder",
                        "readonly" => $readonly,
                    ],
                    "required" => false,
                    'translation_domain' => 'widget',
            ])
            ->add('type', ChoiceType::class,
                [
                    'choices' => [
                        'chooseType' => null,
                        'banner' => 'BANNER',
                        'slider' => 'SLIDER',
                        'carSearch' => 'CAR_SEARCH',
                        'html' => 'HTML',
                        'contactData' => 'CONTACT_DATA',
                        'customerOffice' => 'CUSTOMER_OFFICE',
                        'carneooInNumbers' => 'CARNEOO_IN_NUMBERS',
                        'justDrive' => 'JUST_DRIVE',
                        'bestSellers' => 'BEST_SELLERS',
                        'stockCars' => 'STOCK_CARS',
                        'discounts' => 'DISCOUNTS',
                        'intro' => 'INTRO',
                        'meinkonto' => 'MY_ACCOUNT',
                        'konfigurator' => 'CONFIGURATOR',
                        'vorteile' => 'VORTEILE',
                    ],
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.types.type',
                    'translation_domain' => 'widget',
                    'choice_translation_domain' => 'widget',
                    'choice_label' => function ($choiceValue, $key, $value) {
                        return 'form.elements.types.'.$key;
                    },
                    'choice_value' => function($value) {
                        if (empty($value)) {
                            return null;
                        }
                        return $value;
                    },
                    'constraints' => [
                        new NotBlank(),
                    ],
            ])
            ->add('content', TextareaType::class,
                [
                    'constraints' => [],
                    "label" => "form.elements.content",
                    "attr" => [
                        "placeholder" => "form.elements.contentPlaceholder",
                        "readonly" => $readonly,
                    ],
                    "required" => false,
                    'translation_domain' => 'widget',
            ])
            ->add('active', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.active',
                    'translation_domain' => 'widget',
                    'attr' => [
                        'class' => 'custom-control-input',
                        "readonly" => $readonly,
                    ]
            ])
            ->add('banner', EntityType::class, [
                'required' => false,
                'class' => Banner::class,
                'query_builder' => function (EntityRepository $er) use ($builder) {
                    $queryBuilder = $er->createQueryBuilder('q');
                    $expr         = $queryBuilder->expr();
                    $query        = $queryBuilder
                        ->select('b')
                        ->from('App\Entity\Banner', 'b')
                    ;

                    $query->orderBy('b.name', 'ASC');

                    return $query;
                },
                'translation_domain' => 'widget',
                'choice_label' => 'name',
                'label' => 'form.elements.banner',
            ])
            ->add('content_id', ChoiceType::class,
                [
                    'choices' => [
                        'choose' => null,
                    ],
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.content',
                    'translation_domain' => 'widget',
                    'constraints' => [],
            ])
            ->add('submit', SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success ml-3',
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
            'data_class' => Widget::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ]);
    }
}