<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * SyncDeliveryInfoFromOms Procedure 的参数对象
 *
 * 用于从外部OMS同步发货信息的请求参数
 */
readonly class SyncDeliveryInfoFromOmsParam implements RpcParamInterface
{
    /**
     * @param array<int, array{sku: string, quantity: int, productName: string, productCode?: string, batchNo?: string, serialNo?: string, remark?: string}> $deliveryItems
     */
    public function __construct(
        #[MethodParam(description: '发货单号')]
        #[Assert\NotBlank]
        public string $deliverySn,

        #[MethodParam(description: '来源订单ID')]
        #[Assert\NotBlank]
        public string $sourceOrderId,

        #[MethodParam(description: '快递公司')]
        #[Assert\NotBlank]
        public string $expressCompany,

        #[MethodParam(description: '快递公司编码')]
        #[Assert\NotBlank]
        public string $expressCode,

        #[MethodParam(description: '快递单号')]
        #[Assert\NotBlank]
        public string $expressNumber,

        #[MethodParam(description: '收货人姓名')]
        #[Assert\NotBlank]
        public string $consigneeName,

        #[MethodParam(description: '收货人电话')]
        #[Assert\NotBlank]
        public string $consigneePhone,

        #[MethodParam(description: '收货地址')]
        #[Assert\NotBlank]
        public string $consigneeAddress,

        #[MethodParam(description: '收货备注')]
        public ?string $consigneeRemark = null,

        #[MethodParam(description: '发货时间')]
        public ?string $shippedTime = null,

        #[MethodParam(description: '发货人')]
        public ?string $shippedBy = null,

        #[MethodParam(description: '发货商品列表')]
        #[Assert\NotBlank]
        public array $deliveryItems = [],
    ) {
    }
}
