# DeliverOrderBundle 技术设计

## 1. 设计概述

### 1.1 设计目标
- 将发货单功能从 OrderCoreBundle 中独立出来，形成独立可复用的包
- 提供扁平化的 Service 层架构，避免过度分层
- 实现贫血模型的实体设计（只包含数据和 getter/setter）
- 支持多种业务场景的发货需求（订单发货、售后换货、补发等）

### 1.2 架构原则
- **扁平化架构**：使用扁平的 Service 层，不采用 DDD 分层
- **贫血模型**：实体只负责数据存储，业务逻辑在 Service 中
- **配置管理**：通过环境变量 $_ENV 读取配置，不创建 Configuration 类
- **接口优先**：提供清晰的服务接口，不主动创建 HTTP API

### 1.3 技术栈
- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- PHPStan Level 8
- PHPUnit 10+

## 2. 目录结构

```
packages/deliver-order-bundle/
├── src/
│   ├── Entity/              # 贫血模型实体
│   │   ├── DeliverOrder.php
│   │   └── DeliverStock.php
│   ├── Repository/          # 数据访问层
│   │   ├── DeliverOrderRepository.php
│   │   └── DeliverStockRepository.php
│   ├── Service/             # 业务逻辑层（扁平化）
│   │   ├── DeliverOrderService.php
│   │   ├── DeliverValidationService.php
│   │   ├── DeliverProcessorService.php
│   │   └── DeliverContextService.php
│   ├── Event/               # 事件定义
│   │   ├── DeliverOrderCreatedEvent.php
│   │   ├── DeliverOrderShippedEvent.php
│   │   ├── DeliverOrderReceivedEvent.php
│   │   └── DeliverOrderRejectedEvent.php
│   ├── EventSubscriber/     # 事件订阅者
│   │   └── DeliverOrderEventSubscriber.php
│   ├── Interface/           # 公共接口
│   │   ├── DeliverOrderServiceInterface.php
│   │   ├── DeliverSourceInterface.php
│   │   ├── DeliverValidatorInterface.php
│   │   └── DeliverProcessorInterface.php
│   ├── Model/               # 数据传输对象
│   │   └── DeliverContext.php
│   ├── Exception/           # 异常定义
│   │   ├── DeliverException.php
│   │   ├── InvalidSourceException.php
│   │   └── InsufficientStockException.php
│   └── DeliverOrderBundle.php
├── config/
│   └── services.php         # 服务配置（PHP格式）
├── migrations/              # 数据库迁移
├── tests/                   # 测试文件
├── composer.json
└── README.md
```

## 3. 核心组件设计

### 3.1 实体设计（贫血模型）

#### 3.1.1 DeliverOrder 实体
```php
namespace DeliverOrderBundle\Entity;

class DeliverOrder
{
    private int $id;
    private ?string $sn = null;
    private ?string $sourceType = null;
    private ?string $sourceId = null;
    private ?string $expressCompany = null;
    private ?string $expressCode = null;
    private ?string $expressNumber = null;
    private ?string $consigneeName = null;
    private ?string $consigneePhone = null;
    private ?string $consigneeAddress = null;
    private ?string $consigneeRemark = null;
    private ?string $status = 'pending';
    private ?\DateTimeInterface $shippedAt = null;
    private ?string $shippedBy = null;
    private ?\DateTimeInterface $receivedAt = null;
    private ?string $receivedBy = null;
    private ?\DateTimeInterface $rejectedAt = null;
    private ?string $rejectedBy = null;
    private ?string $rejectReason = null;
    private Collection $deliverStocks;
    
    // 只包含 getter/setter 方法，无业务逻辑
}
```

#### 3.1.2 DeliverStock 实体
```php
namespace DeliverOrderBundle\Entity;

class DeliverStock
{
    private int $id;
    private ?DeliverOrder $deliverOrder = null;
    private ?string $skuId = null;
    private ?string $skuCode = null;
    private ?string $skuName = null;
    private int $quantity = 1;
    private ?string $batchNo = null;
    private ?string $serialNo = null;
    private ?string $remark = null;
    private bool $received = false;
    private ?\DateTimeInterface $receivedAt = null;
    
    // 只包含 getter/setter 方法，无业务逻辑
}
```

### 3.2 服务层设计（扁平化）

#### 3.2.1 DeliverOrderService
核心服务类，处理发货单的主要业务逻辑。

