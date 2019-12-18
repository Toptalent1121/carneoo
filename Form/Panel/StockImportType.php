<?php

namespace App\Form\Panel;

use App\Entity\Dealer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use App\Repository\ConfiguratorMarkRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class StockImportType extends AbstractType
{
	/**
     * @var ConfiguratorMarkRepository $configuratorMarkRepository
     */
    private $configuratorMarkRepository;
	
	public function __construct(ConfiguratorMarkRepository $configuratorMarkRepository)
    {
		$this->configuratorMarkRepository = $configuratorMarkRepository;
    }
	
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
			->add('mark', ChoiceType::class, [
                'multiple' => false,
                'expanded' => false,
                'label' => 'form.elements.levels.marks',
                'translation_domain' => 'discount',
                'choice_translation_domain' => 'discount',
            ])
			->add('dealer', EntityType::class, [
				'class' => Dealer::class,
				'query_builder' => function (EntityRepository $er) {
					return $er->createQueryBuilder('d')
					->where('d.stock = 1')
					->orderBy('d.name', 'ASC');
				},
				'choice_label' => 'name',
				'label' => 'list.title',
                'required' => true,
                'translation_domain' => 'dealer'
			])
            ->add('file', FileType::class, [
                'label' => 'form.elements.file',
                'required' => true,
                'mapped' => false,
                'translation_domain' => 'dealer'
            ])
            ->add('submit', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3',
                    ],
                    'label' => 'list.buttons.submit',
                    'translation_domain' => 'menu'
            ]);
			
		$builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
    }
	
	/**
     * @param FormEvent $event
     */
	function onPreSetData(FormEvent $event) 
	{
        $form = $event->getForm();
		
		$markVehicles = $this->configuratorMarkRepository->getAllMarks();

        foreach($markVehicles as $vehicle){
            $markChoices[$vehicle['name']] = $vehicle['name'];
        }
        $marksOptions['choices'] = $markChoices;
        $marksOptions['choice_value'] = function($value) {
			if (empty($value)) {
				return null;
			}
            return $value;
        };
		
		$form->add('mark', ChoiceType::class, $marksOptions);
	}
}
