<?php

namespace DeliverOrderBundle\Exception;

class InvalidSourceException extends DeliverException
{
    public function __construct(string $message = '发货单来源无效', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
