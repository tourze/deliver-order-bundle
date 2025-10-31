<?php

namespace DeliverOrderBundle\Model;

class DeliverContext
{
    private ?string $sourceType = null;

    private ?string $sourceId = null;

    /** @var array<string, string>|null */
    private ?array $consignee = null;

    /** @var array<int, array<string, mixed>> */
    private array $items = [];

    /** @var array<string, mixed> */
    private array $extra = [];

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(?string $sourceId): void
    {
        $this->sourceId = $sourceId;
    }

    /** @return array<string, string>|null */
    public function getConsignee(): ?array
    {
        return $this->consignee;
    }

    /** @param array<string, string>|null $consignee */
    public function setConsignee(?array $consignee): void
    {
        $this->consignee = $consignee;
    }

    public function getConsigneeName(): ?string
    {
        return $this->consignee['name'] ?? null;
    }

    public function getConsigneePhone(): ?string
    {
        return $this->consignee['phone'] ?? null;
    }

    public function getConsigneeAddress(): ?string
    {
        return $this->consignee['address'] ?? null;
    }

    public function getConsigneeRemark(): ?string
    {
        return $this->consignee['remark'] ?? null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @param array<int, array<string, mixed>> $items */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /** @param array<string, mixed> $item */
    public function addItem(array $item): void
    {
        $this->items[] = $item;
    }

    /** @return array<string, mixed> */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /** @param array<string, mixed> $extra */
    public function setExtra(array $extra): void
    {
        $this->extra = $extra;
    }

    public function getExtraValue(string $key, mixed $default = null): mixed
    {
        return $this->extra[$key] ?? $default;
    }

    public function setExtraValue(string $key, mixed $value): void
    {
        $this->extra[$key] = $value;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'consignee' => $this->consignee,
            'items' => $this->items,
            'extra' => $this->extra,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function createFromArray(array $data): self
    {
        $context = new self();

        $context->setSourceTypeFromData($data);
        $context->setSourceIdFromData($data);
        $context->setConsigneeFromData($data);
        $context->setItemsFromData($data);
        $context->setExtraFromData($data);

        return $context;
    }

    /** @param array<string, mixed> $data */
    private function setSourceTypeFromData(array $data): void
    {
        if (isset($data['source_type'])) {
            $this->setSourceType(is_string($data['source_type']) ? $data['source_type'] : null);
        }
    }

    /** @param array<string, mixed> $data */
    private function setSourceIdFromData(array $data): void
    {
        if (isset($data['source_id'])) {
            $this->setSourceId(is_string($data['source_id']) ? $data['source_id'] : null);
        }
    }

    /** @param array<string, mixed> $data */
    private function setConsigneeFromData(array $data): void
    {
        if (!isset($data['consignee'])) {
            return;
        }

        $consignee = $data['consignee'];
        if (!is_array($consignee)) {
            $this->setConsignee(null);

            return;
        }

        $validConsignee = $this->filterStringKeyValuePairs($consignee);
        $this->setConsignee([] !== $validConsignee ? $validConsignee : null);
    }

    /**
     * @param array<mixed> $data
     * @return array<string, string>
     */
    private function filterStringKeyValuePairs(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    private function setItemsFromData(array $data): void
    {
        if (!isset($data['items'])) {
            return;
        }

        $items = $data['items'];
        if (is_array($items)) {
            /** @var list<array<string, mixed>> $validItems */
            $validItems = array_values(array_filter($items, 'is_array'));
            $this->setItems($validItems);
        } else {
            $this->setItems([]);
        }
    }

    /** @param array<string, mixed> $data */
    private function setExtraFromData(array $data): void
    {
        if (isset($data['extra'])) {
            $this->setExtra(is_array($data['extra']) ? $data['extra'] : []);
        }
    }

    public function isValid(): bool
    {
        return [] === $this->getValidationErrors();
    }

    /** @return array<int, string> */
    public function getValidationErrors(): array
    {
        $errors = [];

        if (null === $this->sourceType || '' === $this->sourceType) {
            $errors[] = 'Source type is required';
        }

        if (null === $this->sourceId || '' === $this->sourceId) {
            $errors[] = 'Source ID is required';
        }

        if ([] === $this->items) {
            $errors[] = 'Items cannot be empty';
        } else {
            $errors = array_merge($errors, $this->validateItems());
        }

        return $errors;
    }

    /** @return array<int, string> */
    private function validateItems(): array
    {
        $errors = [];

        foreach ($this->items as $index => $item) {
            $skuId = $item['sku_id'] ?? null;
            if (!isset($item['sku_id']) || '' === $skuId || null === $skuId) {
                $errors[] = "Item {$index}: SKU ID is required";
            }

            if (isset($item['quantity']) && $item['quantity'] <= 0) {
                $errors[] = "Item {$index}: Quantity must be greater than 0";
            }
        }

        return $errors;
    }

    /**
     * 验证和清理明细数据
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function sanitizeItem(array $item): array
    {
        return [
            'sku_id' => $item['sku_id'] ?? null,
            'sku_code' => $item['sku_code'] ?? null,
            'sku_name' => $item['sku_name'] ?? null,
            'quantity' => is_numeric($item['quantity'] ?? 1) ? (int) ($item['quantity'] ?? 1) : 1,
            'batch_no' => $item['batch_no'] ?? null,
            'serial_no' => $item['serial_no'] ?? null,
            'remark' => $item['remark'] ?? null,
        ];
    }

    /**
     * 获取所有明细的总数量
     */
    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $quantity = $item['quantity'] ?? 1;
            $total += is_numeric($quantity) ? (int) $quantity : 1;
        }

        return $total;
    }

    /**
     * 检查是否有收货人信息
     */
    public function hasConsignee(): bool
    {
        return null !== $this->consignee && ($this->consignee['name'] ?? '') !== '';
    }

    /**
     * 清空所有上下文数据
     */
    public function clear(): void
    {
        $this->sourceType = null;
        $this->sourceId = null;
        $this->consignee = null;
        $this->items = [];
        $this->extra = [];
    }
}
