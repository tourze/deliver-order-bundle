<?php

namespace DeliverOrderBundle\Service;

interface DeliverSourceInterface
{
    /**
     * 检查该源处理器是否支持给定的源类型
     */
    public function supports(string $sourceType): bool;

    /**
     * 验证源是否存在且可以发货
     */
    public function validateSource(string $sourceType, string $sourceId): bool;

    /**
     * 获取源数据
     * @return array<string, mixed>
     */
    public function getSourceData(string $sourceType, string $sourceId): array;

    /**
     * 从源获取收货人信息
     * @return array<string, mixed>
     */
    public function getConsigneeInfo(string $sourceType, string $sourceId): array;

    /**
     * 从源获取待发货项目
     * @return array<int, array<string, mixed>>
     */
    public function getItems(string $sourceType, string $sourceId): array;

    /**
     * 处理发货单创建事件
     */
    public function onDeliverCreated(string $sourceType, string $sourceId, string $deliverSn): void;

    /**
     * 处理发货单发货事件
     * @param array<string, mixed> $trackingInfo
     */
    public function onDeliverShipped(string $sourceType, string $sourceId, string $deliverSn, array $trackingInfo): void;

    /**
     * 处理发货单取消事件
     */
    public function onDeliverCancelled(string $sourceType, string $sourceId, string $deliverSn, string $reason): void;

    /**
     * 处理发货单完成事件
     */
    public function onDeliverCompleted(string $sourceType, string $sourceId, string $deliverSn): void;
}
