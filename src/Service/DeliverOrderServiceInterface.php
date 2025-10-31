<?php

namespace DeliverOrderBundle\Service;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Model\DeliverContext;

interface DeliverOrderServiceInterface
{
    /**
     * 从上下文创建发货单
     */
    public function createFromContext(DeliverContext $context): DeliverOrder;

    /**
     * 更新发货单状态
     */
    public function updateStatus(DeliverOrder $deliverOrder, string $status, ?string $reason = null): void;

    /**
     * 发货
     * @param array<string, mixed> $trackingInfo
     */
    public function ship(DeliverOrder $deliverOrder, array $trackingInfo): void;

    /**
     * 取消发货单
     */
    public function cancel(DeliverOrder $deliverOrder, string $reason): void;

    /**
     * 完成发货单
     */
    public function complete(DeliverOrder $deliverOrder): void;

    /**
     * 根据序列号获取发货单
     */
    public function getBySn(string $sn): ?DeliverOrder;

    /**
     * 根据来源获取发货单
     * @return array<DeliverOrder>
     */
    public function getBySource(string $sourceType, string $sourceId): array;

    /**
     * 验证发货单
     */
    public function validate(DeliverOrder $deliverOrder): bool;

    /**
     * 生成序列号
     */
    public function generateSn(): string;
}
