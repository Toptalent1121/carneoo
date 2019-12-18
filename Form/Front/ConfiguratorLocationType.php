<?php

namespace App\Form\Front;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfiguratorLocationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('zipcode', TextType::class, [
                'label' => ' ',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
                'attr' => [
                    'placeholder' => 'form.elements.zipcode',
                ]
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase',
                ],
                'label' => 'form.elements.next',
                'translation_domain' => 'account',
            ])
        ;
    }
}