<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Subscriber;

use Zn\OrderPositionStates\Service\DalManager;
use Shopware\Core\Checkout\Cart\Event\CartMergedEvent;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\Update\Struct\Version;
use Shopware\Storefront\Event\CartMergedSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;


class OrderSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var DalManager $dalManager
     */
    private DalManager $dalManager;

    /**
     * @var bool $eventRunning
     */
    private $eventRunning;

    private $number = 0;


    public function __construct(DalManager $dalmanager, LoggerInterface $logger)
    {
        $this->dalManager = $dalmanager;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderWrittenSave',
        ];
    }


    public function onOrderWrittenSave(EntityWrittenEvent $event)
    {
        if(isset($event->getPayloads()[0]['id'])) {
             $orderId  = $event->getPayloads()[0]['id'];
             $lineItemIds = $this->dalManager->getLineItemIdsOfOrder($event->getContext(), $orderId);
             if(sizeof($lineItemIds) > 0) {
                 $this->dalManager->checkDataAndAddPositionStateToLineItem($event->getContext(), $lineItemIds);
             }
        }
    }
}