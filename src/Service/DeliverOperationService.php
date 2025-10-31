<?php

namespace DeliverOrderBundle\Service;

use DeliverOrderBundle\DTO\ExtendedDeliveryOrderDTO;
use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Repository\DeliverOrderRepository;
use OrderCoreBundle\DTO\DeliveryOrderDTO;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Service\DeliverOperationInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * 发货操作服务 - 实现 order-core-bundle 的 DeliverOperationInterface
 */
class DeliverOperationService implements DeliverOperationInterface
{
    private DeliveryOrdersCollection $ordersCollection;

    public function __construct(
        private readonly DeliverOrderRepository $deliverOrderRepository,
    ) {
        $this->ordersCollection = new DeliveryOrdersCollection();
    }

    public function notifyShipment(Contract $contract): bool
    {
        // 这里应该实现实际的发货通知逻辑
        // 例如发送消息队列、调用第三方API等
        return true;
    }

    public function hasDeliveryRecords(Contract $contract): bool
    {
        $deliveryOrders = $this->getDeliveryOrdersByContract($contract);

        return [] !== $deliveryOrders;
    }

    public function markAllDeliveryAsReceived(Contract $contract, UserInterface $user, \DateTimeInterface $now): bool
    {
        $deliveryOrders = $this->getDeliveryOrdersByContract($contract);

        foreach ($deliveryOrders as $order) {
            // 这里应该实现标记为已收货的逻辑
            // 例如更新 DeliverStock 的状态
        }

        return true;
    }

    public function getDeliveryStatusSummary(Contract $contract): array
    {
        $deliveryOrders = $this->getExtendedDeliveryOrdersByContract($contract);
        $shippedCount = $this->countShippedOrders($deliveryOrders);
        $totalCount = count($deliveryOrders);

        return [
            'shipped_count' => $shippedCount,
            'total_count' => $totalCount,
            'all_received' => $shippedCount === $totalCount && $totalCount > 0,
        ];
    }

    /**
     * @return DeliveryOrderDTO[]
     */
    public function getDeliveryOrdersByContract(Contract $contract): array
    {
        $entities = $this->deliverOrderRepository->findBy([
            'sourceType' => SourceType::CONTRACT,
            'sourceId' => (string) $contract->getId(),
        ]);

        return array_map(function (DeliverOrder $entity): DeliveryOrderDTO {
            $extendedDTO = $this->convertToExtendedDTO($entity);
            $baseDTO = $extendedDTO->getDeliveryOrder();

            return $baseDTO ?? new DeliveryOrderDTO();
        }, $entities);
    }

    /**
     * @return ExtendedDeliveryOrderDTO[]
     */
    private function getExtendedDeliveryOrdersByContract(Contract $contract): array
    {
        $entities = $this->deliverOrderRepository->findBy([
            'sourceType' => SourceType::CONTRACT,
            'sourceId' => (string) $contract->getId(),
        ]);

        return array_map(fn (DeliverOrder $entity) => $this->convertToExtendedDTO($entity), $entities);
    }

    public function getContractFirstDeliveryTime(Contract $contract): ?\DateTimeInterface
    {
        $deliveryOrders = $this->getExtendedDeliveryOrdersByContract($contract);

        return $this->calculateFirstDeliveryTime($deliveryOrders);
    }

    public function getContractLastDeliveryTime(Contract $contract): ?\DateTimeInterface
    {
        $deliveryOrders = $this->getExtendedDeliveryOrdersByContract($contract);

        return $this->calculateLastDeliveryTime($deliveryOrders);
    }

    public function getContractDeliveredQuantity(Contract $contract): int
    {
        $deliveryOrders = $this->getExtendedDeliveryOrdersByContract($contract);

        return $this->calculateTotalQuantity($deliveryOrders);
    }

    public function isContractFullyDelivered(Contract $contract): bool
    {
        $totalOrderQuantity = $this->calculateContractTotalQuantity($contract);
        $deliveredQuantity = $this->getContractDeliveredQuantity($contract);

        return $deliveredQuantity >= $totalOrderQuantity;
    }

    public function getOrderProductDeliveredQuantity(OrderProduct $orderProduct): int
    {
        $contract = $orderProduct->getContract();
        if (null === $contract) {
            return 0;
        }

        $skuId = $orderProduct->getSku()?->getId();
        if (null === $skuId) {
            return 0;
        }

        $deliveryOrders = $this->getExtendedDeliveryOrdersByContract($contract);

        return $this->calculateDeliveredQuantityForSku($deliveryOrders, $skuId);
    }

    public function getOrderProductFirstDeliveryTime(OrderProduct $orderProduct): ?\DateTimeInterface
    {
        $contract = $orderProduct->getContract();
        if (null === $contract) {
            return null;
        }

        $skuId = $orderProduct->getSku()?->getId();
        if (null === $skuId) {
            return null;
        }

        $deliveryOrders = $this->getExtendedDeliveryOrdersByContract($contract);

        return $this->calculateFirstDeliveryTimeForSku($deliveryOrders, $skuId);
    }

    public function getOrderProductLastDeliveryTime(OrderProduct $orderProduct): ?\DateTimeInterface
    {
        $contract = $orderProduct->getContract();
        if (null === $contract) {
            return null;
        }

        $skuId = $orderProduct->getSku()?->getId();
        if (null === $skuId) {
            return null;
        }

        $deliveryOrders = $this->getExtendedDeliveryOrdersByContract($contract);

        return $this->calculateLastDeliveryTimeForSku($deliveryOrders, $skuId);
    }

