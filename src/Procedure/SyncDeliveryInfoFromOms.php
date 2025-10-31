<?php

namespace DeliverOrderBundle\Procedure;

use DeliverOrderBundle\Exception\DeliverOperationException;
use DeliverOrderBundle\Service\DeliveryService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
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
    #[MethodParam(description: '发货单号')]
    public string $deliverySn;

    #[MethodParam(description: '来源订单ID')]
    public string $sourceOrderId;

    #[MethodParam(description: '快递公司')]
    public string $expressCompany;

    #[MethodParam(description: '快递公司编码')]
    public string $expressCode;

    #[MethodParam(description: '快递单号')]
    public string $expressNumber;

    #[MethodParam(description: '收货人姓名')]
    public string $consigneeName;

    #[MethodParam(description: '收货人电话')]
    public string $consigneePhone;

    #[MethodParam(description: '收货地址')]
    public string $consigneeAddress;

    #[MethodParam(description: '收货备注')]
    public ?string $consigneeRemark = null;

    #[MethodParam(description: '发货时间')]
    public ?string $shippedTime = null;

    #[MethodParam(description: '发货人')]
    public ?string $shippedBy = null;

    /** @var array<int, array{sku: string, quantity: int, productName: string, productCode?: string, batchNo?: string, serialNo?: string, remark?: string}> */
    #[MethodParam(description: '发货商品列表')]
    public array $deliveryItems;

    public function __construct(
        private readonly DeliveryService $deliveryService,
    ) {
    }

    public static function getMockResult(): ?array
    {
        return [
            'success' => true,
            'message' => '发货信息同步成功',
            'deliveryOrderId' => '12345',
        ];
    }

    public function execute(): array
    {
        $this->validateDeliveryItems();

        try {
            $deliveryData = [
                'deliverySn' => $this->deliverySn,
                'sourceOrderId' => $this->sourceOrderId,
                'expressCompany' => $this->expressCompany,
                'expressCode' => $this->expressCode,
                'expressNumber' => $this->expressNumber,
                'consigneeName' => $this->consigneeName,
                'consigneePhone' => $this->consigneePhone,
                'consigneeAddress' => $this->consigneeAddress,
                'consigneeRemark' => $this->consigneeRemark,
                'shippedAt' => $this->shippedTime,
                'shippedBy' => $this->shippedBy,
                'deliveryItems' => $this->deliveryItems,
            ];

            $deliverOrder = $this->deliveryService->syncDeliveryFromOms($deliveryData);

            return [
                'success' => true,
                'message' => '发货信息同步成功',
                'deliveryOrderId' => (string) $deliverOrder->getId(),
            ];
        } catch (DeliverOperationException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateDeliveryItems(): void
    {
        if ([] === $this->deliveryItems) {
            throw new ApiException('发货商品列表不能为空');
        }

        foreach ($this->deliveryItems as $index => $item) {
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
