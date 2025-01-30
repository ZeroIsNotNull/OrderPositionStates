<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;


class PluginConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var Connection $connection ;
     */
    private Connection $connection;

    /**
     * @var SystemConfigService $configService
     */
    private SystemConfigService $configService;


    public function __construct(SystemConfigService $configService, Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onConfigSave'
        ];
    }

    public function onConfigSave(SystemConfigChangedEvent $event) {
        $pluginConfig = $event->getKey();
        if($pluginConfig === 'ZnOrderPositionStates.config.deleteOrderPositionStates') {
            $statesToRemove = $event->getValue();
            $this->removePositionStatesInDatabase($statesToRemove);
        }
        if($pluginConfig === 'ZnOrderPositionStates.config.orderPositionStates') {
            $statesToAddAsString = $event->getValue();
            $this->addPositionStatesInDatabase($statesToAddAsString);
        }
    }

    private function removePositionStatesInDatabase(array $statesToRemove): void
    {
        foreach($statesToRemove as $id) {
            $result = $this->connection->fetchOne("SELECT id FROM order_position_states WHERE HEX(id)='" . $id . "';", [], []);
            if ($result != false) {
                $this->connection->executeQuery("DELETE FROM order_position_states WHERE HEX(id)='" . $id . "';");
            }
        }
    }

    private function addPositionStatesInDatabase(string $statesToAddAsString): void
    {
        $positionStatesArray = explode(",", trim($statesToAddAsString));
        foreach($positionStatesArray as $state) {
            if($state != '') {
                $result = $this->connection->fetchOne("SELECT id FROM order_position_states WHERE technical_name='" . trim($state) . "';", [], []);
                if (!$result) {
                    $sql = 'INSERT INTO `order_position_states` (`id`, `technical_name`, `created_at`, `updated_at`) VALUES  ("' . Uuid::randomBytes() . '", "' . trim($state) . '", CURRENT_TIMESTAMP(), NULL);';
                    $this->connection->executeQuery($sql);
                }
            }
        }
    }
}