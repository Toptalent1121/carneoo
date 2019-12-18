<?php

namespace App\Controller\Panel;

use App\Entity\Dealer;
use App\Entity\Discount;
use App\Form\Panel\DealerImportType;
use App\Repository\ConfiguratorMarkRepository;
use App\Repository\ConfiguratorModelRepository;
use App\Traits\Panel\JATOMapperTrait;
use App\Controller\Panel\PanelConfiguratorHelperController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PanelDealerImportController extends PanelConfiguratorHelperController
{
    use JATOMapperTrait;

    /**
     * @param Request $request
     * @param ConfiguratorModelRepository $configuratorModelRepository
     * @param ConfiguratorMarkRepository $configuratorMarkRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function import(Request $request, ConfiguratorModelRepository $configuratorModelRepository, ConfiguratorMarkRepository $configuratorMarkRepository)
    {
        ini_set('max_execution_time', 0);
        $form = $this->createForm(DealerImportType::class, null);
        $form->handleRequest($request);

        if ($request->isMethod('POST') && $form->isSubmitted() && $form->isValid()) {
            try {
                $fileData = $request->files->get('dealer_import');
                /**
                 * @var UploadedFile $file
                 */
                $file = $fileData['file'];

                /* Create temporary direction */
                $this->createTemporaryFolderIfNotExists();

                $fileName = 'uploaded_sheet_file_' . rand(100, 200) . '.' . $file->guessExtension();
                $filePath = $this->getTemporaryFolderPath();

                $file->move($filePath, $fileName);


                $sheetData = $this->readXLXSFile($filePath . $fileName);
                $mappedDealersData = $this->mapRowData($sheetData, $configuratorModelRepository, $configuratorMarkRepository);

                $this->saveDealers($mappedDealersData);

                /* Delete uploaded file*/
                $this->deleteFile($filePath . $fileName);

                $this->redirectToRoute('panel_configurator_dealer_import');
            } catch (\Exception $exception) {
                dump($exception);exit;
            }
        }

        return $this->render('panel/configurator/dealer/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param array $rowsData
     * @param ConfiguratorModelRepository $configuratorModelRepository
     * @param ConfiguratorMarkRepository $configuratorMarkRepository
     *
     * @return array
     */
    private function mapRowData(array $rowsData, ConfiguratorModelRepository $configuratorModelRepository, ConfiguratorMarkRepository $configuratorMarkRepository)
    {
        $rows = [];
        $groups = ['P', 'F', 'D'];

        foreach ($rowsData as $key => $rowData) {
            if($key > 0 && $rowData[0] != null && $rowData[83] != 'x') {
                $hash = $this->generateHash($rowData[83], $rowData[84]);

                if(false == key_exists($hash, $rows)) {
                    $rows[$hash]['name'] = $rowData[83];
                    $rows[$hash]['person'] = [$rowData[84]];
                    $rows[$hash]['email'] = [$rowData[85]];
                    $rows[$hash]['phone'] = $rowData[86] ? $rowData[86] : '';
                    $rows[$hash]['fax'] = $rowData[87];
                    $rows[$hash]['zipcode'] = intval($rowData[88]);
                    $rows[$hash]['city'] = $rowData[89];
                    $rows[$hash]['address'] = $rowData[90];
                    $rows[$hash]['discounts'] = [];
                }

                $modelIds = $configuratorModelRepository->findModelVehiclesByName(trim($rowData[1]));

                if($modelIds) {
                    foreach ($groups as $group) {
                        $rows[$hash]['discounts'] = $this->mapDiscountData(
                            [$rowData[1], $rowData[9], $rowData[10], $rowData[77], $rowData[74], $rowData[75], $rowData[76], $rowData[78], $rowData[80]],
                            $rows[$hash]['discounts'],
                            $group,
                            $modelIds['vehicle_id'],
                            $configuratorMarkRepository
                        );
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @param array $data
     * @param array $discounts
     * @param string $group
     * @param string $modelId
     * @param ConfiguratorMarkRepository $configuratorMarkRepository
     *
     * @return array
     */
    private function mapDiscountData(array $data, array $discounts, string $group, string $modelId, ConfiguratorMarkRepository $configuratorMarkRepository)
    {
        $mark = $configuratorMarkRepository->getMarkFromModel($modelId);
        $modelHash = sha1($mark['mark'] . $modelId . $group);
        $value = str_replace('%', '', $data[1]);
        $carneoProvision = str_replace('%', '', $data[2]);

        if(isset($discounts[$modelHash])) {
            if($discounts[$modelHash]['value'] < $value) {
                $discounts[$modelHash]['value'] = $value;
            }
        } else {
            $discounts[$modelHash] = [
                'vehicleModel' => $data[0],
                'type' => 'R',
                'name' => 'Nachlass Einkauf',
                'front_name' => 'Nachlass Einkauf',
                'amount_type' => 'P',
                'value' => $value,
                'carneo_provision' => $carneoProvision,
                'level' => 'MODEL',
                'carneo_amount_type' => 'P',
                'mark' => $mark['mark'],
                'model' => $modelId,
                'active' => true,
                'main' => true,
                'obligatory' => false,
                'groups' => $group,
                'delivery_time' => $data[3],
                'cost_type' => 'C',
                'cost_amount_type' => 'Q',
                'cost_carneo_amount_type' => 'Q',
                'cost_price' => $data[4],
                'cost_name' => $data[5],
                'cost_carneoo_provision' => $data[4] - $data[6],
                'description' => $data[7],
                'comment' => $data[8],
            ];
        }

        return $discounts;
    }

    /**
     * @param array $mappedDealersData
     */
    private function saveDealers(array $mappedDealersData)
    {
        $em = $this->getDoctrine()->getManager();

        foreach ($mappedDealersData as $dealerData) {
            $dealer = new Dealer();
            $dealer
                ->setName($dealerData['name'])
                ->setPerson($dealerData['person'])
                ->setMail($dealerData['email'])
                ->setPhone($dealerData['phone'])
                ->setFax($dealerData['fax'])
                ->setZip($dealerData['zipcode'])
                ->setCity($dealerData['city'])
                ->setAddress($dealerData['address'])
                ->setActive(true)
                ->setCreatedBy($this->getUser())
            ;

            $em->persist($dealer);
            $em->flush();

            $this->saveDiscount($dealerData['discounts'], $dealer);
        }
    }

    /**
     * @param array $mappedDiscountData
     * @param Dealer $dealer
     */
    private function saveDiscount(array $mappedDiscountData, Dealer $dealer)
    {
        $em = $this->getDoctrine()->getManager();

        foreach ($mappedDiscountData as $discountData) {
            /* Save discount */
            $discount = new Discount();
            $discount
                ->setDealer($dealer)
                ->setType($discountData['type'])
                ->setName($discountData['name'])
                ->setFrontName($discountData['front_name'])
                ->setAmountType($discountData['amount_type'])
                ->setValue($discountData['value'])
                ->setCarneoProvision($discountData['carneo_provision'])
                ->setLevel($discountData['level'])
                ->setCarneoAmountType($discountData['carneo_amount_type'])
                ->setModel($discountData['model'])
                ->setMark($discountData['mark'])
                ->setActive($discountData['active'])
                ->setMain($discountData['main'])
                ->setObligatory($discountData['obligatory'])
                ->setGroups([$discountData['groups']])
                ->setCreatedBy($this->getUser())
                ->setDeliveryTime($discountData['delivery_time'])
                ->setComment($discountData['comment'])
            ;

            $em->persist($discount);

            /* Save additional cost */
            $discount = new Discount();
            $discount
                ->setDealer($dealer)
                ->setType($discountData['cost_type'])
                ->setName($discountData['cost_name'])
                ->setFrontName($discountData['cost_name'])
                ->setAmountType($discountData['cost_amount_type'])
                ->setValue($discountData['cost_price'])
                ->setCarneoProvision($discountData['cost_carneoo_provision'])
                ->setLevel($discountData['level'])
                ->setCarneoAmountType($discountData['cost_carneo_amount_type'])
                ->setModel($discountData['model'])
                ->setMark($discountData['mark'])
                ->setActive($discountData['active'])
                ->setMain(false)
                ->setObligatory(true)
                ->setGroups([$discountData['groups']])
                ->setCreatedBy($this->getUser())
                ->setDeliveryTime($discountData['delivery_time'])
                ->setDescription($discountData['description'])
            ;

            $em->persist($discount);
            $em->flush();
        }
    }

    /**
     * @param string $name
     * @param string $person
     *
     * @return string
     */
    private function generateHash(string $name, string $person)
    {
        return sha1($name . $person);
    }
}