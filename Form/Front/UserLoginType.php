<?php

namespace App\Form\Front;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class UserLoginType extends AbstractType
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
            ->add('password', PasswordType::class, [
                'label' => 'form.elements.password',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
            ])
            ->add('remain_signed_in', CheckboxType::class, [
                'label' => 'form.elements.remain_signed_in',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'account',
//                'choices' => [
//                    'form.elements.remain_signed_in' => 1,
//                ],
//                'expanded' => true,
                'attr' => ['class' => 'form-radio-buttons-group'],
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase',
					'form' => 'form_login',
					'value' => 'login'
                ],
                'label' => 'form.elements.log_in',
                'translation_domain' => 'account'
            ])
        ;
    }
}