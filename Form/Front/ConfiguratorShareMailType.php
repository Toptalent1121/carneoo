<?php

namespace App\Form\Front;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfiguratorShareMailType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email_to', EmailType::class, [
                'label' => 'form.elements.email_to',
                'required' => true,
                'mapped' => true,
                'translation_domain' => 'account',
            ])
            ->add('email_from', EmailType::class, [
                'label' => 'form.elements.email_from',
                'required' => true,
                'mapped' => true,
                'translation_domain' => 'account',
            ])
            ->add('your_name', TextType::class, [
                'label' => 'form.elements.your_name',
                'required' => true,
                'mapped' => true,
                'translation_domain' => 'account',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'form.elements.message',
                'required' => true,
                'mapped' => true,
                'translation_domain' => 'account',
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase',
                ],
                'label' => 'form.elements.send',
                'translation_domain' => 'account'
            ])
        ;
    }
}