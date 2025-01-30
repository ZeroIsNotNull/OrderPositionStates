<?php declare(strict_types=1);

namespace Zn\OrderPositionStates;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;


class ZnOrderPositionStates extends Plugin
{
    public function activate(ActivateContext $context): void
    {
        parent::activate($context);
    }

    public function update(UpdateContext $context): void
    {
        parent::update($context);
    }

    public function uninstall(UninstallContext $context): void
    {
        if($context->keepUserData()) {
            return;
        }
        $connection = $this->container->get(Connection::class);
        $connection->executeQuery("DROP TABLE IF EXISTS `order_line_item_states`");
        $connection->executeQuery("DROP TABLE IF EXISTS `order_position_states_translation`");
        $connection->executeQuery("DROP TABLE IF EXISTS `order_position_states`");
    }
}