```php
namespace DeliverOrderBundle\Service;

class DeliverOrderService implements DeliverOrderServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DeliverOrderRepository $deliverOrderRepository,
        private readonly DeliverStockRepository $deliverStockRepository,
        private readonly DeliverValidationService $validationService,
        private readonly DeliverProcessorService $processorService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}
    
    public function createDeliverOrder(DeliverContext $context): DeliverOrder
    {
        // 1. 验证
        $this->validationService->validate($context);
        
        // 2. 创建发货单
        $deliverOrder = new DeliverOrder();
        $this->populateDeliverOrder($deliverOrder, $context);
        
        // 3. 创建发货明细
        $this->createDeliverStocks($deliverOrder, $context);
        
        // 4. 执行处理器
        $this->processorService->process($deliverOrder, $context);
        
        // 5. 保存
        $this->entityManager->persist($deliverOrder);
        $this->entityManager->flush();
        
        // 6. 触发事件
        $event = new DeliverOrderCreatedEvent($deliverOrder, $context);
        $this->eventDispatcher->dispatch($event);
        
        return $deliverOrder;
    }
    
    public function ship(DeliverOrder $order, array $expressInfo): void
    {
        // 更新快递信息
        $order->setExpressCompany($expressInfo['company'] ?? null);
        $order->setExpressCode($expressInfo['code'] ?? null);
        $order->setExpressNumber($expressInfo['number'] ?? null);
        $order->setStatus('shipped');
        $order->setShippedAt(new \DateTimeImmutable());
        $order->setShippedBy($_ENV['CURRENT_USER_ID'] ?? 'system');
        
        $this->entityManager->flush();
        
        // 触发发货事件
        $event = new DeliverOrderShippedEvent($order);
        $this->eventDispatcher->dispatch($event);
    }
    
    public function receive(DeliverOrder $order, ?string $userId = null): void
    {
        $order->setStatus('received');
        $order->setReceivedAt(new \DateTimeImmutable());
        $order->setReceivedBy($userId ?? $_ENV['CURRENT_USER_ID'] ?? 'system');
        
        // 更新所有明细为已收货
        foreach ($order->getDeliverStocks() as $stock) {
            $stock->setReceived(true);
            $stock->setReceivedAt(new \DateTimeImmutable());
        }
        
        $this->entityManager->flush();
        
        // 触发收货事件
        $event = new DeliverOrderReceivedEvent($order);
        $this->eventDispatcher->dispatch($event);
    }
    
    public function reject(DeliverOrder $order, string $reason, ?string $userId = null): void
    {
        $order->setStatus('rejected');
        $order->setRejectedAt(new \DateTimeImmutable());
        $order->setRejectedBy($userId ?? $_ENV['CURRENT_USER_ID'] ?? 'system');
        $order->setRejectReason($reason);
        
        $this->entityManager->flush();
        
        // 触发拒收事件
        $event = new DeliverOrderRejectedEvent($order, $reason);
        $this->eventDispatcher->dispatch($event);
    }
    
    public function findDeliverOrder(string $sn): ?DeliverOrder
    {
        return $this->deliverOrderRepository->findOneBySn($sn);
    }
    
    public function findBySource(string $sourceType, string $sourceId): array
    {
        return $this->deliverOrderRepository->findBySource($sourceType, $sourceId);
    }
    
    private function populateDeliverOrder(DeliverOrder $order, DeliverContext $context): void
    {
        // 生成发货单号
        $order->setSn($this->generateSn());
        
        // 设置来源信息
        $order->setSourceType($context->getSourceType());
        $order->setSourceId($context->getSourceId());
        
        // 设置收货人信息
        $consignee = $context->getConsignee();
        if ($consignee) {
            $order->setConsigneeName($consignee['name'] ?? null);
            $order->setConsigneePhone($consignee['phone'] ?? null);
            $order->setConsigneeAddress($consignee['address'] ?? null);
            $order->setConsigneeRemark($consignee['remark'] ?? null);
        }
        
        $order->setStatus('pending');
    }
    
    private function createDeliverStocks(DeliverOrder $order, DeliverContext $context): void
    {
        foreach ($context->getItems() as $item) {
            $stock = new DeliverStock();
            $stock->setDeliverOrder($order);
            $stock->setSkuId($item['sku_id']);
            $stock->setSkuCode($item['sku_code'] ?? null);
            $stock->setSkuName($item['sku_name'] ?? null);
            $stock->setQuantity($item['quantity'] ?? 1);
            $stock->setBatchNo($item['batch_no'] ?? null);
            $stock->setSerialNo($item['serial_no'] ?? null);
            $stock->setRemark($item['remark'] ?? null);
            
            $order->addDeliverStock($stock);
            $this->entityManager->persist($stock);
        }
    }
    
    private function generateSn(): string
    {
        // 使用雪花ID生成器
        $snowflakeId = $_ENV['SNOWFLAKE_SERVICE'] ?? null;
        if ($snowflakeId) {
            return 'D' . $snowflakeId;
        }
        
        // 降级方案：使用时间戳 + 随机数
        return 'D' . date('YmdHis') . mt_rand(1000, 9999);
    }
}
```

