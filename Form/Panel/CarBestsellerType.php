<?php

namespace App\Form\Panel;

use App\Entity\CarBestseller;
use App\Entity\Discount;
use App\Repository\ConfiguratorMarkRepository;
use App\Repository\ConfiguratorModelRepository;
use App\Traits\Panel\JATOMapperTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CarBestsellerType
 *
 * @package App\Form\Panel
 */
class CarBestsellerType extends AbstractType
{
    use JATOMapperTrait;

    /**
     * @var TranslatorInterface|null $translator
     */
    protected $translator;

    /**
     * @var TokenStorageInterface $tokenStorage
     */
    protected $tokenStorage;

    /**
     * CarBestsellerType constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param ConfiguratorModelRepository $configuratorModelRespository
     * @param ConfiguratorMarkRepository $configuratorMarkRespository
     * @param EntityManagerInterface $em
     * @param TranslatorInterface|null $translator
     */
    public function __construct(TokenStorageInterface $tokenStorage, ConfiguratorModelRepository $configuratorModelRespository, ConfiguratorMarkRepository $configuratorMarkRespository, EntityManagerInterface $em, TranslatorInterface $translator = null)
    {
        $this->translator   = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->configuratorModelRespository = $configuratorModelRespository;
        $this->configuratorMarkRespository = $configuratorMarkRespository;
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mark', ChoiceType::class,
                [
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.levels.marks',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                ])
            ->add('model', ChoiceType::class,
                [
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.levels.models',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                ])
            ->add('version', ChoiceType::class,
                [
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.levels.versions',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                ])
            ->add('body', ChoiceType::class,
                [
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'form.elements.levels.body',
                    'translation_domain' => 'discount',
                    'choice_translation_domain' => 'discount',
                ])
            ->add('active', CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'form.elements.active',
                    'translation_domain' => 'discount',
                    'attr' => [
                        'class' => 'custom-control-input'
                    ]
                ])
            ->add('active_from', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'js-datepicker'],
                'label' => 'form.elements.activeFrom',
                'translation_domain' => 'discount',
            ])
            ->add('active_to', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'js-datepicker'],
                'label' => 'form.elements.activeTo',
                'translation_domain' => 'discount',
            ])
            ->add('submit', SubmitType::class,
                [
                    'attr' => [
                        'class' => 'btn btn-sm btn-success default ml-3',
                    ],
                    'label' => 'list.buttons.submit',
                    'translation_domain' => 'menu'
                ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
    }

    /**
     * @param FormEvent $event
     */
    function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $marksOptions = $form->get('mark')->getConfig()->getOptions();
        $modelsOptions = $form->get('model')->getConfig()->getOptions();
        $versionsOptions = $form->get('version')->getConfig()->getOptions();
        $bodiesOptions = $form->get('body')->getConfig()->getOptions();

        $mark = $data->getMark();
        $model = $data->getModel();
        $version = $data->getVersion();

        $disabledMark = false;
        $markDataOptions = $this->getMarks($mark);
        $marksOptions = array_merge($marksOptions, $markDataOptions);

        if($data->getId()) {
            $disabledModel = false;
            $modelDataOptions = $this->getModels($mark, $model);
            $modelsOptions = array_merge($modelsOptions, $modelDataOptions);

            $disabledVersion = false;
            $versionDataOptions = $this->getVersions($model, $version);
            $versionsOptions = array_merge($versionsOptions, $versionDataOptions);

            $disabledBody = false;
            $bodiesDataOptions = $this->getBodies($model, $version);
            $bodiesOptions = array_merge($bodiesOptions, $bodiesDataOptions);
        } else {
            $disabledModel = true;
            $disabledVersion = true;
            $disabledBody = true;
        }

        $marksOptions['disabled'] = $disabledMark;
        $modelsOptions['disabled'] = $disabledModel;
        $versionsOptions['disabled'] = $disabledVersion;
        $bodiesOptions['disabled'] = $disabledBody;
        $form->add('mark', ChoiceType::class, $marksOptions);
        $form->add('model', ChoiceType::class, $modelsOptions);
        $form->add('body', ChoiceType::class, $bodiesOptions);
        $form->add('version', ChoiceType::class, $versionsOptions);
    }

    /**
     * @param null $selected
     * @return mixed
     */
    private function getMarks($selected = null)
    {
        $markVehicles = $this->configuratorMarkRespository->getAllMarks();
        $markChoices = array();
        $markChoices['chooseType'] = null;

        foreach($markVehicles as $vehicle){
            $markChoices[$vehicle['name']] = $vehicle['name'];
            if($vehicle['name'] == $selected) {
                $marksOptions['data'] = $vehicle['name'];
            }
        }
        $marksOptions['choices'] = $markChoices;
        $marksOptions['choice_value'] = function($value) {
            if (empty($value)) {
                return null;
            }
            return $value;
        };

        return $marksOptions;
    }

    /**
     * @param $mark
     * @param $selected
     *
     * @return mixed
     */
    private function getModels($mark, $selected)
    {
        $modelChoices = array();
        $modelChoices['chooseType'] = null;

        $modelVehicles = $this->configuratorModelRespository->getFilteredModels(array('model_slug','model_name'),array('mark' => $mark),array(),'model_slug')->fetchAll();

        foreach($modelVehicles as $vehicle){
            $modelChoices[$vehicle['model_name']] = $vehicle['model_slug'];
            if($vehicle['model_slug'] == $selected) {
                $modelsOptions['data'] = $vehicle['model_slug'];
            }
        }
        $modelsOptions['choices'] = $modelChoices;
        $modelsOptions['choice_value'] = function($value) {
            if (empty($value)) {
                return null;
            }
            return $value;
        };

        return $modelsOptions;
    }

    /**
     * @param $model
     * @param $selected
     *
     * @return mixed
     */
    private function getVersions($model, $selected)
    {
        $versionChoices = array();
        $versionChoices['chooseType'] = null;
        $versions = $this->configuratorModelRespository->getFilteredModels(array('version','vehicle_id'),array('model_slug' => $model))->fetchAll();

        foreach($versions as $version){
            $versionChoices[$version['version']] = $version['vehicle_id'];
            if($version['vehicle_id'] == $selected) {
                $versionsOptions['data'] = $version['vehicle_id'];
            }
        }
        $versionsOptions['choices'] = $versionChoices;
        $versionsOptions['choice_value'] = function($value) {
            if (empty($value)) {
                return null;
            }
            return $value;
        };

        return $versionsOptions;
    }

    /**
     * @param $model
     * @param $selected
     *
     * @return mixed
     */
    private function getBodies($model, $selected)
    {
        $cabines = $this->configuratorModelRespository->getFilteredModels(array('cabine'),array('model_slug' => $model),array(),'cabine')->fetchAll();
        $bodyChoices = array();
        $bodyChoices['chooseType'] = null;
        foreach($cabines as $cabine){
            $bodyChoices[$this->configuratorModelRespository->getSchemaDescription(JATOMapperTrait::$JATO_STANDARD_MAPPER['cabine'], $cabine['cabine'])] = $cabine['cabine'];
            if($cabine['cabine'] == $selected) {
                $bodyOptions['data'] = $cabine['cabine'];
            }
        }
        $bodyOptions['choices'] = $bodyChoices;
        $bodyOptions['choice_value'] = function($value) {
            if (empty($value)) {
                return null;
            }
            return $value;
        };

        return $bodyOptions;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CarBestseller::class,
        ]);
    }
}
