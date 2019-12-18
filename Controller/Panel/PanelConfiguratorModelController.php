<?php

namespace App\Controller\Panel;

use App\Datatable\Panel\ConfiguratorModelDatatable;
use App\Repository\ConfiguratorModelRepository;
use App\Controller\Panel\PanelAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Discount;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use InvalidArgumentException;
use Doctrine\ORM\NoResultException;

class PanelConfiguratorModelController extends PanelAbstractController
{
    protected $validator;
    protected $translator;
    protected $errors          = [];
    protected $discountFormKey = 'discount';
    protected $model;

    public function __construct(AuthorizationCheckerInterface $authChecker, ValidatorInterface $validator, TranslatorInterface $translator)
    {
        $this->authChecker = $authChecker;
        $this->validator   = $validator;
        $this->translator  = $translator;
    }

    public function index(Request $request)
    {
        $discount = new Discount();
        $dealers  = $this->getDoctrine()->getManager()->getRepository('App\Entity\Dealer')->findBy([
            'active' => true
            ], [
            'city' => 'ASC'
        ]);

        return $this->render('panel/configurator/model/index.html.twig', [
                'discount' => $discount,
                'hidden' => true,
                'dealers' => $dealers,
        ]);
    }

    /**
     * Handles displaying models
     * @param Request $request An instance of Request
     * @param ConfiguratorModelDatatable $datatable Datatable service for models
     * @return JsonResponse Json Resonse is returned
     */
    public function list(Request $request, ConfiguratorModelDatatable $datatable)
    {
        $data = $datatable->getData();
        return new JsonResponse($data, 200);
    }

    /**
     * Handles generation of Model View section
     * @param Request $request An instance of Request
     * @param ConfiguratorModelRepository $configuratorModelRespository An instance of ConfiguratorModelRepository
     * @return JsonResponse Json Resonse is returned
     */
    public function view(Request $request, ConfiguratorModelRepository $configuratorModelRespository)
    {
        $flashbag    = $this->get('session')->getFlashBag();
        $em          = $this->getDoctrine()->getManager();
        $this->model = $configuratorModelRespository->getModel($request->get('id'))->fetchAll();

        if (empty($this->model[0])) {
            $flashbag->add("danger", $this->translator->trans('form.messages.modelNotFound', [], 'model'));
            return $this->redirectToRoute('panel_configurator_model_index');
        }

        if ($request->isMethod('post')) {
            $validation = $this->checkData($request->request->all());
            if (!$validation) {
                return new JsonResponse([
                    'success' => false,
                    'errors' => $this->errors,
                    'message' => $this->translator->trans('form.messages.submittionFailed', [], 'model'),
                    ], 200);
            }
            try {
                $em->getConnection()->beginTransaction();
                $this->processData($request->request->all());
                $em->getConnection()->commit();
                return new JsonResponse([
                    'success' => true,
                    'listView' => $this->generateDiscountsListView($request->get('id')),
                    'formView' => $this->generateDiscountsFormView(),
                    'message' => $this->translator->trans('form.messages.submittionSuccessed', [], 'model'),
                    ], 200);
            } catch (Exception $e) {
                $em->getConnection()->rollBack();
                return new JsonResponse([
                    'success' => true,
                    'message' => $this->translator->trans('form.messages.submittionFailed', [], 'model').' '.$e->getMessage(),
                    ], 200);
            }
        }

        return $this->render('panel/configurator/model/view.html.twig', $this->getFormRenderData());
    }

    public function multiple(Request $request)
    {
        if (!$request->isMethod('post')) {
            die('access deined');
        }

        $validation = $this->checkData($request->request->all());
        if (!$validation) {
            return new JsonResponse([
                'success' => false,
                'errors' => $this->errors,
                'message' => $this->translator->trans('form.messages.submittionFailed', [], 'model'),
                ], 200);
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $data = $request->request->all();
            $ids  = explode(',', $data['selectedIds']);

            if (empty($ids)) {
                return new JsonResponse([
                    'success' => false,
                    'errors' => $this->errors,
                    'message' => $this->translator->trans('form.messages.generalError', [], 'model'),
                    ], 200);
            }

            $em->getConnection()->beginTransaction();

            foreach($ids as $modelId) {
                $data['model_id'] = $modelId;
                $this->processData($data);
            }
            $em->getConnection()->commit();
            return new JsonResponse([
                'success' => true,
                'message' => $this->translator->trans('form.messages.submittionSuccessed', [], 'model'),
                ], 200);
        } catch (Exception $e) {
            $em->getConnection()->rollBack();
            return new JsonResponse([
                'success' => true,
                'message' => $this->translator->trans('form.messages.submittionFailed', [], 'model').' '.$e->getMessage(),
                ], 200);
        }
    }

