<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceDefinitionCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DalManager
{
    /**
     * @var  EntityRepository
     */
    private EntityRepository $versionRepository;

    /**
     * @var  EntityRepository
     */
    private EntityRepository $orderLineItemRepository;

    /**
     * @var  EntityRepository
     */
    private EntityRepository $orderLineItemStatesRepository;

    /**
     * @var  EntityRepository
     */
    private EntityRepository $orderPositionStateRepository;

    /**
     * @var  EntityRepository
     */
    private EntityRepository $orderRepository;

    /**
     * @var  EntityRepository
     */
    private EntityRepository $orderDeliveryRepository;

    /**
     * @var  EntityRepository
     */
    private EntityRepository $stateMachineStateRepository;

    /**
     * @var  StateMachineRegistry
     */
    private StateMachineRegistry $stateMachineRegistry;

    /**
     * @var Connection $connection;
     */
    private Connection $connection;

    /**
     * @var SystemConfigService $configService
     */
    private SystemConfigService $configService;


    /**
     * @var String $defaultPositionState
     */
    private String $defaultPositionState;

    /**
     * @var bool $eventRunning
     */
    private bool $eventRunning;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;


    public function __construct(
        EntityRepository $versionRepository,
        EntityRepository $orderLineItemRepository,
        EntityRepository $orderLineItemStatesRepository,
        EntityRepository $orderPositionStateRepository,
        EntityRepository $orderRepository,
        EntityRepository $orderDeliveryRepository,
        EntityRepository $orderTransactionRepository,
        EntityRepository $stateMachineStateRepository,
        StateMachineRegistry $stateMachineRegistry,
        SystemConfigService $configService,
        Connection $connection,
        LoggerInterface $logger
    )
    {
        $this->versionRepository = $versionRepository;
        $this->orderLineItemRepository = $orderLineItemRepository;
        $this->orderLineItemStatesRepository = $orderLineItemStatesRepository;
        $this->orderPositionStateRepository = $orderPositionStateRepository;
        $this->orderRepository = $orderRepository;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stateMachineStateRepository = $stateMachineStateRepository;
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->configService = $configService;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->defaultPositionState = $this->configService->get('ZnOrderPositionStates.config.defaultPositionState');
    }

    public function checkDataAndAddPositionStateToLineItem(Context $context, Array $lineItemIds): void  {
        $stateId = $this->defaultPositionState;
        foreach($lineItemIds as $id) {
            $id = strtolower($id);
            if(!$this->isCustomFieldMarkerSet($context, $id)) {
                if($stateId) {
                    $positionName = $this->getStateNameById($context, $stateId);
                    try {
                        $status = $this->updateOrderLineItem($context, $id, $stateId, '', $positionName);
                    }
                    catch(\Exception $e) {
                        $this->logger->error('SET STATE 1: ' . $e);
                    }
                }
            }
        }
    }

    public function getList(): Array
    {
        return $this->connection->fetchAllAssociative('SELECT `technical_name`, HEX(id) as id FROM order_position_states WHERE technical_name IS NOT NULL', [], []);
    }

    public function getStateIdByName(Context $context, String $name):String
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $name));
        return $this->orderPositionStateRepository->searchIds($criteria, $context)->firstId();
    }

    public function getStateNameById(Context $context, String $id):String
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $response = $this->orderPositionStateRepository->search($criteria, $context)->jsonSerialize();
        $technicalName = $response['elements'][$id]->jsonSerialize()['technicalName'];
        return $technicalName;
    }


    public function getPositionStatesId(Context $context, String $orderLineItemId): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderLineItemId', $orderLineItemId));
        $state = $this->orderLineItemStatesRepository->searchIds($criteria, $context);
        $cleanedArray =  $state->jsonSerialize();
        unset($cleanedArray['data']);
        unset($cleanedArray['extensions']);
        return $cleanedArray;
    }

    public function getPosition(Context $context, String $orderLineItemId): array
    {
        $criteria = new Criteria([$orderLineItemId]);
        $criteria->addFilter(new EqualsFilter('id', $orderLineItemId));
        $orderPosition = $this->orderLineItemRepository->search($criteria, $context);
        $data = [];
        $data['state'] = $this->getPositionStatesId($context, $orderLineItemId);
        if(isset($data['state']['ids'][0])) {
            $positionStatesId = $data['state']['ids'][0]['orderPositionStatesId'];
            $data['state']['positionStatesName'] = $this->getStateNameById($context, $positionStatesId);
            $data['orderLineItem'] = $orderPosition->jsonSerialize();
        }
        return $data;
    }

    public function setState(Context $context, String $orderLineItemId, String $newOrderPositionStateId, string $packageNumber = ''): int
    {
        if(!$this->orderPositionStateExists($context, $newOrderPositionStateId)) {
            return 403;
        }
        $oldPositionState = $this->getPositionStatesId($context, $orderLineItemId)['ids'];
        if(!isset($oldPositionState[0]['orderPositionStatesId']) || $oldPositionState[0]['orderPositionStatesId'] == '') {
            $oldPositionState[0]['orderPositionStatesId'] = Uuid::fromBytesToHex($this->defaultPositionStateExists());
        }
        try{
            $this->orderLineItemStatesRepository->delete([
                [
                    'orderLineItemId' => $orderLineItemId,
                    'orderPositionStatesId' => $oldPositionState[0]['orderPositionStatesId'],
                ]
            ], $context);
            $positionName = $this->getStateNameById($context, $newOrderPositionStateId);
            $status = $this->updateOrderLineItem($context, $orderLineItemId, $newOrderPositionStateId, $packageNumber, $positionName);
        } catch(\Exception $e) {
            $status = 500;
            $this->logger->error('SET STATE 2: ' . $e);
        }
        return $status;
    }

    public function getOrderData(Context $context, String $orderNumber):array
    {
        $data = [];
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        $orderId = $this->orderRepository->searchIds($criteria, $context)->firstId();
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('transactions');
        $order = $this->orderRepository->search($criteria, $context)->getEntities()->jsonSerialize();
        $data['orderId'] = $orderId;
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $lineItemIds = $this->orderLineItemRepository->searchIds($criteria, $context)->getIds();
        $positions = [];
        $i = 0;
        foreach($lineItemIds as $lId) {
           $positions[$i] = $this->getPosition($context, $lId);
           if(isset($positions[$i]['orderLineItem']['elements'][$lId])) {
               $temp = [];
               $quantity = null;
               $articleNumber = null;
               $customFields = [];
               if(isset($positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['priceDefinition']->jsonSerialize()['quantity'])) {
                   $quantity = $positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['priceDefinition']->jsonSerialize()['quantity'];
               }
               if(isset($positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['payload']['productNumber'])) {
                   $articleNumber = $positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['payload']['productNumber'];
               }
               if(isset($positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['customFields'])) {
                   $customFields[] = $positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['customFields'];
               }
               if(isset($positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['payload']['customFields'])) {
                   $customFields[] = $positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['payload']['customFields'];
               }
               if(isset($positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['customFields']['packageNumber'])) {
                   $temp['packageNumber'] = $positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['customFields']['packageNumber'];
               }
               $type = $positions[$i]['orderLineItem']['elements'][$lId]->jsonSerialize()['type'];
               $temp['id'] = $lId;
               $temp['state'] = $this->getPositionStatesId($context, $lId);
               $temp['articleNumber'] = $articleNumber;
               $temp['quantity'] = $quantity;
               $temp['type'] = $type;
               $temp['orderPackagenumber'] = $this->getPackageNumber($context, $orderId);
               $temp['customFields'] = $customFields;
               $data['lineItems'][$i] = [
                   'relevantData' => $temp,
                   'regularData' => $positions[$i]
               ];
               $i++;
           }
        }
        $data['orderData'] = $order;
        return $data;
    }

    public function setStatesAndPackagenumber($request, $context, $orderNumber):array {
        $orderPackageNumber = $request->get('orderPackageNumber');
        $orderStateAction = $request->get('orderStateAction');
        $paymentStateAction = $request->get('paymentStateAction');
        $deliveryStateAction = $request->get('deliveryStateAction');
        if($orderPackageNumber == '' && $orderStateAction == '' && $paymentStateAction == '' && $deliveryStateAction == '') {
            return ['error' => 'No Data to set.'];
        }
        $orderId = $this->getIdOfOrder($context, $orderNumber);
        if(!$orderId) {
            return ['error' => 'No Order found with Number: ' . $orderNumber];
        }
        $actions = [
            0 => [
                'orderStatus',
                $orderId,
                $this->orderRepository,
                $orderStateAction
            ],
            1 => [
                'paymentStatus',
                $this->getIdOfEntity($context, $this->orderTransactionRepository, $orderId),
                $this->orderTransactionRepository,
                $paymentStateAction
            ],
            2 => [
                'deliveryStatus',
                $this->getIdOfEntity($context, $this->orderDeliveryRepository, $orderId),
                $this->orderDeliveryRepository,
                $deliveryStateAction
            ]
        ];
        $message = $this->setOrderStates($context, $actions);
        if($orderPackageNumber != '' && !empty($orderPackageNumber)) {
            $message['trackingCode'] = [
                'PackageNumber' => $this->setTrackingCode($context, $this->orderDeliveryRepository, $orderId, $orderPackageNumber)
            ];
        }
        return $message;
    }

    public function getLineItemIdsOfOrder(Context $context, string $orderId):array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $lineItemIds = $this->orderLineItemRepository->searchIds($criteria, $context)->getIds();
        $ids = [];
        foreach($lineItemIds as $lId) {
            $ids[] = $lId;
        }
        return $ids;
    }


    public function splitPosition(Context $context, string $lId, int $quantity, string $orderPositionStateId):array
    {
        $criteria = new Criteria([$lId]);
        $lineItem = $this->orderLineItemRepository->search($criteria, $context)->getElements()[$lId]->jsonSerialize();
        $currentQuantity = $lineItem['quantity'];
        $newQuantity = $currentQuantity - $quantity;
        if($newQuantity < 1 || $quantity < 0) {
            return ['Quantity calcualation has errors. Quantity must be > 0 for splitted itemd as well as the new item ', 500];
        }

        $identifier = Uuid::randomHex();
        $taxRules = $lineItem['price']->getTaxRules();
        $taxRate = $lineItem['price']->getCalculatedTaxes()->jsonSerialize()[0]->getTaxRate();
        $unitPrice = $lineItem['unitPrice'];
        $newPriceDefinition = new QuantityPriceDefinition($unitPrice, $taxRules, $quantity);
        $calculatedTax = new CalculatedTaxCollection([new CalculatedTax($unitPrice * $taxRate / 100, $taxRate, $unitPrice)]);
        $newPrice = new CalculatedPrice($unitPrice, $unitPrice * $quantity, $calculatedTax, $taxRules, $quantity);

        $oldPriceDefinition = new QuantityPriceDefinition($unitPrice, $taxRules, $newQuantity);
        $oldPrice = new CalculatedPrice($unitPrice, $unitPrice * $newQuantity, $calculatedTax, $taxRules, $newQuantity);
        $lineItemSplit = [
            'id' => $identifier,
            'identifier' => $identifier,
            'quantity' => $quantity,
            'type' => $lineItem['type'],
            'orderId' => $lineItem['orderId'],
            'label' => $lineItem['label'] . ' (PA)',
            'price' => $newPrice,
            'priceDefinition' => $newPriceDefinition,
            'good' => true,
            'payload' => $lineItem['payload'],
            'productId' => $lineItem['productId'],
            'referencedId' => $lineItem['referencedId']
        ];
        try {
            $this->orderLineItemRepository->create([$lineItemSplit], $context);
            $this->orderLineItemRepository->update([
                [
                    'id' => $lId,
                    'quantity' => $newQuantity,
                    'price' => $oldPrice,
                    'priceDefinition' => $oldPriceDefinition,
                ]
            ], $context);
            $currentPositionStateId = $this->getCurrentPositionStateId($context, $identifier);

            if($currentPositionStateId != '') {
                $this->orderLineItemStatesRepository->delete([
                    [
                        'orderLineItemId' => $identifier,
                        'orderPositionStatesId' => $currentPositionStateId,
                    ]
                ], $context);
            }
            $positionName = $this->getStateNameById($context, $orderPositionStateId);
            $this->updateOrderLineItem($context, $identifier, $orderPositionStateId, '', $positionName);
        } catch(\Exception $e) {
            $this->logger->error('LINEITEMSPLITTED Exception: ' . $e);
            return ['write Exception', 500];
        }
        return [
            ['newId' => $identifier],
            ['data' => $lineItemSplit],
            ['message' => 'LineItem with Id '. $lId . ' splitted'],
            ['status' => 200],
        ];
    }

    public function getCurrentPositionStateId(Context $context, string $lineItemId):null|string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderLineItemId', $lineItemId));
        $stateIds = $this->orderLineItemStatesRepository->searchIds($criteria, $context)->getIds();
        foreach($stateIds as $stateId) {
            if(isset($stateId['orderPositionStatesId'])) {
                return $stateId['orderPositionStatesId'];
            }
        }
        return null;
    }

    private function defaultPositionStateExists(): ?String {
        return $this->connection->fetchOne("SELECT id FROM order_position_states WHERE HEX(id)='" . $this->defaultPositionState . "';", []);
    }

    private function orderPositionStateExists(Context $context, String $newOrderPositionStateId): bool {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $newOrderPositionStateId));
        $orderPositionStateId = $this->orderPositionStateRepository->searchIds($criteria, $context)->firstId();
        if($orderPositionStateId != null) {
            return true;
        }
        return false;
    }

    private function isCustomFieldMarkerSet(Context $context, String $orderLineItemId): bool {
        try{
            $criteria = new Criteria([$orderLineItemId]);
            $criteria->addFilter(new EqualsFilter('id', $orderLineItemId));
            $orderPosition = $this->orderLineItemRepository->search($criteria, $context);
            $customFields = $orderPosition->jsonSerialize()['elements'][$orderLineItemId]->jsonSerialize()['customFields'];
            if(isset($customFields['positionStateIsSet'])) {
                if($customFields['positionStateIsSet'] == 'true') {
                    return true;
                }
            }
        } catch(\Exception $e) {
            $this->logger->error('isCustomFieldMarkerSetFunction Exception: ');
        }
        return false;
    }

    private function getPackageNumber(Context $context, string $orderId):array {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $orderDelivery = $this->orderDeliveryRepository->search($criteria, $context)->jsonSerialize();
        $id = array_keys($orderDelivery['elements'])[0];
        $trackingCodes = [];
        if(isset($orderDelivery['elements'][$id]->jsonSerialize()['trackingCodes'])) {
            $trackingCodes = $orderDelivery['elements'][$id]->jsonSerialize()['trackingCodes'];
        }
        
        return $trackingCodes;
    }


    private function transitionExists(Context $context, ?string $actionName):bool
    {
        if($actionName == '' || !$actionName) {
            return false;
        }
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('actionName', $actionName));
        try {
            $state = $this->stateMachineStateRepository->searchIds($criteria, $context)->firstId();
            $true = false;
            if($state != null) {
                $true = true;
            }
        } catch(\Exception $e) {
            $true = false;
        }
        return $true;
    }

    private function setOrderStates(Context $context, array $actions):array
    {
        $message = [];
        foreach($actions as $action) {
            $resource = $action[0];
            $entityId = $action[1];
            $repository = $action[2];
            $actionName = $action[3];
            if(isset($actionName) && $actionName != '' && $actionName != 'null') {
                try {
                    $this->stateMachineRegistry->transition(new Transition(
                        $repository->getDefinition()::ENTITY_NAME,
                        $entityId,
                        $actionName,
                        'stateId'
                    ), $context);
                    $message[$resource] = [
                        $repository->getDefinition()::ENTITY_NAME => [$resource . ' is set: ', true]
                    ];
                } catch (\Exception $e) {
                    $message[$resource] = [
                        $repository->getDefinition()::ENTITY_NAME => [$resource . ' is set: ', false]
                    ];
                }
            }
        }
        return $message;
    }

    private function setTrackingCode(Context $context, EntityRepository $repository, string $oderId, string $packageNumber):array
    {
        $packageNumberAsArray = $this->packageNumbersAsArray($packageNumber);
        try {
            $repository->update([
                [
                    'id' => $this->getIdOfEntity($context, $repository, $oderId),
                    'trackingCodes' => $packageNumberAsArray,
                ]
            ], $context);
        } catch(\Exception $e) {
            return [$packageNumber . ' is set ' . $e, false];
        }
        return [$packageNumber . ' is set', true];
    }

    private function packageNumbersAsArray(string $packageNumbers):array
    {
        $packageNumbers = str_replace(' ', '', $packageNumbers);

        if (!str_contains($packageNumbers, ',')) {
            $packageNumbersAsArray = [$packageNumbers];
        } else {
            $packageNumbersAsArray = explode(",", $packageNumbers);
        }

        return $packageNumbersAsArray;
    }

    private function updateOrderLineItem(Context $context, string $orderLineItemId, string $newOrderPositionStateId, string $packageNumber, string $positionName):int
    {
        if($context->getVersionId() === Defaults::LIVE_VERSION) {
            try {
                $this->orderLineItemRepository->update([
                    [
                        'id' => $orderLineItemId,
                        'customFields' => [
                            'positionStateIsSet' => 'true',
                            'trackingCode'       => $packageNumber,
                            'positionStateName'  => $positionName,
                        ]
                    ]
                ], $context);
                $this->orderLineItemStatesRepository->upsert([
                    [
                        'orderLineItemId' => $orderLineItemId,
                        'orderPositionStatesId' => $newOrderPositionStateId,
                        'orderLineItemVersionId' => Defaults::LIVE_VERSION
                    ]
                ], $context);
            } catch (\Exception $e) {
                $this->logger->error('Not Updated');
                return 500;
            }
            return 200;
        }
        return 404;
    }

    private function getIdOfOrder(Context $context, string $orderNumber):string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        return $this->orderRepository->searchIds($criteria, $context)->firstId();
    }


    private function getIdOfEntity(Context $context, EntityRepository $repository, string $orderId):string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        return $repository->searchIds($criteria, $context)->firstId();
    }
}