<?php

namespace App\Form\Panel;

use App\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Validator\Constraints\Unique;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RoleType extends AbstractType
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
                            'max' => 128,
                            ]),
                        new Unique([
                            'entity' => 'App\Entity\Role',
                            'field' => 'name',
                            'message' => $this->translator->trans('form.elements.validation.notUniqueValue',
                                [], 'role'),
                            ]),
                    ],
                    "label" => "form.elements.name",
                    "attr" => [
                        "placeholder" => "form.elements.namePlaceholder"
                    ],
                    "required" => false,
                    'translation_domain' => 'admin',
            ])
            ->add('active', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.active',
                    'translation_domain' => 'role',
                    'attr' => [
                        'class' => 'custom-control-input'
                    ]
            ])
            ->add('permissions', ChoiceType::class,
                [
                    'choices' => $options['permissions'],
                    'mapped' => false,
                    'required' => false,
                    'multiple' => true,
                ])
            ->add('submit', SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3',
                    ],
                    'label' => 'form.elements.submit',
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
            'permissions' => null,
            'user' => null,
            'data_class' => Role::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ]);
    }
}