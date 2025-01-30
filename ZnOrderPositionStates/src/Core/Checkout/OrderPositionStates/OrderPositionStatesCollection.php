<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Core\Checkout\OrderPositionStates;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(OrderPositionStatesEntity $entity)
 * @method void               set(string $key, OrderPositionStatesEntity $entity)
 * @method OrderPositionStatesEntity[]    getIterator()
 * @method OrderPositionStatesEntity[]    getElements()
 * @method OrderPositionStatesEntity|null get(string $key)
 * @method OrderPositionStatesEntity|null first()
 * @method OrderPositionStatesEntity|null last()
 */
class OrderPositionStatesCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OrderPositionStatesEntity::class;
    }
}