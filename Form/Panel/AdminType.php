<?php

namespace App\Form\Panel;

use App\Entity\Admin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Validator\Constraints\Unique;
use Symfony\Component\Translation\TranslatorInterface;
use App\Entity\Role;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AdminType extends AbstractType
{
    protected $translator;
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator = null)
    {
        $this->translator   = $translator;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returnes validation groups based on form data. If ID exists Edition Form is active
     * @param Admin $formData
     * @return array Collection of validation groups is returned
     */
    private function getValidationGroups($formData)
    {
        $entityId = $formData->getId();
        if (empty($entityId)) {
            return ['Default', 'addition'];
        }
        return ['Default', 'edition'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class,
                [
                    'constraints' => new Length([
                        'min' => 3,
                        'max' => 64,
                        ]),
                    "label" => "form.elements.name",
                    "attr" => [
                        "placeholder" => "form.elements.namePlaceholder"
                    ],
                    "required" => false,
                    'translation_domain' => 'admin',
            ])
            ->add('last_name', TextType::class,
                [
                    'constraints' => new Length([
                        'min' => 3,
                        'max' => 64,
                        ]),
                    "label" => "form.elements.lastName",
                    "attr" => [
                        "placeholder" => "form.elements.lastNamePlaceholder"
                    ],
                    "required" => false,
                    'translation_domain' => 'admin',
            ])
            ->add('email', EmailType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length([
                            'min' => 3,
                            'max' => 64,
                            ]),
                        new Email(),
                        new Unique([
                            'entity' => 'App\Entity\Admin',
                            'field' => 'email',
                            'message' => $this->translator->trans('form.elements.validation.notUniqueValue',
                                [], 'admin'),
                            ]),
                    ],
                    "required" => false,
                    "label" => "form.elements.email",
                    "attr" => [
                        "placeholder" => "form.elements.emailPlaceholder",
                        "novalidate" => "novalidate",
                    ],
                    "required" => false,
                    'translation_domain' => 'admin',
            ])
            ->add('phone', TextType::class,
                [
                    'constraints' => [
                        new Length([
                            'min' => 9,
                            'max' => 32,
                            ]),
                    ],
                    "label" => "form.elements.phone",
                    "attr" => [
                        "placeholder" => "form.elements.phonePlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'admin',
            ])
            ->add('password', RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'required' => false,
                    'error_bubbling' => false,
                    'invalid_message' => $this->translator->trans('form.elements.validation.passwordRepeatViolation',
                        [
                            '%first%' => $this->translator->trans('form.elements.password',
                                [], 'admin'),
                            '%second%' => $this->translator->trans('form.elements.passwordRepeat',
                                [], 'admin'),
                        ], 'admin'),
                    'first_name' => 'password',
                    'second_name' => 'password_repeat',
                    'first_options' => [
                        'constraints' => [
                            new NotBlank([
                                'groups' => [
                                    'addition'
                                ]
                                ]),
                            new Length([
                                'min' => 6,
                                'max' => 32,
                                ]),
                        ],
                        "label" => "form.elements.password",
                        "attr" => [
                            "placeholder" => "form.elements.passwordPlaceholder",
                            "autocomplete" => "off",
                        ],
                        "required" => false,
                        'translation_domain' => 'admin',
                    ],
                    'second_options' => [
                        'constraints' => [
                            new NotBlank([
                                'groups' => [
                                    'addition'
                                ]
                                ]),
                            new Length([
                                'min' => 6,
                                'max' => 32,
                                ]),
                        ],
                        "label" => "form.elements.passwordRepeat",
                        "attr" => [
                            "placeholder" => "form.elements.passwordRepeatPlaceholder",
                            "autocomplete" => "off",
                        ],
                        "required" => false,
                        'translation_domain' => 'admin',
                    ]
                ]
            )
            ->add('active', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.active',
                    'translation_domain' => 'admin',
                    'attr' => [
                        'class' => 'custom-control-input'
                    ]
            ])
            ->add('role', EntityType::class,
                [
                    'class' => Role::class,
                    'choices' => $options['roles'],
                    'multiple' => true,
                    'expanded' => false,
                    'label' => 'form.elements.roles.roles',
                    'translation_domain' => 'admin',
                    'choice_translation_domain' => 'admin',
                    'choice_label' => function ($role) {
                        return $role->getName();
                    },
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'mapped' => false,
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
            'constraints' => [
            ],
            'user' => null,
            'roles' => null,
            'data_class' => Admin::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                return $this->getValidationGroups($data);
            },
        ]);
    }
}