#### 3.2.2 DeliverValidationService
验证服务，处理发货前的各种验证逻辑。

```php
namespace DeliverOrderBundle\Service;

class DeliverValidationService
{
    private array $validators = [];
    
    public function addValidator(DeliverValidatorInterface $validator): void
    {
        $this->validators[] = $validator;
    }
    
    public function validate(DeliverContext $context): void
    {
        // 基础验证
        $this->validateBasic($context);
        
        // 执行注册的验证器
        foreach ($this->validators as $validator) {
            $validator->validate($context);
        }
    }
    
    private function validateBasic(DeliverContext $context): void
    {
        if (!$context->getSourceType() || !$context->getSourceId()) {
            throw new InvalidSourceException('发货单来源信息不完整');
        }
        
        if (empty($context->getItems())) {
            throw new DeliverException('发货明细不能为空');
        }
        
        foreach ($context->getItems() as $item) {
            if (!isset($item['sku_id'])) {
                throw new DeliverException('SKU ID 不能为空');
            }
            
            if (($item['quantity'] ?? 0) <= 0) {
                throw new DeliverException('发货数量必须大于0');
            }
        }
    }
}
```

#### 3.2.3 DeliverProcessorService
处理器服务，执行发货相关的业务处理。

```php
namespace DeliverOrderBundle\Service;

class DeliverProcessorService
{
    private array $processors = [];
    
    public function addProcessor(DeliverProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }
    
    public function process(DeliverOrder $order, DeliverContext $context): void
    {
        foreach ($this->processors as $processor) {
            $processor->process($order, $context);
        }
    }
}
```

#### 3.2.4 DeliverContextService
上下文服务，管理发货上下文数据。

```php
namespace DeliverOrderBundle\Service;

class DeliverContextService
{
    public function createContext(array $data): DeliverContext
    {
        $context = new DeliverContext();
        
        // 设置来源信息
        $context->setSourceType($data['source_type'] ?? null);
        $context->setSourceId($data['source_id'] ?? null);
        
        // 设置收货人信息
        if (isset($data['consignee'])) {
            $context->setConsignee($data['consignee']);
        }
        
        // 设置发货明细
        if (isset($data['items'])) {
            $context->setItems($data['items']);
        }
        
        // 设置扩展数据
        if (isset($data['extra'])) {
            $context->setExtra($data['extra']);
        }
        
        return $context;
    }
}
```

### 3.3 Repository 设计

#### 3.3.1 DeliverOrderRepository
```php
namespace DeliverOrderBundle\Repository;

class DeliverOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliverOrder::class);
    }
    
    public function findOneBySn(string $sn): ?DeliverOrder
    {
        return $this->findOneBy(['sn' => $sn]);
    }
    
    public function findBySource(string $sourceType, string $sourceId): array
    {
        return $this->findBy([
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
        ]);
    }
    
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }
    
    public function countByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }
    
    public function existsBySn(string $sn): bool
    {
        return $this->count(['sn' => $sn]) > 0;
    }
}
```

### 3.4 接口定义

#### 3.4.1 DeliverOrderServiceInterface
```php
namespace DeliverOrderBundle\Interface;

interface DeliverOrderServiceInterface
{
    public function createDeliverOrder(DeliverContext $context): DeliverOrder;
    
    public function ship(DeliverOrder $order, array $expressInfo): void;
    
    public function receive(DeliverOrder $order, ?string $userId = null): void;
    
    public function reject(DeliverOrder $order, string $reason, ?string $userId = null): void;
    
    public function findDeliverOrder(string $sn): ?DeliverOrder;
    
    public function findBySource(string $sourceType, string $sourceId): array;
}
```

