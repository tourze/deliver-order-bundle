<?php

namespace DeliverOrderBundle\DTO;

use OrderCoreBundle\DTO\DeliveryOrderDTO;
use OrderCoreBundle\DTO\DeliveryStockDTO;

/**
 * 扩展的发货单 DTO，增加业务所需的额外属性和方法
 */
class ExtendedDeliveryOrderDTO
{
    private ?DeliveryOrderDTO $deliveryOrder = null;

    private ?string $sn = null;

    private ?string $sourceType = null;

    private ?string $sourceId = null;

    private ?\DateTimeInterface $shippedTime = null;

    /**
     * @var DeliveryStockDTO[]
     */
    private array $deliverStocks = [];

    public function getDeliveryOrder(): ?DeliveryOrderDTO
    {
        return $this->deliveryOrder;
    }

    public function setDeliveryOrder(?DeliveryOrderDTO $deliveryOrder): void
    {
        $this->deliveryOrder = $deliveryOrder;
    }

    // 委派方法到组合的DeliveryOrderDTO对象
    public function getId(): ?string
    {
        return $this->deliveryOrder?->getId();
    }

    public function setId(?string $id): void
    {
        if (null === $this->deliveryOrder) {
            $this->deliveryOrder = new DeliveryOrderDTO();
        }
        $this->deliveryOrder->setId($id);
    }

    public function getExpressCompany(): ?string
    {
        return $this->deliveryOrder?->getExpressCompany();
    }

    public function setExpressCompany(?string $expressCompany): void
    {
        if (null === $this->deliveryOrder) {
            $this->deliveryOrder = new DeliveryOrderDTO();
        }
        $this->deliveryOrder->setExpressCompany($expressCompany);
    }

    public function getExpressNumber(): ?string
    {
        return $this->deliveryOrder?->getExpressNumber();
    }

    public function setExpressNumber(?string $expressNumber): void
    {
        if (null === $this->deliveryOrder) {
            $this->deliveryOrder = new DeliveryOrderDTO();
        }
        $this->deliveryOrder->setExpressNumber($expressNumber);
    }

    public function getSn(): ?string
    {
        return $this->sn;
    }

    public function setSn(?string $sn): void
    {
        $this->sn = $sn;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(?string $sourceId): void
    {
        $this->sourceId = $sourceId;
    }

    public function getShippedTime(): ?\DateTimeInterface
    {
        return $this->shippedTime;
    }

    public function setShippedTime(?\DateTimeInterface $shippedTime): void
    {
        $this->shippedTime = $shippedTime;
    }

    /**
     * @return DeliveryStockDTO[]
     */
    public function getDeliverStocks(): array
    {
        return $this->deliverStocks;
    }

    /**
     * @param DeliveryStockDTO[] $deliverStocks
     */
    public function setDeliverStocks(array $deliverStocks): void
    {
        $this->deliverStocks = $deliverStocks;
    }
}
