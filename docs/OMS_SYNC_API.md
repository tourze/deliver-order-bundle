# OMS 发货信息同步 API 文档

## 接口概述

该接口用于外部 OMS 系统向本地系统同步发货信息。

## 接口信息
- **版本**: 2025082111200
- **方法名**: `SyncDeliveryInfoFromOms`
- **协议**: JSON-RPC 2.0
- **IP 限制**: 只允许配置的白名单 IP 地址访问

## 请求格式

### JSON-RPC 请求示例

```json
{
    "jsonrpc": "2.0",
    "method": "SyncDeliveryInfoFromOms",
    "params": {
        "deliverySn": "DELIVER-20240101-001",
        "sourceOrderId": "ORDER-001",
        "expressCompany": "顺丰快递",
        "expressCode": "SF",
        "expressNumber": "SF1234567890",
        "consigneeName": "张三",
        "consigneePhone": "13800138000",
        "consigneeAddress": "上海市浦东新区陆家嘴环路1000号",
        "consigneeRemark": "请轻拿轻放",
        "shippedAt": "2024-01-01 10:00:00",
        "shippedBy": "OMS操作员",
        "deliveryItems": [
            {
                "sku": "SKU001",
                "quantity": 2,
                "productName": "iPhone 15 Pro",
                "productCode": "PROD001",
                "batchNo": "BATCH001",
                "serialNo": "SN001",
                "remark": "含原装配件"
            },
            {
                "sku": "SKU002",
                "quantity": 1,
                "productName": "AirPods Pro",
                "productCode": "PROD002"
            }
        ]
    },
    "id": 1
}
```

## 参数说明

### 必填参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| deliverySn | string | 发货单号，系统内唯一 |
| sourceOrderId | string | 来源订单ID |
| expressCompany | string | 快递公司名称 |
| expressCode | string | 快递公司编码 |
| expressNumber | string | 快递单号 |
| consigneeName | string | 收货人姓名 |
| consigneePhone | string | 收货人电话 |
| consigneeAddress | string | 收货地址 |
| deliveryItems | array | 发货商品列表 |

### 可选参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| consigneeRemark | string | 收货备注 |
| shippedAt | string | 发货时间，格式: YYYY-MM-DD HH:mm:ss |
| shippedBy | string | 发货人 |

### deliveryItems 数组项结构

#### 必填字段

| 字段名 | 类型 | 说明 |
|--------|------|------|
| sku | string | 商品SKU编码 |
| quantity | integer | 数量，必须大于0 |
| productName | string | 商品名称 |

#### 可选字段

| 字段名 | 类型 | 说明 |
|--------|------|------|
| productCode | string | 商品编码 |
| batchNo | string | 批次号 |
| serialNo | string | 序列号 |
| remark | string | 备注 |

## 响应格式

### 成功响应

```json
{
    "jsonrpc": "2.0",
    "result": {
        "success": true,
        "message": "发货信息同步成功",
        "deliveryOrderId": "123456"
    },
    "id": 1
}
```

### 错误响应

```json
{
    "jsonrpc": "2.0",
    "error": {
        "code": -32603,
        "message": "发货单号已存在: DELIVER-20240101-001"
    },
    "id": 1
}
```

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| -32603 | 内部错误，具体信息见 message |
| -32602 | 参数无效 |
| -32001 | IP 地址不在白名单中，访问被拒绝 |

## 安全限制

### IP 白名单验证

本接口启用了严格的 IP 白名单验证机制：

- ✅ **允许访问**: 只有在系统配置中添加到白名单的 IP 地址才能调用此接口
- ❌ **拒绝访问**: 未在白名单中的 IP 地址将收到 `-32001` 错误响应
- 🔒 **安全保障**: 防止未授权的外部系统访问敏感的发货同步接口

#### IP 白名单配置

请联系系统管理员将您的服务器 IP 地址添加到白名单中。需要提供：
- 服务器公网 IP 地址
- 申请原因和用途说明
- 负责人联系方式

#### IP 被拒绝的错误响应示例

```json
{
    "jsonrpc": "2.0",
    "error": {
        "code": -32001,
        "message": "IP 地址 192.168.1.100 不在白名单中，访问被拒绝"
    },
    "id": 1
}
```

## 业务规则

1. **发货单号唯一性**: 同一个发货单号不能重复提交
2. **商品数量验证**: 所有商品数量必须大于0
3. **必填字段验证**: 所有必填字段不能为空
4. **发货状态**: 同步成功后，发货单状态自动设置为"已发货"(SHIPPED)
5. **来源类型**: 通过此接口同步的订单，来源类型自动设置为"OMS"
6. **IP 访问限制**: 必须从白名单 IP 地址发起请求

## 调用示例 (PHP)

```php
<?php

$client = new \JsonRpc\Client('https://your-api-endpoint.com/json-rpc');

$params = [
    'deliverySn' => 'DELIVER-' . date('Ymd') . '-' . uniqid(),
    'sourceOrderId' => 'ORDER-001',
    'expressCompany' => '顺丰快递',
    'expressCode' => 'SF',
    'expressNumber' => 'SF' . uniqid(),
    'consigneeName' => '张三',
    'consigneePhone' => '13800138000',
    'consigneeAddress' => '上海市浦东新区陆家嘴环路1000号',
    'deliveryItems' => [
        [
            'sku' => 'SKU001',
            'quantity' => 2,
            'productName' => 'iPhone 15 Pro',
        ]
    ]
];

try {
    $result = $client->call('SyncDeliveryInfoFromOms', $params);
    echo "同步成功，发货单ID: " . $result['deliveryOrderId'] . PHP_EOL;
} catch (\Exception $e) {
    echo "同步失败: " . $e->getMessage() . PHP_EOL;
}
```

## 注意事项

### 安全相关
1. 建议使用 HTTPS 协议确保数据传输安全
2. **重要**: 确保服务器 IP 地址已添加到白名单，否则无法访问接口
3. 定期检查和更新 IP 白名单配置

### 开发相关
4. 建议实现重试机制处理网络异常
5. 发货单号建议使用有意义的格式，便于追踪
6. 所有时间字段使用 Asia/Shanghai 时区
7. 在开发和测试环境中，确保测试服务器 IP 也在白名单中

### 故障排除
- 如果收到 `-32001` 错误，请检查请求来源 IP 是否在白名单中
- 联系系统管理员确认当前 IP 白名单配置
- 如有 IP 地址变更，需要及时更新白名单配置