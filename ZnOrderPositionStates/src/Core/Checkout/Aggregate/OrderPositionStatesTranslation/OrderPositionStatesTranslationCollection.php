<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderPositionStatesTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;


/**
 * @method void                          add(OrderPositionStatesTranslationEntity $entity)
 * @method void                          set(string $key, OrderPositionStatesTranslationEntity $entity)
 * @method OrderPositionStatesTranslationEntity[]    getIterator()
 * @method OrderPositionStatesTranslationEntity[]    getElements()
 * @method OrderPositionStatesTranslationEntity|null get(string $key)
 * @method OrderPositionStatesTranslationEntity|null first()
 * @method OrderPositionStatesTranslationEntity|null last()
 */
class OrderPositionStatesTranslationCollection extends EntityCollection {
    
    protected function getExpectedClass(): string
    {
        return OrderPositionStatesTranslationEntity::class;
    }
}
