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

class UserRegisterType extends AbstractType
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
            ->add('gender', ChoiceType::class, [
                'label' => 'form.elements.salutation',
                'placeholder' => 'form.placeholders.choose',
                'required' => true,
                'translation_domain' => 'account',
                'choices' => [
                    'form.values.salutation.1' => 'Frau',
                    'form.values.salutation.2' => 'Herr',
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'form.elements.name',
                'required' => true,
                'translation_domain' => 'account',
            ])
            ->add('lastname', TextType::class, [
                'label' => 'form.elements.surname',
                'required' => true,
                'translation_domain' => 'account',
            ])
            ->add('company', TextType::class, [
                'label' => 'form.elements.company',
                'required' => false,
                'translation_domain' => 'account',
            ])
            ->add('street', TextType::class, [
                'label' => 'form.elements.street',
                'required' => false,
                'translation_domain' => 'account',
            ])
            ->add('zip', TextType::class, [
                'label' => 'form.elements.zipcode',
                'required' => false,
                'translation_domain' => 'account',
            ])
            ->add('city', TextType::class, [
                'label' => 'form.elements.city',
                'required' => false,
                'translation_domain' => 'account',
            ])
            ->add('phone', TextType::class, [
                'label' => 'form.elements.phone',
                'required' => true,
                'translation_domain' => 'account',
            ])
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
            ->add('call_back', ChoiceType::class, [
                'label' => 'form.elements.call_back',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
                'choices' => [
                    'form.values.call_back.1' => 1,
                    'form.values.call_back.2' => 2,
					'form.values.call_back.3' => 3,
                ]
            ])
            ->add('financing', ChoiceType::class, [
                'label' => 'form.elements.financing',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
                'choices' => [
                    'form.values.financing.1' => 1,
                    'form.values.financing.2' => 2,
					'form.values.financing.3' => 3,
					'form.values.financing.4' => 4,
                ]
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'form.elements.notes',
                'required' => false,
                'mapped' => false,
                'translation_domain' => 'account',
                'attr' => [
                    'rows' => 6,
                ],
            ])
			->add('finance_time', TextType::class, [
                'label' => 'form.elements.finance_time',
				'mapped' => false,
                'required' => false,
                'translation_domain' => 'account',
            ])
			->add('finance_deposit', TextType::class, [
                'label' => 'form.elements.finance_deposit',
				'mapped' => false,
                'required' => false,
                'translation_domain' => 'account',
            ])
			->add('finance_rate', ChoiceType::class, [
                'label' => 'form.elements.finance_rate',
				'mapped' => false,
                'required' => true,
                'translation_domain' => 'account',
				'choices' => [
                    'form.values.finance_rate.1' => 0,
                    'form.values.finance_rate.2' => 1,
                ]
            ])
			->add('finance_rate_value', TextType::class, [
                'label' => 'form.elements.finance_rate_value',
				'mapped' => false,
                'required' => false,
                'translation_domain' => 'account',
            ])
			->add('leasing_mileage', TextType::class, [
                'label' => 'form.elements.leasing_mileage',
				'mapped' => false,
                'required' => false,
                'translation_domain' => 'account',
            ])
			->add('leasing_time', TextType::class, [
                'label' => 'form.elements.leasing_time',
				'mapped' => false,
                'required' => false,
                'translation_domain' => 'account',
            ])
			->add('leasing_payment', TextType::class, [
                'label' => 'form.elements.leasing_payment',
				'mapped' => false,
                'required' => false,
                'translation_domain' => 'account',
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase',
					'value' => 'register',
					'form' => 'form_register'
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