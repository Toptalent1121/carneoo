<?php

namespace App\Controller;

use App\Form\Front\AccountContactDataType;
use App\Form\Front\AccountPasswordType;
use App\Form\Front\AccountPersonalDataType;
use App\Form\Front\JustDriveContactType;
use App\Form\Front\NewsletterRegisterType;
use App\Form\Front\AccountThanksPasswordType;
use App\Form\Front\PriceInquiryType;
use App\Form\Front\UserLoginType;
use App\Form\Front\UserPasswordReminderType;
use App\Form\Front\UserPasswordResetType;
use App\Form\Front\UserRegisterType;
use App\Form\Front\UserRegistrationType;
use App\Service\Notifications;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use App\Entity\Offer;
use App\Entity\Discount;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Security;
use App\Repository\ConfiguratorModelRepository;
use Doctrine\ORM\EntityManagerInterface;


/**
 * Class UserController
 *
 * @package App\Controller
 */
class UserController extends HelperController
{
    /**
     * @var UserPasswordEncoderInterface
     */
	protected $encoder;

    /**
     * UserController constructor.
     *
     * @param Security $security
     * @param ConfiguratorModelRepository $confModelEm
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $encoder
     */
	public function __construct(Security $security, ConfiguratorModelRepository $confModelEm, EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
		$this->confModelEm = $confModelEm;
		$this->encoder = $encoder;
		$this->user = $security->getUser();
		$this->em = $em;
		
		$offers = $this->em->getRepository(Offer::class)
			->findBy(['user' => $this->user]);
		
		$this->number = count($offers);
		
		$myVehicles = array();
		
		foreach($offers as $offer)
		{
			$vehicle = $this->confModelEm->getFilteredModels(array('mark','model_name'), array('jato_vehicle_id' => $offer->getVersion()),array(),null,'DE',true)->fetch();			
			$myVehicles[$offer->getId()] = $offer->getStock() == null ? $vehicle['mark'].' '.$vehicle['model_name'] : $offer->getStock()->getMark().' '.$offer->getStock()->getName();
		}
		
		$this->myVehicles = $myVehicles;
    }
	
	public function login(AuthenticationUtils $authenticationUtils)
    {

		if ($this->container->get('security.authorization_checker')->isGranted('ROLE_REGISTER_USER')) {
			return new RedirectResponse($this->generateUrl('account_dashboard'));
		}

		$error = $authenticationUtils->getLastAuthenticationError();

		// last username entered by the user
		$lastEmail = $authenticationUtils->getLastUsername();

		return $this->render('account/login.html.twig',[
            'last_email' => $lastEmail,
            'error' => $error,
		]);		
    }

    /**
     * @param AuthenticationUtils $authenticationUtils
     * @param Request $request
     * @param Notifications $notifications
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function registration(AuthenticationUtils $authenticationUtils, Request $request, Notifications $notifications)
    {
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_REGISTER_USER')) {
            return new RedirectResponse($this->generateUrl('account_dashboard'));
        }

        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(UserRegistrationType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $request->request->all();
            /**
             * @var User $user
             */
            $user = $form->getData();

            $user->setPassword($this->encoder->encodePassword($user, $formData['user_registration']['password']['first']));
            $user->setRoles(['ROLE_REGISTER_USER']);
            $user->setActive(true);

            $entityManager->persist($user);
            $entityManager->flush();

            $notifications->send(Notifications::TYPE_REGISTRATION_CONFIRM, $user->getEmail(), [
                'token' => sha1($user->getEmail()),
            ]);

