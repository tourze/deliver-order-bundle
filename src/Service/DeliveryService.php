<?php

namespace DeliverOrderBundle\Service;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Exception\DeliverOperationException;
use DeliverOrderBundle\Repository\DeliverOrderRepository;
use DeliverOrderBundle\Repository\DeliverStockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[WithMonologChannel(channel: 'deliver_order')]
#[Autoconfigure(public: true)]
readonly class DeliveryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeliverOrderRepository $deliverOrderRepository,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $deliveryData
     * @return DeliverOrder
     * @throws DeliverOperationException
     */
    public function syncDeliveryFromOms(array $deliveryData): DeliverOrder
    {
        $this->logger->info('开始同步OMS发货信息', ['sn' => $deliveryData['deliverySn']]);

        $deliverySn = $deliveryData['deliverySn'] ?? null;
        if (!is_string($deliverySn) || '' === $deliverySn) {
            throw new DeliverOperationException('发货单号不能为空');
        }
        $existingOrder = $this->deliverOrderRepository->findOneBy(['sn' => $deliverySn]);
        if (null !== $existingOrder) {
            $this->logger->warning('发货单号已存在', ['sn' => $deliverySn]);
            throw new DeliverOperationException('发货单号已存在: ' . $deliverySn);
        }

        $this->entityManager->beginTransaction();
        try {
            $deliverOrder = $this->createDeliverOrderFromOms($deliveryData);
            $deliveryItems = $deliveryData['deliveryItems'] ?? [];
            if (!is_array($deliveryItems)) {
                throw new DeliverOperationException('商品数据不是数组格式');
            }
            /** @var array<int, array<string, mixed>> $typedItems */
            $typedItems = $deliveryItems;
            $this->createDeliverStocksFromOms($deliverOrder, $typedItems);

            $this->entityManager->persist($deliverOrder);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('OMS发货信息同步成功', [
                'sn' => $deliveryData['deliverySn'],
                'orderId' => $deliverOrder->getId(),
            ]);

            return $deliverOrder;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('OMS发货信息同步失败', [
                'sn' => $deliveryData['deliverySn'],
                'error' => $e->getMessage(),
            ]);
            throw new DeliverOperationException('同步发货信息失败: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return DeliverOrder
     * @throws DeliverOperationException
     */
    private function createDeliverOrderFromOms(array $data): DeliverOrder
    {
        $deliverOrder = new DeliverOrder();
        $deliverOrder->setSn($this->extractString($data, 'deliverySn'));
        $deliverOrder->setSourceType(SourceType::OMS);
        $deliverOrder->setSourceId($this->extractString($data, 'sourceOrderId'));
        $deliverOrder->setExpressCompany($this->extractString($data, 'expressCompany'));
        $deliverOrder->setExpressCode($this->extractString($data, 'expressCode'));
        $deliverOrder->setExpressNumber($this->extractString($data, 'expressNumber'));
        $deliverOrder->setConsigneeName($this->extractStringOrNull($data, 'consigneeName'));
        $deliverOrder->setConsigneePhone($this->extractStringOrNull($data, 'consigneePhone'));
        $deliverOrder->setConsigneeAddress($this->extractStringOrNull($data, 'consigneeAddress'));

        if (isset($data['consigneeRemark'])) {
            $deliverOrder->setConsigneeRemark($this->extractString($data, 'consigneeRemark'));
        }

        $shippedTimeStr = $data['shippedTime'] ?? null;
        $shippedTime = (null !== $shippedTimeStr && is_string($shippedTimeStr) && '' !== $shippedTimeStr) ? new \DateTimeImmutable($shippedTimeStr) : new \DateTimeImmutable();
        $deliverOrder->setShippedTime($shippedTime);

        if (isset($data['shippedBy'])) {
            $deliverOrder->setShippedBy($this->extractString($data, 'shippedBy'));
        }

        $deliverOrder->setStatus(DeliverOrderStatus::SHIPPED);
        $deliverOrder->setCreateTime(new \DateTimeImmutable());
        $deliverOrder->setCreatedBy('OMS_SYNC');

        $this->validateEntity($deliverOrder);

        return $deliverOrder;
    }

    /**
     * 从OMS数据创建发货库存记录
     * 不考虑并发 - 此方法在事务中调用，通过数据库事务保证一致性
     *
     * @param DeliverOrder $deliverOrder
     * @param array<int, array<string, mixed>> $items
     * @throws DeliverOperationException
     */
    private function createDeliverStocksFromOms(DeliverOrder $deliverOrder, array $items): void
    {
        foreach ($items as $item) {
            $deliverStock = new DeliverStock();
            $deliverStock->setDeliverOrder($deliverOrder);
            $deliverStock->setSkuCode($this->extractStringOrNull($item, 'sku'));
            $deliverStock->setQuantity($this->extractInt($item, 'quantity'));
            $deliverStock->setSkuName($this->extractStringOrNull($item, 'productName'));

            if (isset($item['productCode'])) {
                $deliverStock->setSkuId($this->extractString($item, 'productCode'));
            }

            if (isset($item['batchNo'])) {
                $deliverStock->setBatchNo($this->extractString($item, 'batchNo'));
            }

            if (isset($item['serialNo'])) {
                $deliverStock->setSerialNo($this->extractString($item, 'serialNo'));
            }

            if (isset($item['remark'])) {
                $deliverStock->setRemark($this->extractString($item, 'remark'));
            }

            $deliverStock->setCreateTime(new \DateTimeImmutable());

            $this->validateEntity($deliverStock);

            $deliverOrder->addDeliverStock($deliverStock);
        }
    }

    /**
     * @param object $entity
     * @throws DeliverOperationException
     */
    private function validateEntity(object $entity): void
    {
        $violations = $this->validator->validate($entity);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getMessage();
            }
            throw new DeliverOperationException('数据验证失败: ' . implode(', ', $messages));
        }
    }

    /**
     * @param string $deliverySn
     * @return DeliverOrder|null
     */
    public function findDeliveryBySn(string $deliverySn): ?DeliverOrder
    {
        return $this->deliverOrderRepository->findOneBy(['sn' => $deliverySn]);
    }

    /**
     * @param DeliverOrder $deliverOrder
     * @param DeliverOrderStatus $status
     */
    public function updateDeliveryStatus(DeliverOrder $deliverOrder, DeliverOrderStatus $status): void
    {
        $deliverOrder->setStatus($status);
        $deliverOrder->setUpdateTime(new \DateTimeImmutable());

        if (DeliverOrderStatus::RECEIVED === $status) {
            $deliverOrder->setReceivedTime(new \DateTimeImmutable());
        } elseif (DeliverOrderStatus::REJECTED === $status) {
            $deliverOrder->setRejectedTime(new \DateTimeImmutable());
        }

        $this->entityManager->persist($deliverOrder);
        $this->entityManager->flush();
    }

    /**
     * 安全地从数组中提取字符串值
     * @param array<string, mixed> $data
     */
    private function extractString(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * 安全地从数组中提取可空字符串值
     * @param array<string, mixed> $data
     */
    private function extractStringOrNull(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * 安全地从数组中提取整数值
     * @param array<string, mixed> $data
     */
    private function extractInt(array $data, string $key): int
    {
        $value = $data[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }
}
