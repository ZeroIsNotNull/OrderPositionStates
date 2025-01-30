<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Core\Checkout\OrderPositionStates;

use AsyncAws\S3\Input\PutBucketCorsRequest;
use Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderLineItemsStates\OrderLineItemStatesDefinition;
use Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderPositionStatesTranslation\OrderPositionStatesTranslationDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderPositionStatesDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'order_position_states';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return OrderPositionStatesEntity::class;
    }

    public function getCollectionClass(): string
    {
        return OrderPositionStatesCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
            (new StringField('technical_name', 'technicalName'))->addFlags(new ApiAware(), new Required()),
            (new TranslatedField('name')),
            (new TranslationsAssociationField(
                OrderPositionStatesTranslationDefinition::class,
                'order_position_states_id'
            )),
            (new ManyToManyAssociationField(
                'orderLineItem',
                OrderLineItemDefinition::class,
                OrderLineItemStatesDefinition::class,
                'order_line_item_id',
                'order_position_states_id'
            ))->addFlags(new Inherited())
        ]);
    }
}

