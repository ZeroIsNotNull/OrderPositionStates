<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Administration\Controller;

use Zn\OrderPositionStates\Service\DalManager;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class OrderPositionStatesController extends AbstractController
{
    /**
     * @var DalManager
     */
    private DalManager $dalManager;

    /**
     * @var Logger
     */
    private LoggerInterface $logger;

    public function __construct(
        DalManager $dalManager,
        LoggerInterface $logger
    )
    {
        $this->dalManager = $dalManager;
        $this->logger = $logger;
    }

    /**
     * @Route("/api/_action/orderpositionstates/getlist", name="api.action.orderpositionstates.get_list", methods={"GET"})
     */
    public function getList(Context $context): JsonResponse
    {
        $result = $this->dalManager->getList();
        return new JsonResponse($result, 200);
    }

    /**
     * @Route("/api/_action/orderpositionstates/get-state-id-by-name/{name}", name="api.action.orderpositionstates.get_state_id_by_name", methods={"GET"})
     */
    public function getStateIdByName(Context $context, String $name): JsonResponse
    {
        $id = $this->dalManager->getStateIdByName($context, $name);
        return new JsonResponse(strtolower($id), 200 );
    }

    /**
     * @Route("/api/_action/orderpositionstates/get-state-name-by-id/{id}", name="api.action.orderpositionstates.get_state_name_by_id", methods={"GET"})
     */
    public function getStateNameById(Context $context, String $id): JsonResponse
    {
        $name = $this->dalManager->getStateNameById($context, strtolower($id));
        return new JsonResponse(['name' => $name], 200 );
    }

    /**
     * @Route("/api/_action/orderpositionstates/get-position-state-id/{orderLineItemId}", name="api.action.orderpositionstates.get_position_state", methods={"GET"})
     */
    public function getPositionStatesId(Request $request, Context $context, String $orderLineItemId): JsonResponse
    {
        $orderPositionState = $this->dalManager->getPositionStatesId($context, strtolower($orderLineItemId));
        return new JsonResponse($orderPositionState, 200);
    }

    /**
     * @Route("/api/_action/orderpositionstates/get-position/{orderLineItemId}", name="api.action.orderpositionstates.get_position", methods={"GET"})
     */
    public function getPosition(Request $request, Context $context, String $orderLineItemId): JsonResponse
    {
        $lineItem = $this->dalManager->getPosition($context, strtolower($orderLineItemId));
        return new JsonResponse($lineItem, 200);
    }

    /**
     * @Route("/api/_action/orderposition/split-position/{orderLineItemId}", name="api.action.orderposition.split_position", methods={"PATCH"})
     */
    public function splitPosition(Request $request, Context $context, String $orderLineItemId): JsonResponse
    {
        $quantity = $request->get('quantity');
        $orderPositionStateId = $request->get('orderPositionStateId');
        if(!$orderPositionStateId) {
            return new JsonResponse(['error' => 'No OrderPositionStateId'], 500);
           // return new JsonResponse(['No OrderPositionStateId', 500]);
        }
        if(!$quantity) {
            $quantity = 1;
        }
        $status = $this->dalManager->splitPosition($context, strtolower($orderLineItemId), $quantity, strtolower($orderPositionStateId));
        return new JsonResponse($status);
    }

    /**
     * @Route("/api/_action/orderpositionstates/set-position-state/{orderLineItemId}/{newOrderPositionStateId}", name="api.action.orderpositionstates.set_position_state", methods={"PATCH"})
     */
    public function setState(Request $request, Context $context, String $orderLineItemId, String $newOrderPositionStateId): JsonResponse
    {
        $packageNumber = $request->get('packageNumber');
        if(!$packageNumber) {
            $packageNumber = '';
        }
        $response = $this->dalManager->setState($context, strtolower($orderLineItemId), strtolower($newOrderPositionStateId), $packageNumber);
        return new JsonResponse($response);
    }

    /**
     * @Route("/api/_action/extendedorder/get-data/{orderNumber}", name="api.action.extendedorder.get_data", methods={"GET"})
     */
    public function getData(Context $context, String $orderNumber): JsonResponse
    {
        $result = $this->dalManager->getOrderData($context, $orderNumber);
        return new JsonResponse($result, 200);
    }

    /**
     * @Route("/api/_action/extendedorder/set-states-and-packagenumber/{orderNumber}", name="api.action.extendedorder.set-states-and-packagenumber", methods={"PATCH"})
     */
    public function setStatesAndPackagenumber(Request $request, Context $context, String $orderNumber): JsonResponse
    {
        $result = $this->dalManager->setStatesAndPackagenumber($request, $context, $orderNumber);
        if(!$result) {
            return new JsonResponse(['error' => 'No Data to set.'], 403);
        }
        return new JsonResponse($result, 200);
    }
}