    /**
     * Handles Pages deactivation Request. XHR only
     * @param Discount $discount An Instance of Discount
     * @return JsonResponse Json Resonse is returned
     */
    public function activateDiscount(Discount $discount = null)
    {
        if (empty($discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('form.messages.deactivationFailed', [], 'model').': '.$this->translator->trans('form.messages.discountNotFound', [], 'model'),
                ], 200);
        }

        return $this->changeDiscountStatus($discount, true);
    }

    /**
     * Handles Pages deactivation Request. XHR only
     * @param Discount $discount An Instance of Discount
     * @return JsonResponse Json Resonse is returned
     */
    public function deactivateDiscount(Discount $discount = null)
    {
        if (empty($discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('form.messages.deactivationFailed', [], 'model').': '.$this->translator->trans('form.messages.discountNotFound', [], 'model'),
                ], 200);
        }

        return $this->changeDiscountStatus($discount, false);
    }

    /**
     * Executes model discount status change
     * @param Discount $discount An instance of Discount
     * @param bool $statusType Status type
     * @return JsonResponse JSON response is returned for XHR Response
     */
    private function changeDiscountStatus(Discount $discount, bool $statusType)
    {
        $failedMessage  = $statusType ? 'activationFailed' : 'deactivationFailed';
        $successMessage = $statusType ? 'activationSuccess' : 'deactivationSuccess';

//        if (!$this->isGranted('discount_update', $discount)) {
//            return new JsonResponse([
//                'success' => false,
//                'message' => $this->translator->trans('form.messages.'.$failedMessage, [], 'model').': '.$this->translator->trans('form.messages.noPermission', [], 'model'),
//                ], 200);
//        }

        $em = $this->getDoctrine()->getManager();
        $discount->setActive($statusType);
        $em->persist($discount);
        $em->flush();

        $listView = $this->generateDiscountsListView($discount->getVehicleId());

        return new JsonResponse([
            'success' => true,
            'listView' => $listView,
            'message' => $this->translator->trans('form.messages.'.$successMessage, [], 'model'),
            ], 200);
    }

    /**
     * Handles Discount deletion Request. XHR only
     * @param Request $request An instance of Request
     * @param Discount $discount An Instance od Discount
     * @return JsonResponse Json Resonse is returned
     */
    public function deleteDiscount(Request $request, Discount $discount = null)
    {
        if (empty($discount)) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('form.messages.deleteFailed', [], 'discount').': '.$this->translator->trans('form.messages.discountNotFound', [], 'discount'),
                ], 200);
        }

