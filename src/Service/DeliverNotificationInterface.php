<?php

namespace DeliverOrderBundle\Service;

use DeliverOrderBundle\Entity\DeliverOrder;

interface DeliverNotificationInterface
{
    /**
     * 通知发货单已创建
     */
    public function notifyCreated(DeliverOrder $deliverOrder): void;

    /**
     * 通知发货单已发货
     * @param array<string, mixed> $trackingInfo
     */
    public function notifyShipped(DeliverOrder $deliverOrder, array $trackingInfo): void;

    /**
     * 通知发货单已取消
     */
    public function notifyCancelled(DeliverOrder $deliverOrder, string $reason): void;

    /**
     * 通知发货单已完成
     */
    public function notifyCompleted(DeliverOrder $deliverOrder): void;
}
