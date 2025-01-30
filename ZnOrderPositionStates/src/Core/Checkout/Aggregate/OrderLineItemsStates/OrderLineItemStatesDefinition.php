<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderLineItemsStates;

use Zn\OrderPositionStates\Core\Checkout\OrderPositionStates\OrderPositionStatesDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

class OrderLineItemStatesDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'order_line_item_states';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('order_line_item_id', 'orderLineItemId', OrderLineItemDefinition::class))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('order_position_states_id', 'orderPositionStatesId', OrderPositionStatesDefinition::class))->addFlags(new Required(), new PrimaryKey()),
            (new ReferenceVersionField(OrderLineItemDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('orderLineItem', 'order_line_item_id', OrderLineItemDefinition::class, 'id'),
            new ManyToOneAssociationField('orderPositionStates', 'order_position_states_id', OrderPositionStatesDefinition::class, 'id'),
            new CreatedAtField(),
        ]);
    }
}