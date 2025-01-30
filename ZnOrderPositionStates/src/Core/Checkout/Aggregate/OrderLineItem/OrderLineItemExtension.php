<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderLineItem;

use Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderLineItemsStates\OrderLineItemStatesDefinition;
use Zn\OrderPositionStates\Core\Checkout\OrderPositionStates\OrderPositionStatesDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderLineItemExtension extends EntityExtension  {


    public function getDefinitionClass(): string
    {
        return OrderLineItemDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'orderLineItemStates',
                OrderPositionStatesDefinition::class,
                OrderLineItemStatesDefinition::class,
                'order_line_item_id',
                'order_position_states_id'
            ))->addFlags(new Inherited())
        );
    }
}