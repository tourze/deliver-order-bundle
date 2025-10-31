<?php

namespace DeliverOrderBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum SourceType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case ORDER = 'order';
    case CONTRACT = 'contract';
    case AFTERSALES = 'aftersales';
    case REPLENISHMENT = 'replenishment';
    case OMS = 'oms';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::ORDER => '订单',
            self::CONTRACT => '合同',
            self::AFTERSALES => '售后',
            self::REPLENISHMENT => '补货',
            self::OMS => 'OMS系统',
            self::OTHER => '其他',
        };
    }
}
