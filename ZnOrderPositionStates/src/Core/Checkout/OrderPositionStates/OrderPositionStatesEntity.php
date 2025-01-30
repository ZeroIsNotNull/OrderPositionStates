<?php declare(strict_types=1);

namespace Zn\OrderPositionStates\Core\Checkout\OrderPositionStates;

use Zn\OrderPositionStates\Core\Checkout\Aggregate\OrderPositionStatesTranslation\OrderPositionStatesTranslationCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class OrderPositionStatesEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $technicalName;

    /**
     * @var OrderLineItemCollection|null
     */
    protected OrderLineItemCollection $orderLineItems;

    /**
     * @var OrderPositionStatesTranslationCollection|null
     */
    protected $translations;


    /**
     * @return string
     */
    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    /**
     * @param string $technicalName
     */
    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }


    /**
     * @return OrderLineItemCollection|null
     */
    public function getOrderLineItems(): ?OrderLineItemCollection
    {
        return $this->orderLineItems;
    }

    /**
     * @param OrderLineItemCollection|null $orderLineItems
     */
    public function setOrderLineItems(?OrderLineItemCollection $orderLineItems): void
    {
        $this->orderLineItems = $orderLineItems;
    }

    /**
     * @return OrderPositionStatesTranslationCollection|null
     */
    public function getTranslations(): ?OrderPositionStatesTranslationCollection
    {
        return $this->translations;
    }

    /**
     * @param OrderPositionStatesTranslationCollection|null $translations
     */
    public function setTranslations(?OrderPositionStatesTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

}