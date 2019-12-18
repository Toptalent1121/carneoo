<?php

namespace App\Form\Front;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class AccountPasswordType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.elements.email',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
            ])
            ->add('old_password', PasswordType::class, [
                'label' => 'form.elements.old_password',
                'required' => true,
                'mapped' => false,
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
}