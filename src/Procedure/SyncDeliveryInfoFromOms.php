<?php

namespace DeliverOrderBundle\Procedure;

use DeliverOrderBundle\Exception\DeliverOperationException;
use DeliverOrderBundle\Param\SyncDeliveryInfoFromOmsParam;
use DeliverOrderBundle\Service\DeliveryService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;

#[MethodTag(name: '发货管理')]
#[MethodDoc(summary: '从外部OMS同步发货信息')]
#[MethodExpose(method: 'SyncDeliveryInfoFromOms')]
#[Log]
#[Autoconfigure(public: true)]
class SyncDeliveryInfoFromOms extends BaseProcedure implements JsonRpcMethodInterface
{

    public function __construct(
        private readonly DeliveryService $deliveryService,
    ) {
    }

    /**
     * @phpstan-param SyncDeliveryInfoFromOmsParam $param
     */
    public function execute(SyncDeliveryInfoFromOmsParam|RpcParamInterface $param): ArrayResult
    {
        $this->validateDeliveryItems($param);

        try {
            $deliveryData = [
                'deliverySn' => $param->deliverySn,
                'sourceOrderId' => $param->sourceOrderId,
                'expressCompany' => $param->expressCompany,
                'expressCode' => $param->expressCode,
                'expressNumber' => $param->expressNumber,
                'consigneeName' => $param->consigneeName,
                'consigneePhone' => $param->consigneePhone,
                'consigneeAddress' => $param->consigneeAddress,
                'consigneeRemark' => $param->consigneeRemark,
                'shippedAt' => $param->shippedTime,
                'shippedBy' => $param->shippedBy,
                'deliveryItems' => $param->deliveryItems,
            ];

            $deliverOrder = $this->deliveryService->syncDeliveryFromOms($deliveryData);

            return new ArrayResult([
                'success' => true,
                'message' => '发货信息同步成功',
                'deliveryOrderId' => (string) $deliverOrder->getId(),
            ]);
        } catch (DeliverOperationException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateDeliveryItems(SyncDeliveryInfoFromOmsParam $param): void
    {
        if ([] === $param->deliveryItems) {
            throw new ApiException('发货商品列表不能为空');
        }

        foreach ($param->deliveryItems as $index => $item) {
            if (!isset($item['sku']) || '' === ($item['sku'] ?? '')) {
                throw new ApiException(sprintf('第%d个商品SKU不能为空', $index + 1));
            }
            if ($item['quantity'] <= 0) {
                throw new ApiException(sprintf('第%d个商品数量必须大于0', $index + 1));
            }
            if (!isset($item['productName']) || '' === ($item['productName'] ?? '')) {
                throw new ApiException(sprintf('第%d个商品名称不能为空', $index + 1));
            }
        }
    }
}
