<?php

namespace App\Form\Front;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Validator\Constraints\Unique;
use Symfony\Component\Translation\TranslatorInterface;
use App\Entity\User;

class UserRegistrationType extends AbstractType
{
	
	protected $translator;

    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator   = $translator;
    }
	
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.elements.email',
                'required' => true,
                'translation_domain' => 'account',
				'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 3,
                        'max' => 64,
                    ]),
                    new Email(),
                    new Unique([
                        'entity' => 'App\Entity\User',
                        'field' => 'email',
                        'message' => $this->translator->trans('form.elements.validation.notUniqueValue', [], 'admin'),
                    ]),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'mapped' => true,
                'translation_domain' => 'account',
                'first_options'  => ['label' => 'form.elements.password'],
                'second_options' => ['label' => 'form.elements.password_repeat'],
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase margin-top-2',
					'value' => 'register',
                ],
                'label' => 'form.elements.register',
                'translation_domain' => 'account'
            ])
        ;
    }
	
	public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [],
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ]);
    }
}