<?php

namespace App\Form\Front;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;

class JustDriveContactType extends AbstractType
{
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
            ])
            ->add('callBack', ChoiceType::class, [
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
            ->add('financingRequest', ChoiceType::class, [
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
            ->add('notes', TextareaType::class, [
                'label' => 'form.elements.notes',
                'required' => false,
                'mapped' => false,
                'translation_domain' => 'account',
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase',
					'value' => 'register',
					'form' => 'form_register'
                ],
                'label' => 'form.elements.send_inquiry',
                'translation_domain' => 'account'
            ])
            ->add('back', ButtonType::class, [
                'attr' => [
                    'class' => 'button white font-s font-uppercase',
                ],
                'label' => 'form.elements.back',
                'translation_domain' => 'account'
            ])
        ;
    }
}