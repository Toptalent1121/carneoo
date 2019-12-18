<?php

namespace App\Form\Panel;

use App\Entity\Discount;
use App\Entity\Dealer;
use App\Traits\Panel\JATOMapperTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityRepository;
use App\Repository\ConfiguratorModelRepository;
use App\Repository\ConfiguratorMarkRepository;
use App\Repository\DiscountRepository;
use Doctrine\ORM\EntityManagerInterface;

class DiscountType extends AbstractType
{
    use JATOMapperTrait;

    protected $translator;
    protected $tokenStorage;

    /**
     * @var ConfiguratorModelRepository $configuratorModelRepository
     */
    private $configuratorModelRepository;

    /**
     * @var ConfiguratorMarkRepository $configuratorMarkRepository
     */
    private $configuratorMarkRepository;

    /**
     * @var DiscountRepository $discountRepository
     */
    private $discountRepository;

    /**
     * DiscountType constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface|null $translator
     * @param ConfiguratorModelRepository $configuratorModelRepository
     * @param ConfiguratorMarkRepository $configuratorMarkRepository
     * @param EntityManagerInterface $em
     * @param DiscountRepository $discountRepository
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator = null,
        ConfiguratorModelRepository $configuratorModelRepository,
        ConfiguratorMarkRepository $configuratorMarkRepository,
        EntityManagerInterface $em,
        DiscountRepository $discountRepository
    )
    {
        $this->translator   = $translator;
        $this->tokenStorage = $tokenStorage;
		$this->configuratorModelRepository = $configuratorModelRepository;
		$this->configuratorMarkRepository = $configuratorMarkRepository;
		$this->discountRepository = $discountRepository;
		$this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class,
                [
                    'constraints' => [
                        new NotBlank()
                    ],
                    "label" => "form.elements.name",
                    "attr" => [
                        "placeholder" => "form.elements.namePlaceholder"
                    ],
                    "required" => true,
                    'translation_domain' => 'discount',
            ])
			->add('type', ChoiceType::class,
                [
                    'choices' => [
                        'chooseType' => null,
                        'rebate' => 'R',
						'additionalCost' => 'C'
                    ],
                    'multiple' => false,
                    'expanded' => false,
					'required' => true,
                    'label' => 'form.elements.types.type',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
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
			->add('front_name', TextType::class,
                [
                    'constraints' => [
                        new NotBlank()
                    ],
                    "label" => "form.elements.frontName",
                    "attr" => [
                        "placeholder" => "form.elements.frontNamePlaceholder"
                    ],
                    "required" => true,
                    'translation_domain' => 'discount',
            ])
			->add('amount_type', ChoiceType::class,
                [
                    'choices' => [
                        'chooseType' => null,
                        'quota' => 'Q',
						'percent' => 'P'
                    ],
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.amount_types.amount_type',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                    'choice_label' => function ($choiceValue, $key, $value) {
                        return 'form.elements.amount_types.'.$key;
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
            ->add('main', CheckboxType::class,
                [
                    'label' => 'form.elements.main',
                    'translation_domain' => 'discount',
                    'attr' => [
                        'class' => 'custom-control-input'
                    ],
                    'required' => false,

            ])
            ->add('obligatory', CheckboxType::class,
                [
                    'label' => 'form.elements.obligatory',
                    'translation_domain' => 'discount',
                    'attr' => [
                        'class' => 'custom-control-input'
                    ],
                    'required' => false,

                ])
			->add('level', ChoiceType::class,
                [
                    'choices' => [
                        'chooseType' => null,
                        'mark' => 'MARK',
						'model' => 'MODEL',
						'body' => 'BODY',
						'version' => 'VERSION'
                    ],
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.levels.level',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                    'choice_label' => function ($choiceValue, $key, $value) {
                        return 'form.elements.levels.'.$key;
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
			->add('mark', ChoiceType::class,
                [
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.levels.marks',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
            ])
			->add('model', ChoiceType::class,
                [
                    'multiple' => true,
                    'expanded' => false,
                    'mapped' => false,
                    'label' => 'form.elements.levels.models',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                    'attr' => [
                        'class' => 'select2-multi-select'
                    ],
            ])
			->add('version', ChoiceType::class,
                [
                    'multiple' => true,
                    'expanded' => false,
                    'mapped' => false,
                    'label' => 'form.elements.levels.versions',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                    'attr' => [
                        'class' => 'select2-multi-select'
                    ],
            ])
			->add('body', ChoiceType::class,
                [
                    'multiple' => true,
                    'expanded' => false,
                    'mapped' => false,
                    'label' => 'form.elements.levels.bodies',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                    'attr' => [
                        'class' => 'select2-multi-select'
                    ],
            ])
            ->add('value', TextType::class,
                [
                    'constraints' => [
                        new NotBlank()
                    ],
                    "label" => "form.elements.value",
                    "attr" => [
                        "placeholder" => "form.elements.valuePlaceholder"
                    ],
                    "required" => false,
                    'translation_domain' => 'discount',
            ])
			->add('carneo_provision', TextType::class,
                [
                    "label" => "form.elements.provision",
                    "required" => true,
                    'translation_domain' => 'discount',
					'constraints' => [
                        new NotBlank(),
                    ],
            ])
			->add('carneo_amount_type', ChoiceType::class,
                [
                    'choices' => [
                        'chooseType' => null,
                        'quota' => 'Q',
						'percent' => 'P'
                    ],
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.carneo_amount_type',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                    'choice_label' => function ($choiceValue, $key, $value) {
                        return 'form.elements.amount_types.'.$key;
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
            ->add('description', TextareaType::class,
                [
                    'constraints' => [],
                    "label" => "form.elements.description",
                    "attr" => [
                        "placeholder" => "form.elements.descriptionPlaceholder"
                    ],
                    "required" => false,
                    'translation_domain' => 'discount',
            ])
            ->add('comment', TextareaType::class,
                [
                    'constraints' => [],
                    "label" => "form.elements.comment",
                    "attr" => [
                        "placeholder" => "form.elements.commentPlaceholder"
                    ],
                    "required" => false,
                    'translation_domain' => 'discount',
                ])
			->add('dealer', EntityType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'required' => true,
					'multiple' => false,
                    'label' => 'form.elements.dealer',
                    'placeholder' => 'form.elements.dealerPlaceholder',
                    'attr' => [
                        'novalidate' => 'novalidate',
						'class' => 'select2'
					],
                    'class' => Dealer::class,
                    'query_builder' => function (EntityRepository $er) use ($builder) {
                        $queryBuilder = $er->createQueryBuilder('q');
                        $query        = $queryBuilder
                            ->select('d')
                            ->from('App\Entity\Dealer', 'd');
                        $query->orderBy('d.name', 'ASC');
                        return $query;
                    },
                    'translation_domain' => 'discount',
                    'choice_label' => 'name',
                    'choice_value' => function (Dealer $entity = null) {
                        return $entity ? $entity->getId() : '';
                    },
            ])
			->add('groups', ChoiceType::class,
                [
                    'choices' => [
                        'chooseType' => null,
                        'private' => 'P',
						'firm' => 'F',
						'disabled' => 'D'
                    ],
					'required' => true,
                    'multiple' => true,
                    'attr' => [
                        'novalidate' => 'novalidate',
						'class' => 'select2-multiple'
					],
                    'label' => 'form.elements.groups.group',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                    'choice_label' => function ($choiceValue, $key, $value) {
                        return 'form.elements.groups.'.$key;
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
            ->add('active', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.active',
                    'translation_domain' => 'discount',
                    'attr' => [
                        'class' => 'custom-control-input'
                    ]
            ])
			->add('active_from', DateType::class, [
				'widget' => 'single_text',
				'html5' => false,
				'attr' => ['class' => 'js-datepicker'],
				'label' => 'form.elements.activeFrom',
                'translation_domain' => 'discount',
			])
			->add('active_to', DateType::class, [
				'widget' => 'single_text',
				'html5' => false,
				'attr' => ['class' => 'js-datepicker'],
				'label' => 'form.elements.activeTo',
                'translation_domain' => 'discount',
			])
            ->add('deliveryTime', TextType::class,
                [
                    "label" => "form.elements.deliveryTime",
                    "attr" => [
                        "placeholder" => "form.elements.deliveryTimePlaceholder"
                    ],
                    "required" => false,
                    'translation_domain' => 'discount',
                ])
            ->add('submit', SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3',
                    ],
                    'label' => 'list.buttons.submit',
					'translation_domain' => 'menu'
            ]);
			
			$builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
    }

    /**
     * @param FormEvent $event
     */
	function onPreSetData(FormEvent $event) 
	{
        $data = $event->getData();
        $form = $event->getForm();
		
		$levelOptions = $form->get('level')->getConfig()->getOptions();
		$marksOptions = $form->get('mark')->getConfig()->getOptions();
		$modelsOptions = $form->get('model')->getConfig()->getOptions();
		$versionsOptions = $form->get('version')->getConfig()->getOptions();
		$bodyOptions = $form->get('body')->getConfig()->getOptions();

        $disabledMark = true;
        $disabledModel = true;
        $disabledVersion = true;
        $disabledBody = true;
		
		//exclusions
        if($data->getId())
		{
			if($data->getDealer())
			{								
				$exclusions = $this->em->getRepository(Discount::class)->findBy([
				    'dealer' => $data->getDealer(),
                    'main' => 0,
                    'type' => 'R'

                ]);
				$form->add('exclusions', EntityType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'required' => false,
					'multiple' => true,
					'disabled' => false,
                    'label' => 'form.elements.exclusion',
                    'attr' => [
                        'novalidate' => 'novalidate',
						'class' => 'select2-multiple'
					],
                    'class' => Discount::class,
                    'translation_domain' => 'discount',
					'choices' => $exclusions,
					'choice_label' => function (Discount $discount) {
						$amountType = ($discount->getAmountType() == 'Q' ? " EUR" : "%");
						return $discount->getTypeCategory($discount->getType()).' '.$discount->getValue().$amountType;
					},
					'choice_value' => function (Discount $entity = null) {
						return $entity ? $entity->getId() : '';
					}					
				]);
            }



            /**
             * @var Discount $data
             */

            $levelOptions['disabled'] = true;

            $marksOptions['choices'] = [$data->getMark() => $data->getMark()];
            $modelsOptions['choices'] = [$data->getModel() => $data->getModel()];
            $bodyOptions['choices'] = [$data->getBody() => $data->getBody()];
            $versionsOptions['choices'] = [$data->getVersion() => $data->getVersion()];

            $marksOptions['data'] = $data->getMark();
            if($data->getModel()) {
                $modelsOptions['data'] = [$data->getModel()];
            }
            if($data->getBody()) {
                $bodyOptions['data'] = [$data->getBody()];
            }
            if($data->getVersion()) {
                $versionsOptions['data'] = [$data->getVersion()];
            }

            $mark = $data->getMark();
            $model = $data->getModel();
            $selectedOptions = $this->discountRepository->getChildrenByLevel($data);

			switch ($data->getLevel()) {
                case 'MARK':
                    $disabledMark = false;
                    $markChoices = [];
                    $markVehicles = $this->configuratorMarkRepository->getAllMarks();

                    foreach($markVehicles as $vehicle){
                        $markChoices[$vehicle['name']] = $vehicle['name'];
                        if($vehicle['name'] == $mark) {
                            $marksOptions['data'] = $vehicle['name'];
                        }
                    }
                    $marksOptions['choices'] = $markChoices;
                    $marksOptions['choice_value'] = function($value) {
                        if (empty($value)) {
                            return null;
                        }
                        return $value;
                    };
                    break;
                case 'MODEL':
                    $disabledModel = false;
                    $modelChoices = [];
                    $modelVehicles = $this->configuratorModelRepository->getFilteredModels(array('model_slug','model_name'),array('mark' => $mark),array(),'model_slug')->fetchAll();

                    foreach($modelVehicles as $vehicle){
                        $modelChoices[$vehicle['model_name']] = $vehicle['model_slug'];
                        if(in_array($vehicle['model_slug'], $selectedOptions)) {
                            $modelsOptions['data'][] = $vehicle['model_slug'];
                        }
                    }
                    $modelsOptions['choices'] = $modelChoices;
                    $modelsOptions['choice_value'] = function($value) {
                        if (empty($value)) {
                            return null;
                        }
                        return $value;
                    };

                    break;
                case 'BODY':
                    $disabledBody = false;
                    $bodyChoices = [];
                    $cabines = $this->configuratorModelRepository->getFilteredModels(array('cabine'),array('model_slug' => $model),array(),'cabine')->fetchAll();

                    $bodyChoices['chooseType'] = null;
                    foreach($cabines as $cabine){
                        $bodyChoices[$this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['cabine'], $cabine['cabine'])] = $cabine['cabine'];
                        if(in_array($cabine['cabine'], $selectedOptions)) {
                            $bodyOptions['data'][] = $cabine['cabine'];
                        }
                    }
                    $bodyOptions['choices'] = $bodyChoices;
                    $bodyOptions['choice_value'] = function($value) {
                        if (empty($value)) {
                            return null;
                        }
                        return $value;
                    };
                    break;
                case 'VERSION':
                    $disabledVersion = false;
                    $versionChoices = [];
                    $versions = $this->configuratorModelRepository->getFilteredModels(array('version','vehicle_id'),array('model_slug' => $model))->fetchAll();

                    $versionChoices['chooseType'] = null;
                    foreach($versions as $version){
                        $versionChoices[$version['version']] = $version['vehicle_id'];
                        if(in_array($version['vehicle_id'], $selectedOptions)) {
                            $versionsOptions['data'][] = $version['vehicle_id'];
                        }
                    }
                    $versionsOptions['choices'] = $versionChoices;
                    $versionsOptions['choice_value'] = function($value) {
                        if (empty($value)) {
                            return null;
                        }
                        return $value;
                    };

                    break;
            }
		} else {
			$disabledMark = true;
			$disabledModel = true;
			$disabledVersion = true;
			$disabledBody = true;
			
			$form->add('exclusions', EntityType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'required' => false,
					'multiple' => true,
					'disabled' => true,
                    'label' => 'form.elements.exclusion',
                    'attr' => [
                        'novalidate' => 'novalidate',
						'class' => 'select2-multiple'
					],
                    'class' => Discount::class,
                    'translation_domain' => 'discount',
					'choice_label' => null
            ]);
		}
		
		$marksOptions['disabled'] = $disabledMark;
		$modelsOptions['disabled'] = $disabledModel;
		$versionsOptions['disabled'] = $disabledVersion;
		$bodyOptions['disabled'] = $disabledBody;
		$form->add('level', ChoiceType::class, $levelOptions);
		$form->add('mark', ChoiceType::class, $marksOptions);
		$form->add('model', ChoiceType::class, $modelsOptions);
		$form->add('version', ChoiceType::class, $versionsOptions);
		$form->add('body', ChoiceType::class, $bodyOptions);
    }
	
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [],
            'user' => null,
            'data_class' => Discount::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ]);
    }
}