<?php

namespace App\Form\Front;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountPersonalDataType extends AbstractType
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
            ->add('lastName', TextType::class, [
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
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase',
                ],
                'label' => 'form.elements.save',
                'translation_domain' => 'account'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}