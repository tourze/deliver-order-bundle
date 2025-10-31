<?php

namespace DeliverOrderBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum DeliverOrderStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case SHIPPED = 'shipped';
    case RECEIVED = 'received';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待发货',
            self::SHIPPED => '已发货',
            self::RECEIVED => '已收货',
            self::REJECTED => '已拒收',
        };
    }
}