#### 3.4.2 DeliverSourceInterface
```php
namespace DeliverOrderBundle\Interface;

interface DeliverSourceInterface
{
    public function getSourceType(): string;
    
    public function getSourceId(): string;
    
    public function canDeliver(): bool;
    
    public function getDeliverItems(): array;
    
    public function getConsigneeInfo(): array;
}
```

#### 3.4.3 DeliverValidatorInterface
```php
namespace DeliverOrderBundle\Interface;

interface DeliverValidatorInterface
{
    public function validate(DeliverContext $context): void;
    
    public function supports(DeliverContext $context): bool;
}
```

#### 3.4.4 DeliverProcessorInterface
```php
namespace DeliverOrderBundle\Interface;

interface DeliverProcessorInterface
{
    public function process(DeliverOrder $order, DeliverContext $context): void;
    
    public function supports(DeliverContext $context): bool;
    
    public function getPriority(): int;
}
```

### 3.5 事件设计

#### 3.5.1 DeliverOrderCreatedEvent
```php
namespace DeliverOrderBundle\Event;

class DeliverOrderCreatedEvent
{
    public function __construct(
        private readonly DeliverOrder $deliverOrder,
        private readonly DeliverContext $context,
    ) {}
    
    public function getDeliverOrder(): DeliverOrder
    {
        return $this->deliverOrder;
    }
    
    public function getContext(): DeliverContext
    {
        return $this->context;
    }
}
```

## 4. 服务配置

### 4.1 services.php
```php
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use DeliverOrderBundle\Service\DeliverOrderService;
use DeliverOrderBundle\Service\DeliverValidationService;
use DeliverOrderBundle\Service\DeliverProcessorService;
use DeliverOrderBundle\Service\DeliverContextService;
use DeliverOrderBundle\Interface\DeliverOrderServiceInterface;

return function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();
    
    // 自动加载所有服务
    $services->load('DeliverOrderBundle\\', '../src/')
        ->exclude('../src/{Entity,Migrations,Tests}');
    
    // 服务别名
    $services->alias(DeliverOrderServiceInterface::class, DeliverOrderService::class);
    
    // 验证器和处理器标签
    $services->instanceof(DeliverValidatorInterface::class)
        ->tag('deliver_order.validator');
    
    $services->instanceof(DeliverProcessorInterface::class)
        ->tag('deliver_order.processor');
    
    // 注入验证器
    $services->set(DeliverValidationService::class)
        ->call('addValidator', [tagged_iterator('deliver_order.validator')]);
    
    // 注入处理器
    $services->set(DeliverProcessorService::class)
        ->call('addProcessor', [tagged_iterator('deliver_order.processor')]);
};
```

## 5. 数据库设计

### 5.1 deliver_order 表
```sql
CREATE TABLE deliver_order (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sn VARCHAR(100) UNIQUE NOT NULL COMMENT '发货单号',
    source_type VARCHAR(50) NOT NULL COMMENT '来源类型',
    source_id VARCHAR(100) NOT NULL COMMENT '来源ID',
    express_company VARCHAR(50) COMMENT '快递公司',
    express_code VARCHAR(30) COMMENT '快递公司编码',
    express_number VARCHAR(100) COMMENT '快递单号',
    consignee_name VARCHAR(100) COMMENT '收货人姓名',
    consignee_phone VARCHAR(50) COMMENT '收货人电话',
    consignee_address VARCHAR(500) COMMENT '收货人地址',
    consignee_remark TEXT COMMENT '收货备注',
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态',
    shipped_at DATETIME COMMENT '发货时间',
    shipped_by VARCHAR(100) COMMENT '发货操作人',
    received_at DATETIME COMMENT '收货时间',
    received_by VARCHAR(100) COMMENT '收货人',
    rejected_at DATETIME COMMENT '拒收时间',
    rejected_by VARCHAR(100) COMMENT '拒收操作人',
    reject_reason TEXT COMMENT '拒收原因',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    INDEX idx_sn (sn),
    INDEX idx_source (source_type, source_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='发货单表';
```

