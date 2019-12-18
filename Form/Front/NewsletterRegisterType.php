<?php

namespace App\Form\Front;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;

class NewsletterRegisterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            ->add('email', EmailType::class, [
                'label' => 'form.elements.email',
                'required' => true,
                'translation_domain' => 'account',
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'submit font-s font-uppercase',
					'value' => 'register',
					'form' => 'form_register'
                ],
                'label' => 'form.elements.newsletter_sign_in',
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