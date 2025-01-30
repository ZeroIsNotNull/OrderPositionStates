<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderPositionStatesTranslation;

use Zn\OrderPositionStates\Core\Checkout\OrderPositionStates\OrderPositionStatesEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class OrderPositionStatesTranslationEntity extends TranslationEntity {

    /**
     * @var string|null
     */
    protected ?string $name;

    /**
     * @var string
     */
    protected $orderPositionStatesId;

    /**
     * @var OrderPositionStatesEntity|null
     */
    protected $orderPositionStates;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getOrderPositionStatesId(): string
    {
        return $this->orderPositionStatesId;
    }

    /**
     * @param string $orderPositionStatesId
     */
    public function setOrderPositionStatesId(string $orderPositionStatesId): void
    {
        $this->orderPositionStatesId = $orderPositionStatesId;
    }

    /**
     * @return OrderPositionStatesEntity|null
     */
    public function getOrderPositionStates(): ?OrderPositionStatesEntity
    {
        return $this->orderPositionStates;
    }

    /**
     * @param OrderPositionStatesEntity|null $orderPositionStates
     */
    public function setOrderPositionStates(?OrderPositionStatesEntity $orderPositionStates): void
    {
        $this->orderPositionStates = $orderPositionStates;
    }
}
