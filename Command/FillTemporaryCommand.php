<?php

namespace App\Command;

use App\Entity\Discount;
use App\Entity\TemporaryList;
use App\Repository\ConfiguratorModelRepository;
use App\Repository\DiscountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FillTemporaryCommand
 *
 * @package App\Command
 */
class FillTemporaryCommand extends Command
{
    /**
     * @var string $defaultName
     */
    protected static $defaultName = 'app:fill-temporary';

    /**
     * @var EntityManagerInterface $entityManager
     */
    private $entityManager;

    /**
     * @var ConfiguratorModelRepository $configuratorModelRepository
     */
    private $configuratorModelRepository;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * FillTemporaryCommand constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ConfiguratorModelRepository $configuratorModelRepository
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $em, ConfiguratorModelRepository $configuratorModelRepository, DiscountRepository $discountRepository,ContainerInterface $container)
    {
        $this->em = $em;
        $this->configuratorModelRepository = $configuratorModelRepository;
		$this->discountRepository = $discountRepository;
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Fills temporary table with financial data');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->em->getConnection();
		$platform   = $connection->getDatabasePlatform();
		$connection->executeUpdate($platform->getTruncateTableSQL('temporary_list', true));
		
		$vehicles = $this->configuratorModelRepository->getFilteredModels(array('mark','model_slug','cabine','jato_vehicle_id','price'));
		
		$n=1;
		foreach($vehicles as $vehicle)
		{
			$discountMin = $this->getDiscount(array('version' => $vehicle['jato_vehicle_id'],'mark' => $vehicle['mark'],'model' => $vehicle['model_slug'],'body' => $vehicle['cabine']),'MIN',array('amount_type' => 'P','main' => true));
			$image = $this->configuratorModelRepository->getImageByModelAndBody($vehicle['model_slug'],$vehicle['cabine']);
			
			if($discountMin['value'] != null){
				$list = new TemporaryList();
				$list->setMark($vehicle['mark']);
				$list->setModel($vehicle['model_slug']);
				$list->setBody($vehicle['cabine']);
				$list->setVersion($vehicle['jato_vehicle_id']);
				$list->setPrice($vehicle['price']);
				
				$discountMax = $this->getDiscount(array('version' => $vehicle['jato_vehicle_id'],'mark' => $vehicle['mark'],'model' => $vehicle['model_slug'],'body' => $vehicle['cabine']),'MAX',array('amount_type' => 'P','main' => true));
				
				$list->setDiscountMin($discountMin['value']);
				$list->setDiscountMax($discountMax['value']);
				
				if($discountMax['active_from'] != null){
					$list->setActiveFrom($discountMax['active_from']);
				}
				
				if($discountMax['active_to'] != null){
					$list->setActiveTo($discountMax['active_to']);
				}
				
				if($image != null){
					$list->setImage($image);
				}
				
				$this->em->persist($list);
				$this->em->flush();
				$n++;
			}
		}

        $output->writeln('Imported ' . $n . ' vehicles');
    }
	
	private function getDiscount($vehicles, $scale, $filter = null, $group = null, $one = true, $groupBy = null)
	{	
		$discount = $this->discountRepository->findDiscountRange($vehicles, $scale, $filter, $group, $one, $groupBy);
		
		if($discount == false && isset($vehicles['version'])){		
			
			unset($vehicles['version']);
			$discount = $this->discountRepository->findDiscountRange($vehicles, $scale, $filter, $group, $one, $groupBy);
		}
		if($discount == false && isset($vehicles['body'])){
				
			unset($vehicles['body']);
			$discount = $this->discountRepository->findDiscountRange($vehicles, $scale, $filter, $group, $one, $groupBy);			
		}
		if($discount == false && isset($vehicles['model'])){
				
			unset($vehicles['model']);
			$discount = $this->discountRepository->findDiscountRange($vehicles, $scale, $filter, $group, $one, $groupBy);				
		}	
		
		return $discount;			
	}
}