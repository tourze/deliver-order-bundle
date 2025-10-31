<?php

namespace DeliverOrderBundle\Entity;

use DeliverOrderBundle\Repository\DeliverStockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: DeliverStockRepository::class)]
#[ORM\Table(name: 'deliver_stock', options: ['comment' => '发货库存表'])]
class DeliverStock implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DeliverOrder::class, inversedBy: 'deliverStocks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DeliverOrder $deliverOrder = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => 'SKU ID'])]
    #[Assert\Length(max: 100)]
    #[IndexColumn]
    private ?string $skuId = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => 'SKU编码'])]
    #[Assert\Length(max: 100)]
    private ?string $skuCode = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'SKU名称'])]
    #[Assert\Length(max: 255)]
    private ?string $skuName = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1, 'comment' => '数量'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $quantity = 1;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '批次号'])]
    #[Assert\Length(max: 100)]
    private ?string $batchNo = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '序列号'])]
    #[Assert\Length(max: 100)]
    private ?string $serialNo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remark = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否已收货'])]
    #[Assert\NotNull]
    private bool $received = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '收货时间'])]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeInterface $receivedTime = null;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return $this->skuName ?? $this->skuCode ?? 'DeliverStock#' . $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeliverOrder(): ?DeliverOrder
    {
        return $this->deliverOrder;
    }

    public function setDeliverOrder(?DeliverOrder $deliverOrder): void
    {
        $this->deliverOrder = $deliverOrder;
    }

    public function getSkuId(): ?string
    {
        return $this->skuId;
    }

    public function setSkuId(?string $skuId): void
    {
        $this->skuId = $skuId;
    }

    public function getSkuCode(): ?string
    {
        return $this->skuCode;
    }

    public function setSkuCode(?string $skuCode): void
    {
        $this->skuCode = $skuCode;
    }

    public function getSkuName(): ?string
    {
        return $this->skuName;
    }

    public function setSkuName(?string $skuName): void
    {
        $this->skuName = $skuName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getBatchNo(): ?string
    {
        return $this->batchNo;
    }

    public function setBatchNo(?string $batchNo): void
    {
        $this->batchNo = $batchNo;
    }

    public function getSerialNo(): ?string
    {
        return $this->serialNo;
    }

    public function setSerialNo(?string $serialNo): void
    {
        $this->serialNo = $serialNo;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function isReceived(): bool
    {
        return $this->received;
    }

    public function setReceived(bool $received): void
    {
        $this->received = $received;
    }

    public function getReceivedTime(): ?\DateTimeInterface
    {
        return $this->receivedTime;
    }

    public function setReceivedTime(?\DateTimeInterface $receivedTime): void
    {
        $this->receivedTime = $receivedTime;
    }
}
