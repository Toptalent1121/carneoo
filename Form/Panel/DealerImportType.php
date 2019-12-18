<?php

namespace App\Form\Panel;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class DealerImportType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'form.elements.file',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'dealer',
                'attr' => [
                    'placeholder' => 'form.elements.filenamePlaceholder',
                ],
            ])
            ->add('submit', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3',
                    ],
                    'label' => 'list.buttons.submit',
                    'translation_domain' => 'menu'
            ])
        ;
    }
}