//        if (!$this->isGranted('discount_delete', $discount)) {
//            return new JsonResponse([
//                'success' => false,
//                'message' => $this->translator->trans('form.messages.deleteFailed', [], 'discount').': '.$this->translator->trans('form.messages.noPermission', [], 'discount'),
//                ], 200);
//        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($discount);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'listView' => $this->generateDiscountsListView($discount->getVehicleId()),
            'message' => $this->translator->trans('form.messages.deleteSuccess', [], 'model'),
            ], 200);
    }

    private function processData($data)
    {
        $a  = 1;
        $em = $this->getDoctrine()->getManager();

        foreach ($data as $key => $value) {
            $alteredKey = $this->discountFormKey.'_'.$a;
            if (!array_key_exists($alteredKey, $data)) {
                $a++;
                continue;
            }

            $formData = $this->reCastData($data[$this->discountFormKey.'_'.$a]);
            $dealers  = $formData['dealers'];

            foreach ($dealers as $dealerId) {
                $discount = new Discount();
                $dealer   = $this->getDealerById($dealerId);

                $discount->setType($discount->getTypeCategory(strtoupper($formData['type'])))
                    ->setLevel(strtoupper($data['level']))
                    ->setAmountType(strtoupper($formData['amount_type']))
                    ->setDescription($formData['description'])
                    ->setValue($formData['value'])
                    ->setVehicleId($data['model_id'])
                    ->setCreatedBy($this->getUser())
                    ->setActive($formData['active'])
                    ->setDealer($dealer);

                $em->persist($discount);
            }
            $em->flush();
            $a++;
        }
    }

    /**
     * Checkes if dealer with given ID exists in database
     * @param mixed $id ID ot the dealer to be checked if exists
     * @return Dealer An instance of Dealer is returned
     * @throws InvalidArgumentException When no id has been provided
     * @throws NoResultException When no dealer found
     */
    private function getDealerById($id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Empty ID for Dealers to check is exists');
        }

        $dealerRepo = $this->getDoctrine()->getManager()->getRepository('App\Entity\Dealer');
        $dealer     = $dealerRepo->findOneBy([
            'id' => $id,
        ]);

        if (empty($dealer)) {
            throw new NoResultException('Dealer with given ID: '.$id.' does not exists');
        }

        return $dealer;
    }

    /**
     * Collection of data used in form view render process
     * @return array Collection of data is returned
     */
    private function getFormRenderData()
    {
        $discount  = new Discount();
        $discounts = $this->getDoctrine()->getManager()->getRepository('App\Entity\Discount')->findBy([
            'vehicle_id' => $this->model[0]['id_101'],
            'level' => 'VERSION',
        ]);
        $dealers   = $this->getDoctrine()->getManager()->getRepository('App\Entity\Dealer')->findBy([
            'active' => true
            ], [
            'city' => 'ASC'
        ]);

        return [
            'model' => $this->model[0],
            'discount' => $discount,
            'dealers' => $dealers,
            'discounts' => $discounts,
        ];
    }

    /**
     * Dynamic validation rule only for single form row
     * @return Collection Collection of validation rules is returned
     * @throws \Exception An exception is thrown in case of errors
     */
    private function getValidationRules()
    {
        $rules = [
            'dealers' => [
                new NotBlank([
                    'message' => $this->translator->trans('form.validation.valueEmpty', array(), 'model')
                    ]),
            ],
            'type' => [
                new NotBlank([
                    'message' => $this->translator->trans('form.validation.valueEmpty', array(), 'model')
                    ]),
                new Length([
                    'max' => 1,
                    'maxMessage' => $this->translator->trans('form.validation.characterLimieExceeded', array(), 'model')
                    ]),
            ],
            'amount_type' => [
                new NotBlank([
                    'message' => $this->translator->trans('form.validation.valueEmpty', array(), 'model')
                    ]),
                new Length([
                    'max' => 1,
                    'maxMessage' => $this->translator->trans('form.validation.characterLimieExceeded', array(), 'model')
                    ]),
            ],
            'value' => [
                new NotBlank([
                    'message' => $this->translator->trans('form.validation.valueEmpty', array(), 'model')
                    ]),
                new Type([
                    'type' => 'float',
                    'message' => $this->translator->trans('form.validation.valueShouldBeFloat', array(), 'model')
                    ]),
            ],
            'description' => [],
            'active' => [],
        ];

        $collectionConstraint = new Collection($rules);

        return $collectionConstraint;
    }

    /**
     * Validates input data based on provided validation rules
     * @param Collection $validationRules Collection of validation rules
     * @param type $data Collection of dat to be validated
     * @return boolean true/false
     */
    private function validateData(Collection $validationRules, $data, $index)
    {

        $errorList = $this->validator->validate($data, $validationRules);
        if (count($errorList) === 0) {
            return true;
        }

        foreach ($errorList as $error) {
            $field = substr($error->getPropertyPath(), 1, -1);
            if (isset($this->errors[$field])) {
                continue;
            }
            $this->errors[$field.'_'.$index] = $error->getMessage();
        }
        return false;
    }

    /**
     * Chunks form request data for verification
     * @param array $data colelction of Request Form Data
     * @return boolean true is returned if dats OK, false otherwise
     */
    private function checkData(array $data)
    {
        $validation = true;
        $a          = 1;

        foreach ($data as $key => $value) {
            $alteredKey = $this->discountFormKey.'_'.$a;
            if (!array_key_exists($alteredKey, $data)) {
                $a++;
                continue;
            }

            $formData = $this->reCastData($data[$this->discountFormKey.'_'.$a]);
            if (!$this->validateData($this->getValidationRules(), $formData, $a)) {
                $validation = false;
            }
            $a++;
        }

        return $validation;
    }

    /**
     * This method performs correction on Form Data to meet expectations of the data type
     * @param array $data Colelction of Form Data
     * @return array reCast data is returned
     */
    private function reCastData($data)
    {
        $results = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'value':
                    if (!is_numeric($value)) {
                        $results[$key] = $value;
                        continue;
                    }
                    $results[$key] = (float) $value;
                    break;
                case 'active':
                    $results[$key] = (boolean) $value;
                    break;
                default:
                    $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Renders list view for discounts
     * @return HTML is returned
     */
    private function generateDiscountsListView($vehicleId)
    {
        $discounts = $this->getDoctrine()->getManager()->getRepository('App\Entity\Discount')->findBy([
            'vehicle_id' => $vehicleId,
            'level' => 'VERSION',
        ]);

        return $this->renderView('panel/configurator/model/_partials/discountList.html.twig', [
                'discounts' => $discounts,
        ]);
    }

    /**
     * Renders form view for discounts
     * @return HTML is returned
     */
    private function generateDiscountsFormView()
    {
        $discount = new Discount();
        $dealers  = $this->getDoctrine()->getManager()->getRepository('App\Entity\Dealer')->findBy([
            'active' => true
            ], [
            'city' => 'ASC'
        ]);

        return $this->renderView('panel/configurator/model/_partials/discountForm.html.twig', [
                'dealers' => $dealers,
                'discount' => $discount,
                'model' => $this->model[0],
        ]);
    }
}