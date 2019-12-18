<?php

namespace App\Form\Panel;

use App\Entity\Dealer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Validator\Constraints\Unique;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DealerType extends AbstractType
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
        $builder
            ->add('name', TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length([
                            'min' => 3,
                            'max' => 255,
                            ])
                    ],
                    "label" => "form.elements.name",
                    "attr" => [
                        "placeholder" => "form.elements.namePlaceholder"
                    ],
                    "required" => false,
                    'translation_domain' => 'dealer',
            ])
            ->add('phone', TextType::class,
                [
                    "label" => "form.elements.phone",
                    "attr" => [
                        "placeholder" => "form.elements.phonePlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'dealer',
                    'empty_data' => '',
            ])
            ->add('fax', TextType::class,
                [
                    'constraints' => [
                        new Length([
                            'min' => 3,
                            'max' => 255,
                            ]),
                    ],
                    "label" => "form.elements.fax",
                    "attr" => [
                        "placeholder" => "form.elements.faxPlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'dealer',
            ])
            ->add('zip', TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                    ],
                    "label" => "form.elements.zip",
                    "attr" => [
                        "placeholder" => "form.elements.zipPlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'dealer',
            ])
            ->add('city', TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length([
                            'min' => 3,
                            'max' => 255,
                            ]),
                    ],
                    "label" => "form.elements.city",
                    "attr" => [
                        "placeholder" => "form.elements.cityPlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'dealer',
            ])
            ->add('address', TextType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length([
                            'min' => 3,
                            'max' => 255,
                            ]),
                    ],
                    "label" => "form.elements.address",
                    "attr" => [
                        "placeholder" => "form.elements.addressPlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'dealer',
            ])
			->add('front_description', TextareaType::class,
                [
                    "label" => "form.elements.description",
                    "required" => false,
                    'translation_domain' => 'dealer',
            ])
			->add('carneo_provision', NumberType::class,
                [
                    "label" => "form.elements.provision",
                    "required" => false,
                    'translation_domain' => 'dealer',
            ])
			->add('stock', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.stock',
                    'translation_domain' => 'dealer',
                    'attr' => [
                        'class' => 'custom-control-input'
                    ]
            ])
            ->add('active', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.active',
                    'translation_domain' => 'dealer',
                    'attr' => [
                        'class' => 'custom-control-input'
                    ]
            ])
            ->add('submit', SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3',
                    ],
                    'label' => 'list.buttons.submit',
					'translation_domain' => 'menu'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [],
            'user' => null,
            'data_class' => Dealer::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
			'allow_extra_fields' => true
        ]);
    }
}