            return $this->redirectToRoute('account_registration_send');
        }

        return $this->render('account/registration.html.twig',[
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registrationSend()
    {
        return $this->render('account/password_send.html.twig');
    }

    /**
     * @param string $token
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function registrationConfirm(string $token)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->findByToken($token);

        if($user) {
            /**
             * @var User $user
             */
            $user->setActive(true);

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('account/password_confirm.html.twig');
    }
	
	/**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboard()
    {		
				
		return $this->render('account/dashboard.html.twig',[
			'number' => $this->number,
			'my_vehicles' => $this->myVehicles
		]);
    }
	
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function password()
    {
        $form = $this->createForm(AccountPasswordType::class, null);

        return $this->render('account/password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Notifications $notifications
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function passwordReminder(Request $request, Notifications $notifications)
    {
        $form = $this->createForm(UserPasswordReminderType::class, null);
        $error = '';

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findByEmail($formData['email']);

            if($user) {
                /**
                 * @var User $user
                 */
                $notifications->send(Notifications::TYPE_FORGOT_PASSWORD, $user->getEmail(), [
                    'token' => sha1($user->getEmail()),
                ]);

                return $this->redirectToRoute('account_password_send_message');
            } else {
                $error = 'errors.password_reset.no_email';
            }
        }

        return $this->render('account/password_reminder.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function passwordResetSend()
    {
        return $this->render('account/password_send.html.twig');
    }

    /**
     * @param Request $request
     * @param string $token
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function passwordReset(Request $request, string $token, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(UserPasswordResetType::class, null);
        $user = $entityManager->getRepository(User::class)->findByToken($token);

        if(false == $user) {
            return $this->redirectToRoute('cms_page', ['slug' => 'home']);
        }

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            /**
             * @var User $user
             */
            $formData = $form->getData();
            $newPassword = $userPasswordEncoder->encodePassword($user, $formData['password']);

            $user->setPassword($newPassword);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('account_password_changed');
        }

        return $this->render('account/password_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function passwordChanged()
    {
        return $this->render('account/password_changed.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contact()
    {
        $form = $this->createForm(AccountContactDataType::class, null);

        return $this->render('account/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
	 *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function personal(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(AccountPersonalDataType::class, $user, [
            'method' => 'post'
        ]);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $entityManager->persist($entity);
            $entityManager->flush();
        }

        return $this->render('account/personal.html.twig', [
            'form' => $form->createView(),
			'number' => $this->number,
			'my_vehicles' => $this->myVehicles		
        ]);
    }

   /**
     * @param int $offer_id
	 *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function vehiclesDetails($offer_id)
    {
		
		$description = array();
		
		if($offer_id){
			
			$selectedOffer = $this->em->getRepository(Offer::class)->find($offer_id);
			
			if($selectedOffer->getStock() == null){
			
				$description = $this->confModelEm->getFilteredModels(array('power','engine','fuel','cabine','gear','drive','doors','cabine','year'),array('jato_vehicle_id' => $selectedOffer->getVersion()),array('weight','consumption','size_out','size_in','cargo','energy_class','co_emission'))->fetch();
			
				$description['fuel'] = $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['fuel'],$description['fuel']);
				$description['gear'] = $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['gear'],$description['gear']);
				$description['drive'] = $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['drive'],$description['drive']);
				$description['cabine'] = $this->confModelEm->getSchemaDescription($this->confModelEm::$JATO_STANDARD_MAPPER['cabine'],$description['cabine']);
				$description['energy_class'] = substr($description['energy_class'],23);
				$description['consumption'] = $this->confModelEm->getOptionValue($selectedOffer->getVersion(),0,'consumption_average');
				$description['co_emission'] = substr($description['co_emission'],18);
				$description['weight'] = $this->confModelEm->getOptionValue($selectedOffer->getVersion(),0,'weight_1');
				
				$color = $this->confModelEm->getOption($selectedOffer->getVersion(),$selectedOffer->getColor());
				$description['color'] = $color['name'];
				
				$packets = $selectedOffer->getPacket();			
				foreach($packets as $packet)
				{
					$packetData = $this->confModelEm->getOption($selectedOffer->getVersion(),$packet);
					$description['packet'][] = $packetData['name'];
				}
				
				$rim = $this->confModelEm->getOption($selectedOffer->getVersion(),$selectedOffer->getRim());
				$description['outside'][] = $rim['name'];
				
				$exteriors = $selectedOffer->getExterior();
				foreach($exteriors as $exterior)
				{
					$exteriorData = $this->confModelEm->getOption($selectedOffer->getVersion(),$exterior);
					$description['outside'][] = $exteriorData['name'];
				}
			
				$polster = $this->confModelEm->getOption($selectedOffer->getVersion(),$selectedOffer->getPolster());
				$description['inside'][] = $polster['name'];
				
				$interiors = $selectedOffer->getMisc();
				foreach($interiors as $interior)
				{
					$interiorData = $this->confModelEm->getOption($selectedOffer->getVersion(),$interior);
					$description['inside'][] = $interiorData['name'];
				}
			
				$audios = $selectedOffer->getAudio();
				foreach($audios as $audio)
				{
					$audioData = $this->confModelEm->getOption($selectedOffer->getVersion(),$audio);
					$description['inside'][] = $audioData['name'];
				}
			}else{
				
				$consumption = $selectedOffer->getStock()->getConsumption();
				$description['color'] = $selectedOffer->getStock()->getColor();
				$description['fuel'] = $selectedOffer->getStock()->getFuel();
				$description['gear'] = $selectedOffer->getStock()->getGear();
				$description['drive'] = $selectedOffer->getStock()->getDrive();
				$description['energy_class'] = $selectedOffer->getStock()->getEnergyClass();
				$description['consumption'] = $consumption[2];
				$description['weight'] = $selectedOffer->getStock()->getWeight();
				$description['power'] = $selectedOffer->getStock()->getPower();
				$description['capacity'] = $selectedOffer->getStock()->getCapacity();
				$description['doors'] = $selectedOffer->getStock()->getDoors();
				$description['cabine'] = $selectedOffer->getStock()->getBody();
				$description['options'] = $selectedOffer->getStock()->getOptions();
			}
		}		
		
		$offers = $this->em->getRepository(Offer::class)
			->findBy(['user' => $this->user]);
		
		$carList = array();
		
		foreach($offers as $offer)
		{
		
			if($offer->getStock() == null){
				$vehicle = $this->confModelEm->getFilteredModels(array('mark','model_slug','cabine','doors','year','model_name','jato_vehicle_id'),array('jato_vehicle_id' => $offer->getVersion()))->fetch();
				
				$mainDiscount = $this->getDoctrine()->getRepository(Discount::class)->find($offer->getMainDiscount());
				
				$image = $this->confModelEm->getImageByModelAndBody($vehicle['model_slug'],$vehicle['cabine']);
				$alt = $vehicle['model_slug'];
				$mark = $vehicle['mark'];
				$model = $vehicle['model_name'];
				$discount = $mainDiscount->getValue()-$mainDiscount->getCarneoProvision().'%';
			}else{
				
				$image = $offer->getStock()->getImage();
				$alt = $model = $offer->getStock()->getName();;
				$mark = $offer->getStock()->getMark();
				$discount = 'Legerwagen';
			}

			$validTo = clone $offer->getCreatedAt();
			$validTo->modify('+ 8 Days');
			$today = new \DateTime();

			if($validTo < $today) {
                $timeToCount = 0;
            } else {
                $timeToCount = $today->getTimestamp() - $offer->getCreatedAt()->getTimestamp();
            }

			$carList[] = array(
				'id' => $offer->getId(),
				'src' => '/uploads/cars/'.$image.'.png',
				'alt' => $alt,
				'name' => $mark,
				'model' => $model,
				'update' => 'Gemerkt am '.$offer->getCreatedAt()->format('d.m.Y'),
				'carneoo_discount' => $discount,
				'carneoo_price' => str_replace(',00',',-',number_format($offer->getPrice(),2,',','.')),
				'selected' => $offer->getId() == $offer_id ? true : false,
				'time' => $timeToCount,
			);
		}
		
        return $this->render('account/vehicles_details.html.twig',[
			'number' => $this->number,
			'my_vehicles' => $this->myVehicles,
			'car_list' => $carList,
			'description' => $description
		]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function justDriveCarDetails()
    {
        return $this->render('account/just_drive_car_details.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function justDriveContact()
    {
        $form = $this->createForm(JustDriveContactType::class, null);

        return $this->render('account/just_drive_contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function leaveAlert()
    {
        return $this->render('account/leave_alert.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function leaveRegistration()
    {
        $form = $this->createForm(NewsletterRegisterType::class, null);

        return $this->render('account/leave_registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function leaveThanks()
    {
        return $this->render('account/leave_thanks.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dealerSelection()
    {
        return $this->render('account/dealer_selection.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function carList()
    {
        return $this->render('account/car_list.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function carDetails()
    {
        return $this->render('account/car_details.html.twig');
    }
}