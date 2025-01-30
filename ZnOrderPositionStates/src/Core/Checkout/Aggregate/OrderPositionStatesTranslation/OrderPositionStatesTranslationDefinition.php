<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderPositionStatesTranslation;

use Zn\OrderPositionStates\Core\Checkout\OrderPositionStates\OrderPositionStatesDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderPositionStatesTranslationDefinition extends EntityTranslationDefinition {

    public const ENTITY_NAME = 'order_position_states_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return OrderPositionStatesTranslationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return OrderPositionStatesTranslationCollection::class;
    }

    public function getParentDefinitionClass(): string
    {
        return OrderPositionStatesDefinition::class;
    }


    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required)
        ]);
    }
}