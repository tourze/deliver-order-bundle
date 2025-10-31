<?php

namespace DeliverOrderBundle\Exception;

class InsufficientStockException extends DeliverException
{
    public function __construct(string $message = '库存不足', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