    public function getDeliveryOrderById(int $deliveryOrderId): ?DeliveryOrderDTO
    {
        $entity = $this->deliverOrderRepository->find($deliveryOrderId);

        if (null === $entity) {
            return null;
        }

        $extendedDTO = $this->convertToExtendedDTO($entity);

        return $extendedDTO->getDeliveryOrder();
    }

    public function getExpressTrackingData(int $deliveryOrderId): array
    {
        // 这里应该实现快递查询逻辑
        // 可以调用快递API或从本地缓存获取数据

        return [
            'status' => '1',
            'data' => [],
            'message' => 'success',
        ];
    }

    private function convertToExtendedDTO(DeliverOrder $entity): ExtendedDeliveryOrderDTO
    {
        return $this->ordersCollection->convertToExtendedDTO($entity);
    }

    /**
     * @param ExtendedDeliveryOrderDTO[] $deliveryOrders
     */
    private function countShippedOrders(array $deliveryOrders): int
    {
        return $this->ordersCollection->countShippedOrders($deliveryOrders);
    }

    // 内联时间计算方法（原 DeliveryTimeCalculator）
    /**
     * @param ExtendedDeliveryOrderDTO[] $deliveryOrders
     */
    private function calculateFirstDeliveryTime(array $deliveryOrders): ?\DateTimeInterface
    {
        $firstShippedTime = null;
        foreach ($deliveryOrders as $orderDTO) {
            $shippedTime = $orderDTO->getShippedTime();
            if (null !== $shippedTime) {
                if (null === $firstShippedTime || $shippedTime < $firstShippedTime) {
                    $firstShippedTime = $shippedTime;
                }
            }
        }

        return $firstShippedTime;
    }

    /**
     * @param ExtendedDeliveryOrderDTO[] $deliveryOrders
     */
    private function calculateLastDeliveryTime(array $deliveryOrders): ?\DateTimeInterface
    {
        $lastShippedTime = null;
        foreach ($deliveryOrders as $orderDTO) {
            $shippedTime = $orderDTO->getShippedTime();
            if (null !== $shippedTime) {
                if (null === $lastShippedTime || $shippedTime > $lastShippedTime) {
                    $lastShippedTime = $shippedTime;
                }
            }
        }

        return $lastShippedTime;
    }

    /**
     * @param ExtendedDeliveryOrderDTO[] $deliveryOrders
     */
    private function calculateFirstDeliveryTimeForSku(array $deliveryOrders, string $skuId): ?\DateTimeInterface
    {
        $firstShippedTime = null;

        foreach ($deliveryOrders as $orderDTO) {
            $shippedTime = $orderDTO->getShippedTime();
            if (null === $shippedTime) {
                continue;
            }

            foreach ($orderDTO->getDeliverStocks() as $stockDTO) {
                if ($stockDTO->getSkuCode() !== $skuId) {
                    continue;
                }

                if (null === $firstShippedTime || $shippedTime < $firstShippedTime) {
                    $firstShippedTime = $shippedTime;
                }
            }
        }

        return $firstShippedTime;
    }

    /**
     * @param ExtendedDeliveryOrderDTO[] $deliveryOrders
     */
    private function calculateLastDeliveryTimeForSku(array $deliveryOrders, string $skuId): ?\DateTimeInterface
    {
        $lastShippedTime = null;

        foreach ($deliveryOrders as $orderDTO) {
            $shippedTime = $orderDTO->getShippedTime();
            if (null === $shippedTime) {
                continue;
            }

            foreach ($orderDTO->getDeliverStocks() as $stockDTO) {
                if ($stockDTO->getSkuCode() !== $skuId) {
                    continue;
                }

                if (null === $lastShippedTime || $shippedTime > $lastShippedTime) {
                    $lastShippedTime = $shippedTime;
                }
            }
        }

        return $lastShippedTime;
    }

    // 内联数量计算方法（原 DeliveryQuantityCalculator）
    /**
     * @param ExtendedDeliveryOrderDTO[] $deliveryOrders
     */
    private function calculateTotalQuantity(array $deliveryOrders): int
    {
        $totalQuantity = 0;
        foreach ($deliveryOrders as $orderDTO) {
            foreach ($orderDTO->getDeliverStocks() as $stockDTO) {
                $totalQuantity += $stockDTO->getQuantity();
            }
        }

        return $totalQuantity;
    }

    private function calculateContractTotalQuantity(Contract $contract): int
    {
        $totalOrderQuantity = 0;
        foreach ($contract->getProducts() as $orderProduct) {
            $totalOrderQuantity += $orderProduct->getQuantity();
        }

        return $totalOrderQuantity;
    }

    /**
     * @param ExtendedDeliveryOrderDTO[] $deliveryOrders
     */
    private function calculateDeliveredQuantityForSku(array $deliveryOrders, string $skuId): int
    {
        $deliveredQuantity = 0;

        foreach ($deliveryOrders as $orderDTO) {
            foreach ($orderDTO->getDeliverStocks() as $stockDTO) {
                if ($stockDTO->getSkuCode() === $skuId) {
                    $deliveredQuantity += $stockDTO->getQuantity();
                }
            }
        }

        return $deliveredQuantity;
    }
}
