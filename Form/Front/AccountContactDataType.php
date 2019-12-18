<?php

namespace App\Form\Front;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AccountContactDataType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => false,
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
                'choices' => [
                    'form.values.type.1' => 1,
                    'form.values.type.2' => 2,
                ],
                'expanded' => true,
                'attr' => ['class' => 'form-radio-buttons-group'],
            ])
            ->add('adress_additional', TextType::class, [
                'label' => 'form.elements.adress_additional',
                'required' => false,
                'mapped' => false,
                'translation_domain' => 'account',
            ])
            ->add('street', TextType::class, [
                'label' => 'form.elements.street',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
            ])
            ->add('zipcode', TextType::class, [
                'label' => 'form.elements.zipcode',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
            ])
            ->add('city', TextType::class, [
                'label' => 'form.elements.city',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
            ])
            ->add('land', ChoiceType::class, [
                'label' => 'form.elements.land',
                'placeholder' => 'form.placeholders.choose',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
                'choices' => [
                    'test1' => 1,
                    'test2' => 2,
                    'test3' => 3,
                    'test4' => 4,
                    'test5' => 5,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase',
                ],
                'label' => 'form.elements.save',
                'translation_domain' => 'account',
            ])
        ;
    }
}