<?php

namespace App\Controller;

use App\Entity\CarBestseller;
use App\Entity\Discount;
use App\Entity\Page;
use App\Entity\Stock;
use App\Repository\CarBestsellerRepository;
use App\Repository\ConfiguratorModelRepository;
use App\Repository\DiscountRepository;
use App\Repository\TemporaryListRepository;
use App\Repository\StockRepository;
use Symfony\Component\Finder\Finder;

class IndexController extends HelperController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function index()
    {
        $doctrine = $this->getDoctrine();
        $pageRepository = $doctrine->getRepository('App:Page');
        $widgetRepository = $doctrine->getRepository('App:Widget');
        $homePage = $pageRepository->getHomePage();
        $widgets = $widgetRepository->getPageActiveWidgets($homePage);

        return $this->render('page/index.html.twig', [
            'page' => $homePage,
            'widgets' => $widgets,
        ]);
    }

    /**
     * @param $slug
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function page($slug)
    {
        $doctrine = $this->getDoctrine();
        $pageRepository = $doctrine->getRepository('App:Page');
        $widgetRepository = $doctrine->getRepository('App:Widget');
        /**
         * @var Page $page
         */
        $page = $pageRepository->findBySlug($slug);
        if(!$page) {
            return $this->redirectToRoute('app');
        }

        $widgets = $widgetRepository->getPageActiveWidgets($page);


        return $this->render('page/index.html.twig', [
            'page' => $page,
            'widgets' => $widgets,
        ]);
    }

    /**
     * @return string
     */
    public function mainMenu()
    {
        $pageRepository = $this->getDoctrine()->getRepository('App:Page');
        /**
         * @var Page $page
         */
        $pages = $pageRepository->getPages();

        return $this->render('_macro/main-menu.html.twig', [
            'pages' => $pages,
        ]);
    }

    /**
     * @param CarBestsellerRepository $carBestsellerRepository
     * @param ConfiguratorModelRepository $configuratorModelRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function carBestseller(CarBestsellerRepository $carBestsellerRepository, ConfiguratorModelRepository $configuratorModelRepository, DiscountRepository $discountRepository)
    {
        $carBestsellers = $carBestsellerRepository->getAllActive();
        $data = $this->prepareCarBestsellerData($carBestsellers, $configuratorModelRepository, $discountRepository);

        return $this->render('_macro/car-bestseller.html.twig', [
            'data' => $data,
        ]);
    }

    /**
     * @param StockRepository $stockRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stockCars(StockRepository $stockRepository)
    {
        $stockCars = $stockRepository->getStockCarsForSlider();
        $data = $this->prepareStockCarsData($stockCars);

        return $this->render('_macro/stock-cars.html.twig', [
            'data' => $data,
        ]);
    }

    /**
     * @param TemporaryListRepository $tListRepository
     * @param ConfiguratorModelRepository $configuratorModelRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function maxDiscounts(TemporaryListRepository $tListRepository, ConfiguratorModelRepository $configuratorModelRepository)
    {
        $discounts = $tListRepository->getDiscountsForSlider();
        $data = $this->prepareDiscountsData($discounts, $configuratorModelRepository);

        return $this->render('_macro/discount.html.twig', [
            'data' => $data,
        ]);
    }

    /**
     * @param TemporaryListRepository $tListRepository
     * @param ConfiguratorModelRepository $configuratorModelRepository
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function cheapestModels(TemporaryListRepository $tListRepository, ConfiguratorModelRepository $configuratorModelRepository)
    {
        $discounts = $tListRepository->getCheapestForSlider();
		
        $data = $this->prepareDiscountsData($discounts, $configuratorModelRepository);

        return $this->render('_macro/cheapest-models.twig', [
            'data' => $data,
        ]);
    }

    /**
     * @param $carBestsellers
     * @param ConfiguratorModelRepository $configuratorModelRepository
     *
     * @return array
     *
     * @throws \Exception
     */
    private function prepareCarBestsellerData($carBestsellers, ConfiguratorModelRepository $configuratorModelRepository, DiscountRepository $discountRepository): array
    {
        $data = [];

        if($carBestsellers) {
            foreach ($carBestsellers as $carBestseller) {
                /**
                 * @var CarBestseller $carBestseller
                 */
                $dateToday = new \DateTime();
                if($carBestseller->getVersion()) {
                    $criteria = ['model_slug' => $carBestseller->getModel(),'vehicle_id'=>$carBestseller->getVersion()];
                } else {
                    $criteria = ['model_slug' => $carBestseller->getModel()];
                }

				$discount = $discountRepository->findDiscountRange(array('mark' => $carBestseller->getMark(), 'model' => $carBestseller->getModel()),'MAX',array('amount_type' => 'P', 'main' => true));
				$carneooPrice = $carBestseller->getMinPrice() - $carBestseller->getMinPrice()*($discount['value']-$discount['provision'])/100;
                $car = $configuratorModelRepository->getFilteredModels(array('model_slug','doors','cabine','year','jato_vehicle_id'),$criteria,array(),'cabine')->fetch();
				$image = $configuratorModelRepository->getImageByModelAndBody($car['model_slug'],$car['cabine']);
				
                if(
                    ($carBestseller->getActiveFrom() != null && $carBestseller->getActiveFrom() <= $dateToday && $carBestseller->getActiveTo() != null && $carBestseller->getActiveTo() >= $dateToday) ||
                    ($carBestseller->getActiveFrom() != null && $carBestseller->getActiveFrom() <= $dateToday) ||
                    ($carBestseller->getActiveTo() != null && $carBestseller->getActiveTo() >= $dateToday) ||
                    ($carBestseller->getActiveFrom() == null && $carBestseller->getActiveTo() == null)
                ) {
                    if($carBestseller->getActiveFrom()) {
                        $validFrom = $carBestseller->getActiveFrom();
                    } else {
                        $validFrom = $dateToday;
                    }
                    if($carBestseller->getActiveTo()) {
                        $validTo = $carBestseller->getActiveTo();
                    } else {
                        $validTo = new \DateTime();
                        $validTo->modify('+ 8 days');
                    }
                    
                    $data[] = [
                        'id' => $carBestseller->getId(),
                        'alt' => $carBestseller->getMark() . ' ' . $carBestseller->getModel(),
                        'src' => '/uploads/cars/'.$image.'.png',
                        'name' => $carBestseller->getMark() . ' ' . $carBestseller->getModel(),
                        'carneoo_price' => $carneooPrice,
						'carneoo_discount' => $discount['value']-$discount['provision'],
                        'valid_from' => $validFrom->format('d.m.Y'),
                        'valid_to' => $validTo->format('d.m.Y'),
                        'href' => $this->generateUrl('configurator_car_detail', [
                            'mark' => $carBestseller->getMark(),
                            'model_slug' => $carBestseller->getModel(),
                            'cabine' => $car['cabine'],
                        ]),
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * @param $stockCars
     *
     * @return array
     */
    private function prepareStockCarsData($stockCars): array
    {
        $data = [];

        if($stockCars) {
            foreach ($stockCars as $stockCar) {
                /**
                 * @var Stock $stockCar
                 */

                $image = '/uploads/cars/' . $stockCar->getImage() . '.png';
				
				$carneooPrice = $stockCar->getPrice() - $stockCar->getPrice()*$stockCar->getDiscount()/100;

                $data[] = [
                    'id' => $stockCar->getId(),
                    'alt' => $stockCar->getName(),
                    'src' => $image,
                    'name' => $stockCar->getName(),
                    'carneoo_price' => $carneooPrice,
					'carneoo_discount' => $stockCar->getDiscount(),
                    'valid_from' => $stockCar->getValidDate()->format('d.m.Y'),
                    'valid_to' => $stockCar->getValidDate()->format('d.m.Y'),
                    'href' => $this->generateUrl('stock_details', ['id' => $stockCar->getId()]),
                ];
            }
        }

        return $data;
    }

    /**
     * @param $discounts
     * @param ConfiguratorModelRepository $configuratorModelRepository
     *
     * @return array
     *
     * @throws \Exception
     */
    private function prepareDiscountsData($discounts, ConfiguratorModelRepository $configuratorModelRepository): array
    {
        $data = [];

        if($discounts) {
            foreach ($discounts as $discount) {
                /**
                 * @var Discount $discount
                 */
                $dateToday = new \DateTime();
				
                if(
                    ($discount['active_from'] != null && $discount['active_from'] <= $dateToday && $discount['active_to'] != null && $discount['active_to'] >= $dateToday) ||
                    ($discount['active_from'] != null && $discount['active_from'] <= $dateToday) ||
                    ($discount['active_to'] != null && $discount['active_to'] >= $dateToday) ||
                    ($discount['active_from'] == null && $discount['active_to'] == null)
                ) {
                    if($discount['active_from']) {
                        $validFrom = $discount['active_from'];
                    } else {
                        $validFrom = $dateToday;
                    }
                    if($discount['active_to']) {
                        $validTo = $discount['active_to'];
                    } else {
                        $validTo = new \DateTime();
                        $validTo->modify('+ 8 days');
                    }

                    $data[] = [
                        'id' => $discount['id'],
                        'alt' => $discount['mark'] . ' ' . $discount['model'],
                        'src' => '/uploads/cars/'.$discount['image'].'.png',
                        'name' => $discount['mark'] . ' ' . $discount['model'],
                        'carneoo_price' => $discount['sprice'],
                        'carneoo_discount' => $discount['discount_max'],
                        'valid_from' => $validFrom->format('d.m.Y'),
                        'valid_to' => $validTo->format('d.m.Y'),
                        'href' => $this->generateUrl('max_discount_details', ['id' => $discount['id']]),
                    ];
                }
            }
        }

        return $data;
    }
}