### 5.2 deliver_stock 表
```sql
CREATE TABLE deliver_stock (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    deliver_order_id BIGINT NOT NULL COMMENT '发货单ID',
    sku_id VARCHAR(100) NOT NULL COMMENT 'SKU ID',
    sku_code VARCHAR(100) COMMENT 'SKU编码',
    sku_name VARCHAR(255) COMMENT 'SKU名称',
    quantity INT NOT NULL DEFAULT 1 COMMENT '数量',
    batch_no VARCHAR(100) COMMENT '批次号',
    serial_no VARCHAR(100) COMMENT '序列号',
    remark TEXT COMMENT '备注',
    received BOOLEAN DEFAULT FALSE COMMENT '是否收货',
    received_at DATETIME COMMENT '收货时间',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (deliver_order_id) REFERENCES deliver_order(id) ON DELETE CASCADE,
    INDEX idx_deliver_order (deliver_order_id),
    INDEX idx_sku (sku_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='发货明细表';
```

## 6. 扩展机制

### 6.1 自定义验证器
```php
class OrderSourceValidator implements DeliverValidatorInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}
    
    public function validate(DeliverContext $context): void
    {
        if (!$this->supports($context)) {
            return;
        }
        
        $order = $this->orderRepository->find($context->getSourceId());
        if (!$order) {
            throw new InvalidSourceException('订单不存在');
        }
        
        if (!in_array($order->getStatus(), ['paid', 'part_shipped'])) {
            throw new InvalidSourceException('订单状态不允许发货');
        }
    }
    
    public function supports(DeliverContext $context): bool
    {
        return $context->getSourceType() === 'order';
    }
}
```

### 6.2 自定义处理器
```php
class StockDeductionProcessor implements DeliverProcessorInterface
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}
    
    public function process(DeliverOrder $order, DeliverContext $context): void
    {
        if (!$this->supports($context)) {
            return;
        }
        
        foreach ($order->getDeliverStocks() as $stock) {
            $this->stockService->deduct(
                $stock->getSkuId(),
                $stock->getQuantity(),
                $stock->getBatchNo()
            );
        }
    }
    
    public function supports(DeliverContext $context): bool
    {
        return $_ENV['DELIVER_ORDER_DEDUCT_STOCK'] === 'true';
    }
    
    public function getPriority(): int
    {
        return 100;
    }
}
```

## 7. 使用示例

### 7.1 创建发货单
```php
// 创建发货上下文
$context = $contextService->createContext([
    'source_type' => 'order',
    'source_id' => '12345',
    'consignee' => [
        'name' => '张三',
        'phone' => '13800138000',
        'address' => '北京市朝阳区xxx街道',
        'remark' => '请放门卫处',
    ],
    'items' => [
        [
            'sku_id' => 'SKU001',
            'sku_code' => 'ABC123',
            'sku_name' => '商品A',
            'quantity' => 2,
        ],
        [
            'sku_id' => 'SKU002',
            'sku_code' => 'DEF456',
            'sku_name' => '商品B',
            'quantity' => 1,
        ],
    ],
]);

// 创建发货单
$deliverOrder = $deliverOrderService->createDeliverOrder($context);
```

### 7.2 发货操作
```php
$deliverOrderService->ship($deliverOrder, [
    'company' => '顺丰速运',
    'code' => 'SF',
    'number' => 'SF1234567890',
]);
```

### 7.3 确认收货
```php
$deliverOrderService->receive($deliverOrder, 'user123');
```

### 7.4 拒收处理
```php
$deliverOrderService->reject($deliverOrder, '商品破损', 'user123');
```

## 8. 性能优化

### 8.1 查询优化
- 使用索引优化常用查询（sn、source、status）
- 使用 Doctrine 的 Extra Lazy 加载优化关联查询
- 批量操作使用 bulk insert/update

### 8.2 缓存策略
```php
// 通过环境变量控制缓存
if ($_ENV['DELIVER_ORDER_CACHE_ENABLED'] === 'true') {
    // 缓存常用查询结果
    $cacheKey = 'deliver_order_' . $sn;
    $ttl = (int)($_ENV['DELIVER_ORDER_CACHE_TTL'] ?? 3600);
    // 缓存实现...
}
```

### 8.3 异步处理
```php
// 通过环境变量控制异步
if ($_ENV['DELIVER_ORDER_ASYNC_EVENTS'] === 'true') {
    // 使用 Symfony Messenger 异步处理事件
}
```

## 9. 测试策略

### 9.1 单元测试
- 测试所有 Service 的公共方法
- 测试 Repository 的查询方法
- 测试事件的触发和处理

