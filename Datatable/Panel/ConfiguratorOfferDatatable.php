<?php

namespace App\Datatable\Panel;

use App\Datatable\Panel\DatatableAbstract;
use App\Entity\Offer;
use App\Entity\User;
use App\Entity\Discount;
use App\Repository\ConfiguratorModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ConfiguratorOfferDatatable extends DatatableAbstract
{
    protected $entityName = 'App\Entity\Offer';

    /**
     * ConfiguratorOfferDatatable constructor.
     *
     * @param RequestStack $request
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param UrlGeneratorInterface $router
     * @param ConfiguratorModelRepository $configuratorModelRespository
     */
    public function __construct(RequestStack $request, EntityManagerInterface $entityManager, TranslatorInterface $translator, UrlGeneratorInterface $router, ConfiguratorModelRepository $configuratorModelRespository, \Twig_Environment $templating)
    {
        $this->em         = $entityManager;
        $this->request    = $request;
        $this->translator = $translator;
        $this->templating = $templating;
        $this->router     = $router;
        $this->configuratorModelRespository = $configuratorModelRespository;
        $this->columnsRendererDefinition = $this->setColumnsRendererDefinition();
    }

    /**
     * Defines what should be rendered in columns row. Key is the name of the column defined in JS
     * If no column definition has been set - default DB value will be returned when row rendering
     * If name of the template has been set - this tamplate will be rendered as the result
     * if function has been defined - callback will be called
     *
     * @return array
     */
    protected function setColumnsRendererDefinition()
    {
        $columns = [
			'version' => function($entity, $value) {
				if($value == null){
					return 'Legerwagen';
				}else{
					$vehicle = $this->configuratorModelRespository->getJatoVersion($value)->fetch();
					return $vehicle[$this->configuratorModelRespository::$JATO_VERSION_MAPPER['version']];
				}
			},
			'dealer' => function($entity, $value) {
				if($entity->getStock() != null){
					$stock = $entity->getStock();
					return $stock->getDealer()->getName();
				}else{
					$mainDiscount = $this->em->getRepository(Discount::class)->find($entity->getMainDiscount());
					return $mainDiscount->getDealer()->getName();
				}
			},
			'main_discount' => function($entity, $value) {
                if($value == null){
					return '-';
				}else{
					$mainDiscount = $this->em->getRepository(Discount::class)->find($entity->getMainDiscount());				
					return $mainDiscount->getValue().'%';
				}
            },
			'user' => function($entity, $value) {
                /**
                 * @var Offer $entity
                 */
                $user = $entity->getUser();

                if($user) {
                    return $user->getName().' '.$user->getLastName();
                } else {
                    return $value;
                }
			},
			'phone' => function($entity, $value) {
                /**
                 * @var Offer $entity
                 */
                $user = $entity->getUser();

                if($user) {
                    return $user->getPhone();
                } else {
                    return $value;
                }
			},
            'email' => function($entity, $value) {
                /**
                 * @var Offer $entity
                 */
                $user = $entity->getUser();

                if($user) {
                    return $user->getEmail();
                } else {
                    return $value;
                }
            },
            'created_at' => function($entity, $value) {
                /**
                 * @var Offer $entity
                 */
                $createdAt = $entity->getCreatedAt();

                if($createdAt) {
                    return $createdAt->format('Y-m-d H:i:s');
                } else {
                    return '';
                }

            },
            'valid_to' => function($entity, $value) {
                /**
                 * @var Offer $entity
                 */
                $validTo = $entity->getValidTo();

                if($validTo) {
                    return $validTo->format('Y-m-d H:i:s');
                } else {
                    return '';
                }
            },
            '_actions' => 'panel/configurator/offer/datatable/actions_column.html.twig',
		];
        return $columns;
    }
}