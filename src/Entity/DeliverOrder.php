<?php

namespace DeliverOrderBundle\Entity;

use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Repository\DeliverOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: DeliverOrderRepository::class)]
#[ORM\Table(name: 'deliver_order', options: ['comment' => '发货单表'])]
#[ORM\Index(name: 'deliver_order_idx_source', columns: ['source_type', 'source_id'])]
class DeliverOrder implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true, nullable: true, options: ['comment' => '发货单号'])]
    #[Assert\Length(max: 100)]
    #[IndexColumn]
    private ?string $sn = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '来源类型'], enumType: SourceType::class)]
    #[Assert\Choice(callback: [SourceType::class, 'cases'])]
    private ?SourceType $sourceType = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '来源ID'])]
    #[Assert\Length(max: 100)]
    private ?string $sourceId = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '快递公司'])]
    #[Assert\Length(max: 50)]
    private ?string $expressCompany = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true, options: ['comment' => '快递公司编码'])]
    #[Assert\Length(max: 30)]
    private ?string $expressCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '快递单号'])]
    #[Assert\Length(max: 100)]
    private ?string $expressNumber = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '收货人姓名'])]
    #[Assert\Length(max: 100)]
    private ?string $consigneeName = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '收货人电话'])]
    #[Assert\Length(max: 50)]
    private ?string $consigneePhone = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '收货地址'])]
    #[Assert\Length(max: 500)]
    private ?string $consigneeAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '收货备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $consigneeRemark = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'pending', 'comment' => '状态'], enumType: DeliverOrderStatus::class)]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [DeliverOrderStatus::class, 'cases'])]
    #[IndexColumn]
    private DeliverOrderStatus $status = DeliverOrderStatus::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '发货时间'])]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeInterface $shippedTime = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '发货人'])]
    #[Assert\Length(max: 100)]
    private ?string $shippedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '收货时间'])]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeInterface $receivedTime = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '收货人'])]
    #[Assert\Length(max: 100)]
    private ?string $receivedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '拒收时间'])]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeInterface $rejectedTime = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '拒收操作人'])]
    #[Assert\Length(max: 100)]
    private ?string $rejectedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '拒收原因'])]
    #[Assert\Length(max: 65535)]
    private ?string $rejectReason = null;

    // createdBy 和 updatedBy 字段现在由 BlameableAware trait 提供

    /**
     * @var Collection<int, DeliverStock>
     */
    #[ORM\OneToMany(targetEntity: DeliverStock::class, mappedBy: 'deliverOrder', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $deliverStocks;

    public function __construct()
    {
        $this->deliverStocks = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->sn ?? 'DeliverOrder#' . $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSn(): ?string
    {
        return $this->sn;
    }

    public function setSn(?string $sn): void
    {
        $this->sn = $sn;
    }

    public function getSourceType(): ?SourceType
    {
        return $this->sourceType;
    }

    public function setSourceType(?SourceType $sourceType): void
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

    public function getExpressCompany(): ?string
    {
        return $this->expressCompany;
    }

    public function setExpressCompany(?string $expressCompany): void
    {
        $this->expressCompany = $expressCompany;
    }

    public function getExpressCode(): ?string
    {
        return $this->expressCode;
    }

    public function setExpressCode(?string $expressCode): void
    {
        $this->expressCode = $expressCode;
    }

    public function getExpressNumber(): ?string
    {
        return $this->expressNumber;
    }

    public function setExpressNumber(?string $expressNumber): void
    {
        $this->expressNumber = $expressNumber;
    }

    public function getConsigneeName(): ?string
    {
        return $this->consigneeName;
    }

    public function setConsigneeName(?string $consigneeName): void
    {
        $this->consigneeName = $consigneeName;
    }

    public function getConsigneePhone(): ?string
    {
        return $this->consigneePhone;
    }

    public function setConsigneePhone(?string $consigneePhone): void
    {
        $this->consigneePhone = $consigneePhone;
    }

    public function getConsigneeAddress(): ?string
    {
        return $this->consigneeAddress;
    }

    public function setConsigneeAddress(?string $consigneeAddress): void
    {
        $this->consigneeAddress = $consigneeAddress;
    }

    public function getConsigneeRemark(): ?string
    {
        return $this->consigneeRemark;
    }

    public function setConsigneeRemark(?string $consigneeRemark): void
    {
        $this->consigneeRemark = $consigneeRemark;
    }

    public function getStatus(): DeliverOrderStatus
    {
        return $this->status;
    }

    public function setStatus(DeliverOrderStatus $status): void
    {
        $this->status = $status;
    }

    public function getShippedTime(): ?\DateTimeInterface
    {
        return $this->shippedTime;
    }

    public function setShippedTime(?\DateTimeInterface $shippedTime): void
    {
        $this->shippedTime = $shippedTime;
    }

    public function getShippedBy(): ?string
    {
        return $this->shippedBy;
    }

    public function setShippedBy(?string $shippedBy): void
    {
        $this->shippedBy = $shippedBy;
    }

    public function getReceivedTime(): ?\DateTimeInterface
    {
        return $this->receivedTime;
    }

    public function setReceivedTime(?\DateTimeInterface $receivedTime): void
    {
        $this->receivedTime = $receivedTime;
    }

    public function getReceivedBy(): ?string
    {
        return $this->receivedBy;
    }

    public function setReceivedBy(?string $receivedBy): void
    {
        $this->receivedBy = $receivedBy;
    }

    public function getRejectedTime(): ?\DateTimeInterface
    {
        return $this->rejectedTime;
    }

    public function setRejectedTime(?\DateTimeInterface $rejectedTime): void
    {
        $this->rejectedTime = $rejectedTime;
    }

    public function getRejectedBy(): ?string
    {
        return $this->rejectedBy;
    }

    public function setRejectedBy(?string $rejectedBy): void
    {
        $this->rejectedBy = $rejectedBy;
    }

    public function getRejectReason(): ?string
    {
        return $this->rejectReason;
    }

    public function setRejectReason(?string $rejectReason): void
    {
        $this->rejectReason = $rejectReason;
    }

    // createdBy 和 updatedBy 的 getter/setter 方法现在由 BlameableAware trait 提供

    /**
     * @return Collection<int, DeliverStock>
     */
    public function getDeliverStocks(): Collection
    {
        return $this->deliverStocks;
    }

    public function addDeliverStock(DeliverStock $deliverStock): void
    {
        if (!$this->deliverStocks->contains($deliverStock)) {
            $this->deliverStocks->add($deliverStock);
            $deliverStock->setDeliverOrder($this);
        }
    }

    public function removeDeliverStock(DeliverStock $deliverStock): void
    {
        if ($this->deliverStocks->removeElement($deliverStock)) {
            if ($deliverStock->getDeliverOrder() === $this) {
                $deliverStock->setDeliverOrder(null);
            }
        }
    }
}
