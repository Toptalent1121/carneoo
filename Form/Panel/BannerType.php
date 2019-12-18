<?php

namespace App\Form\Panel;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use App\Entity\Widget;
use App\Entity\Banner;
use App\Validator\Constraints\Unique;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormInterface;

class BannerType extends AbstractType
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
     * @param Banner $formData
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
        $user     = $this->tokenStorage->getToken()->getUser();
        $formData = $builder->getData();

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
                            'entity' => 'App\Entity\Banner',
                            'field' => 'name',
                            'message' => $this->translator->trans('form.elements.validation.notUniqueValue',
                                [], 'banner'),
                            ]),
                    ],
                    "label" => "form.elements.name",
                    "attr" => [
                        "placeholder" => "form.elements.namePlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'banner',
            ])
            ->add('filename', FileType::class,
                [
                    'constraints' => [
                        new NotBlank([
                            'groups' => [
                                'addition'
                            ]
                            ]),
                    ],
                    'data_class' => null,
                    'required' => false,
                    "label" => "form.elements.filename",
                    "attr" => [
                        "placeholder" => "form.elements.filenamePlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'banner',
                    'mapped' => false,
            ])
            ->add('alt', TextType::class,
                [
                    'constraints' => [
                        new Length([
                            'min' => 3,
                            'max' => 128,
                            ]),
                    ],
                    "label" => "form.elements.alt",
                    "attr" => [
                        "placeholder" => "form.elements.altPlaceholder",
                    ],
                    "required" => false,
                    'translation_domain' => 'banner',
            ])
            ->add('base64', HiddenType::class,
                [
                    "attr" => [
                        "placeholder" => "form.elements.altPlaceholder",
                        "id" => 'base64',
                    ],
                    "required" => false,
                    "mapped" => false,
            ])
            ->add('active', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.active',
                    'translation_domain' => 'banner',
                    'attr' => [
                        'class' => 'custom-control-input',
                    ]
            ])
            ->add('submit', SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3 js-submit-banner-form',
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
            'data_class' => Banner::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                return $this->getValidationGroups($data);
            },
        ]);
    }
}