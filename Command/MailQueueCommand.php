<?php

namespace App\Command;

use App\Entity\Dealer;
use App\Entity\Discount;
use App\Entity\MailQueue;
use App\Entity\Page;
use App\Repository\ConfiguratorMarkRepository;
use App\Repository\ConfiguratorModelRepository;
use App\Service\Notifications;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MailQueueCommand
 *
 * @package App\Command
 */
class MailQueueCommand extends Command
{
    /**
     * @var string $defaultName
     */
    protected static $defaultName = 'app:mail-queue';

    /**
     * @var EntityManagerInterface $entityManager
     */
    private $entityManager;

    /**
     * @var ConfiguratorModelRepository $configuratorModelRepository
     */
    private $configuratorModelRepository;

    /**
     * @var ConfiguratorMarkRepository $configuratorMarkRepository
     */
    private $configuratorMarkRepository;

    /**
     * @var Notifications $notifications
     */
    private $notifications;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * MailQueueCommand constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ConfiguratorModelRepository $configuratorModelRepository
     * @param ConfiguratorMarkRepository $configuratorMarkRepository
     * @param Notifications $notifications
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ConfiguratorModelRepository $configuratorModelRepository,
        ConfiguratorMarkRepository $configuratorMarkRepository,
        Notifications $notifications,
        ContainerInterface $container
    )
    {
        $this->entityManager = $entityManager;
        $this->configuratorModelRepository = $configuratorModelRepository;
        $this->configuratorMarkRepository = $configuratorMarkRepository;
        $this->notifications = $notifications;
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Sends all e-mail notifications');
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
        $mailQueueRepository = $this->entityManager->getRepository('App:MailQueue');
        $mailsToSend = $mailQueueRepository->getEmailsToSend();
        $mailsSend = 0;

        if($mailsToSend) {
            foreach ($mailsToSend as $mail) {
                /**
                 * @var MailQueue $mail
                 */
                $offer = $mail->getOffer();
                $user = $offer->getUser();
                $car = $this->configuratorModelRepository->getFilteredModels(array('mark','model_name','model_slug','doors','cabine','year','power','drive','fuel','version','variant_name','seats','gear','engine'),array('jato_vehicle_id'=>$offer->getVersion()),array('weight','consumption','cargo','co_emission','energy_class'))->fetch();
                $mark = $this->configuratorMarkRepository->getMarkUrlByName($car['mark']);
				$image = $this->configuratorModelRepository->getImageByModelAndBody($car['model_slug'],$car['cabine']);
				
                $mainDiscount = $this->entityManager->getRepository(Discount::class)->find($offer->getMainDiscount());
                /**
                 * @var Dealer $dealer
                 */
                $dealer = $mainDiscount->getDealer();

                $notificationData = [
                    'gender' => $user->getGender(),
                    'date' => $offer->getCreatedAt(),
                    'name' => $user->getName(),
                    'surname' => $user->getLastName(),
                    'company' => $user->getCompany(),
                    'zip' => $user->getZip(),
                    'street' => $user->getStreet(),
                    'city' => $user->getCity(),
                    'phone' => $user->getPhone(),
                    'email' => $user->getEmail(),
                    'dealer' => $dealer->getName(),
                    'call_back' => 'form.values.call_back.'.$offer->getCallBack(),
                    'delivery_time' => $mainDiscount->getDeliveryTime(),
                    'price' => number_format($offer->getPrice()/(1-$mainDiscount->getValue()/100),2, ',', '.'),
                    'discount' => $mainDiscount->getValue(),
                    'carneo_price' => number_format($offer->getPrice(),2, ',', '.'),
                    'date_to' => date('d.m.Y', $offer->getCreatedAt()->getTimestamp()+691200),
                    'comment' => $offer->getComment(),
                    'discount_comment' => $mainDiscount->getComment(),
                    'financing' => $offer->getFinancing(),
                    'financing_value' => 'form.values.financing.'.$offer->getFinancing(),
                    'mark_site_url' => $mark['url'],
                    'car' => [
                        'mark' => $car['mark'],
                        'model' => $car['model_name'],
                        'variant' => $car['variant_name'],
                        'version' => $car['version'],
                        'body' => $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['cabine'], $car['cabine'],'DE',true),
                        'image' => $this->container->getParameter('http_host') . '/uploads/cars/'.$image.'.png',
                        'standard_options' => [],
                        'parameters' => [
                            'power' => $car['power'].' PS',
                            'power_kw' => floor(($car['power']*0.74)) .' kW',
                            'engine' => $car['engine'].' l',
                            'doors_seats' => $car['doors'].' / '.$car['seats'],
                            'fuel' => $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['fuel'], $car['fuel']),
                            'gear' => $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['gear'], $car['gear']),
                            'cabine' => $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['cabine'], $car['cabine']),
                            'drive' => $this->configuratorModelRepository->getSchemaDescription($this->configuratorModelRepository::$JATO_STANDARD_MAPPER['drive'], $car['drive']),
                            'year' => $car['year'],
                            'energy_efficiency' => substr($car['energy_class'],23),
                            'consumption' => substr($car['consumption'],20),
                            'co2_emission' => substr($car['co_emission'],18),
                            'weight' => substr($car['weight'],10),
                        ]
                    ]
                ];

                $standardOptions = $this->configuratorModelRepository->getStandardList($offer->getVersion());

                $options = array();
				$soptions = array();
				
                foreach($standardOptions as $standardOption) {
                    if(false == in_array($standardOption['schema_id'], $this->configuratorModelRepository::$STANDARD_OPTIONS_EXCLUSION_SCHEMA_ID)) {
                        $soptions[] = $standardOption['description'];
                    }
                }
                $notificationData['car']['standard_options'] = $soptions;

                $financialOptions = unserialize($offer->getFinancialOptions());

                if($offer->getFinancing() == 2){
                    $notificationData['finance']['finance_time'] = $financialOptions['finance_deposit'];
                    $notificationData['finance']['finance_deposit'] = $financialOptions['finance_deposit'];
                    $notificationData['finance']['finance_rate'] = $financialOptions['finance_rate'];
                    if(isset($financialOptions['finance_rate_value'])) {
                        $notificationData['finance']['finance_rate_value'] = $financialOptions['finance_rate_value'];
                    }
                }elseif($offer->getFinancing() == 3){
                    $notificationData['finance']['leasing_mileage'] = $financialOptions['leasing_mileage'];
                    $notificationData['finance']['leasing_time'] = $financialOptions['leasing_time'];
                    $notificationData['finance']['leasing_payment'] = $financialOptions['leasing_payment'];
                }


                $color = $offer->getColor();
                $extendedColor = $this->configuratorModelRepository->getOptionBuild($offer->getVersion(),$color,100006)->fetch();
                $rule = $this->parseRule($extendedColor['option_rule']);
                if(count($rule) > 0 && $rule[0] > 0)
                    $options[] = $rule[0];
                else
                    $options[] = $color;


                $polster = $offer->getPolster();
                $extendedPolster = $this->configuratorModelRepository->getOptionBuild($offer->getVersion(),$polster,100006)->fetch();
                $rule = $this->parseRule($extendedPolster['option_rule']);
                if(count($rule) > 0 && $rule[0] > 0)
                    $options[] = $rule[0];
                else
                    $options[] = $polster;

                if($offer->getRim() != 1)
                    $options[] = $offer->getRim();

                $options = array_merge($options,$offer->getPacket(),$offer->getExterior(),$offer->getAudio(),$offer->getSafety(),$offer->getMisc());

                $n=0;
                foreach($options as $option)
                {
                    $optionData = $this->configuratorModelRepository->getOption($offer->getVersion(),$option);
                    $notificationData['car']['parameters']['option'][$n]['name'] = $optionData['name'];
                    $notificationData['car']['parameters']['option'][$n]['price'] = number_format($optionData['price'],2, ',', '.');
                    $notificationData['car']['parameters']['option'][$n]['carneo_price'] = number_format($optionData['price']-($mainDiscount->getValue()*$optionData['price']/100),2, ',', '.');
                    $n++;
                }

                $additionalDiscounts = $offer->getAdditionalDiscount();
                $i=0;
                $j=0;
                $additionalSum = 0;
                $costSum = 0;
                $procent = 0;
                foreach($additionalDiscounts as $additionalDiscount)
                {
                    if($additionalDiscount->getType() == 'R'){

                        if($additionalDiscount->getAmountType() == 'Q'){

                            $additionalSum = $additionalSum + $additionalDiscount->getValue();
                            $procent = $procent + ($offer->getPrice()/$additionalSum)*100;

                        }else{

                            $procent = $procent + $additionalDiscount->getValue();
                            $additionalSum = $additionalSum + ($offer->getPrice()*$additionalDiscount->getValue())/100;
                        }

                    }else{

                        $notificationData['additional_cost'][$j]['name'] = $additionalDiscount->getFrontName();
                        $notificationData['additional_cost'][$j]['price'] = number_format($additionalDiscount->getValue(),2, ',', '.');
                        $costSum = $costSum + $additionalDiscount->getValue();
                        $j++;
                    }
                }

                $finalPrice = $offer->getPrice()-$additionalSum;
                $notificationData['final_price'] = number_format($finalPrice,2, ',', '.');
                $notificationData['discount'] = number_format($notificationData['discount']+$procent,2,',','.');

                $notificationData['save'] = number_format(($offer->getPrice()/(1-$mainDiscount->getValue()/100))-$finalPrice ,2, ',', '.');

                $rim = $this->configuratorModelRepository->getOption($offer->getVersion(),$offer->getRim());
                $notificationData['car']['parameters']['outside'][] = $rim['name'];
                $exteriors = $offer->getExterior();
                foreach($exteriors as $exterior)
                {
                    $exteriorData = $this->configuratorModelRepository->getOption($offer->getVersion(),$exterior);
                    $notificationData['car']['parameters']['outside'][] = $exteriorData['name'];
                }

                $polster = $this->configuratorModelRepository->getOption($offer->getVersion(),$offer->getPolster());
                $notificationData['car']['parameters']['inside'][] = $polster['name'];
                $interiors = $offer->getMisc();
                foreach($interiors as $interior)
                {
                    $interiorData = $this->configuratorModelRepository->getOption($offer->getVersion(),$interior);
                    $notificationData['car']['parameters']['inside'][] = $interiorData['name'];
                }

                $audios = $offer->getAudio();
                foreach($audios as $audio)
                {
                    $audioData = $this->configuratorModelRepository->getOption($offer->getVersion(),$audio);
                    $notificationData['car']['parameters']['audio'][] = $audioData['name'];
                }

                $notificationData['price_items'] = $this->getItemList(
                    [
                        'value' => $mainDiscount->getValue(),
                        'provision' => $mainDiscount->getCarneoProvision(),
                    ],
                    [
                        'mark' => strtoupper($car['mark']),
                        'model_slug' => strtoupper($car['model_slug']),
                        'cabine' => strtoupper($car['cabine']),
                        'variant' => strtoupper($car['variant_name']),
                        'version' => $offer->getVersion(),
                        "group" => "P",
                        'color' => $offer->getColor(),
                        'packet' => $offer->getPacket(),
                        'rim' => $offer->getRim(),
                        'polster' => $offer->getPolster(),
                        'exterior' => $offer->getExterior(),
                        'audio' => $offer->getAudio(),
                        'safety' => $offer->getSafety(),
                        'misc' => $offer->getMisc(),
                    ]
                );
				
                try {

                    if($mail->getType() == Notifications::TYPE_ADMIN_FINISH_CONFIGURATION) {
                        $email = 'anfragen@carneoo.de';
                    } else {
                        $email = $user->getEmail();
                    }
                    $this->notifications->send($mail->getType(), $email, $notificationData);

                    $mail->setSendAt(new \DateTime());
                    $this->entityManager->persist($mail);
                    $this->entityManager->flush();

                    $mailsSend++;
                } catch (\Exception $exception) {
                    dump($exception);
                }
            }
        }


        $output->writeln('Send ' . $mailsSend . ' e-mails');
    }

    /**
     * @param $rule
     * 
     * @return array
     */
    private function parseRule($rule)
    {
        $ruleList = array();

        if(strstr($rule,'AND')){
            $ruleArray = explode(' AND ',$rule);

            foreach($ruleArray as $rule2)
            {
                if(!strstr($rule2,'OR'))
                    $ruleList[] = trim($rule2,'{}');
            }
        }elseif(!strstr($rule,'AND') && !strstr($rule,'OR')){
            $ruleList[] = trim($rule,'{}');
        }
        return $ruleList;
    }

    /**
     * @param $discount
     * @param $data
     * @param string $mode
     *
     * @return array|int
     */
    private function getItemList($discount,$data,$mode='list')
    {
        $itemList = array();
        if(!isset($data['variant']))
            return $itemList;

        $sum = 0;
        if($data['version'] && $data['version'] != null)
            $search = array('jato_vehicle_id' => $data['version']);
        else
            $search = array('model_slug' => $data['model_slug'], 'cabine' => $data['cabine'], 'variant' => $data['variant']);

        $variant = $this->configuratorModelRepository->getFilteredModels(array('M_price','variant_name'),$search)->fetch();
        $variant['name'] = $variant['variant_name'];
        if($mode == 'list'){
            $itemList[] = $this->addToItemList($variant,'Variant: ',$discount);
        }else{
            $sum += $variant['price'];
        }

        foreach($data as $key => $value)
        {
            if(is_array($value)){
                foreach($value as $val)
                {
                    $item = $this->configuratorModelRepository->getOption($data['version'],$val);
                    if($item){
                        if($mode == 'list'){
                            $itemList[] = $this->addToItemList($item,'',$discount);
                        }else{
                            $sum += $item['price'];
                        }
                    }
                }
            }else{
                if($key == 'rim' && $value == 1){
                    $itemList[] = array(
                        'key' => 'Felgen: Standard felgen',
                        'value_1' => 0,
                        'value_2' => 0
                    );
                }else{
                    switch($key){
                        case 'color':
                            $name = 'Lackierung: ';
                            break;

                        case 'rim':
                            $name = 'Felgen: ';
                            break;

                        case 'polster':
                            $name = 'Polster: ';
                            break;

                        default:
                            $name = '';
                            break;
                    }

                    $item = $this->configuratorModelRepository->getOption($data['version'],intVal($value));
                    if($item){

                        if($key == 'color' || $key == 'polster'){

                            $extended = $this->configuratorModelRepository->getOptionBuild($data['version'],$value,100006)->fetch();
                            $rule = $this->parseRule($extended['option_rule']);

                            if(count($rule) > 0 && $rule[0] > 0){
                                $object = $this->configuratorModelRepository->getOption($data['version'],$rule[0]);
                                $item['price'] = $object['price'];
                            }
                        }

                        if($mode == 'list'){
                            $itemList[] = $this->addToItemList($item,$name,$discount);
                        }else{
                            $sum += $item['price'];
                        }
                    }
                }
            }
        }
        if($mode == 'list'){
            return $itemList;
        }else{
            return $sum;
        }
    }

    /**
     * @param $item
     * @param $name
     * @param $discount
     *
     * @return array
     */
    private function addToItemList($item,$name,$discount)
    {

        $itemArray = array(
            'key' => $name.($item['name'] == '-' ? 'Standard' : $item['name']),
            'value_1' => $this->calculatePrice($item['price'],$discount),
            'value_2' => $this->calculatePrice($item['price']),
        );

        if(isset($item['option_id']))
            $itemArray['value_3'] = $item['option_id'];

        return $itemArray;
    }

    /**
     * @param $price
     * @param null $discount
     *
     * @return float|int
     */
    private function calculatePrice($price,$discount=null)
    {
        if($discount == null){
            $finalPrice = $price;
        }else{
            $finalPrice = $price-$price*($discount['value']-$discount['provision'])/100;
        }
        return $finalPrice;
    }
}