### 9.2 集成测试
- 测试完整的发货流程
- 测试验证器和处理器的集成
- 测试事件订阅者的响应

### 9.3 测试覆盖率要求
- 单元测试覆盖率 ≥ 90%
- 集成测试覆盖主要业务流程
- PHPStan Level 8 零错误

## 10. 需求映射

### 10.1 EARS 需求对照表

| 需求编号 | 需求描述 | 实现组件 |
|---------|---------|---------|
| 2.1.1 | DeliverOrder 实体管理 | Entity/DeliverOrder.php |
| 2.1.1 | 雪花ID生成 | DeliverOrderService::generateSn() |
| 2.1.1 | 快递信息记录 | DeliverOrderService::ship() |
| 2.1.1 | 收货人信息 | DeliverOrder 实体属性 |
| 2.1.1 | 状态驱动的时间记录 | Service 方法中的状态更新 |
| 2.1.2 | DeliverStock 实体管理 | Entity/DeliverStock.php |
| 2.1.2 | SKU验证 | DeliverValidationService |
| 2.2.1 | 来源接口定义 | Interface/DeliverSourceInterface |
| 2.2.2 | 发货上下文 | Model/DeliverContext.php |
| 2.3.1 | 发货流程 | DeliverOrderService::createDeliverOrder() |
| 2.3.1 | 发货事件 | Event/DeliverOrderCreatedEvent |
| 2.3.2 | 收货流程 | DeliverOrderService::receive() |
| 2.3.2 | 收货事件 | Event/DeliverOrderReceivedEvent |
| 2.3.3 | 拒收流程 | DeliverOrderService::reject() |
| 2.3.3 | 拒收事件 | Event/DeliverOrderRejectedEvent |
| 2.4.1 | 服务接口 | Interface/DeliverOrderServiceInterface |
| 2.5.1 | 验证器扩展 | Interface/DeliverValidatorInterface |
| 2.5.2 | 处理器扩展 | Interface/DeliverProcessorInterface |

## 11. 迁移计划

### 11.1 从 OrderCoreBundle 迁移
1. 安装 DeliverOrderBundle
2. 运行数据库迁移脚本
3. 配置环境变量
4. 实现自定义验证器和处理器
5. 更新调用代码使用新的服务接口
6. 数据迁移（可选）

### 11.2 向后兼容
- 提供适配器模式支持旧接口
- 保留原有数据结构的映射
- 提供迁移工具和脚本

## 12. 部署要求

### 12.1 环境变量配置
```bash
# 发货单号生成
DELIVER_ORDER_SN_PREFIX=D
SNOWFLAKE_SERVICE=snowflake_id_generator

# 功能开关
DELIVER_ORDER_CHECK_SOURCE=true
DELIVER_ORDER_CHECK_STOCK=true
DELIVER_ORDER_DEDUCT_STOCK=true

# 事件处理
DELIVER_ORDER_ASYNC_EVENTS=false

# 缓存配置
DELIVER_ORDER_CACHE_ENABLED=false
DELIVER_ORDER_CACHE_TTL=3600

# 用户信息
CURRENT_USER_ID=system
```

### 12.2 依赖要求
- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- tourze/doctrine-snowflake-bundle
- tourze/doctrine-timestamp-bundle
- tourze/doctrine-user-bundle
- tourze/doctrine-ip-bundle

## 13. 安全考虑

### 13.1 权限控制
- 通过 Symfony Security 控制操作权限
- 记录所有关键操作的操作人和时间

### 13.2 数据验证
- 输入数据严格验证
- 防止 SQL 注入
- 敏感信息加密存储（如需要）

### 13.3 审计日志
- 记录所有状态变更
- 保留操作人信息
- 支持操作追溯

## 14. 监控指标

### 14.1 业务指标
- 发货单创建数量
- 发货成功率
- 收货确认率
- 拒收率

### 14.2 性能指标
- 发货单创建响应时间
- 数据库查询性能
- 事件处理延迟

### 14.3 异常监控
- 验证失败次数
- 库存不足异常
- 系统错误日志

## 15. 文档维护

### 15.1 API 文档
- 使用 PHPDoc 注释
- 提供使用示例
- 说明扩展点

### 15.2 升级指南
- 版本变更说明
- 破坏性变更提示
- 迁移步骤

### 15.3 最佳实践
- 推荐的使用模式
- 性能优化建议
- 常见问题解答