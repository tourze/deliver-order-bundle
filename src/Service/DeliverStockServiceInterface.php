<?php

namespace DeliverOrderBundle\Service;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;

interface DeliverStockServiceInterface
{
    /**
     * 添加库存到发货单
     *
     * @note 此方法涉及并发敏感操作，建议在实现时添加适当的并发控制措施，
     *       如使用数据库事务、乐观锁或悲观锁来防止并发冲突。
     */
    /**
     * @param array<string, mixed> $stockData
     */
    public function addStock(DeliverOrder $deliverOrder, array $stockData): DeliverStock;

    /**
     * 更新库存信息
     *
     * @note 此方法涉及并发敏感操作，建议在实现时添加适当的并发控制措施，
     *       如使用数据库事务、乐观锁或悲观锁来防止并发冲突。
     */
    /**
     * @param array<string, mixed> $stockData
     */
    public function updateStock(DeliverStock $deliverStock, array $stockData): void;

    /**
     * 标记库存为已收货
     *
     * @note 此方法涉及并发敏感操作，建议在实现时添加适当的并发控制措施，
     *       如使用数据库事务、乐观锁或悲观锁来防止并发冲突。
     */
    /**
     * @param array<string, mixed> $receivedData
     */
    public function receiveStock(DeliverStock $deliverStock, array $receivedData): void;

    /**
     * 根据发货单获取库存
     *
     * @note 此方法涉及并发敏感操作，建议在实现时添加适当的并发控制措施，
     *       如使用数据库事务、乐观锁或悲观锁来防止并发冲突。
     */
    /**
     * @return array<DeliverStock>
     */
    public function getStocksByOrder(DeliverOrder $deliverOrder): array;

    /**
     * 计算发货单的总数量
     *
     * @note 此方法涉及并发敏感操作，建议在实现时添加适当的并发控制措施，
     *       如使用数据库事务、乐观锁或悲观锁来防止并发冲突。
     */
    public function calculateTotalQuantity(DeliverOrder $deliverOrder): int;
}
