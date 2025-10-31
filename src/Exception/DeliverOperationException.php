<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Exception;

/**
 * 发货操作异常
 */
class DeliverOperationException extends DeliverException
{
    public function __construct(string $message = '发货操作失败', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
