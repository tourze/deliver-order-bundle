<?php

namespace DeliverOrderBundle\Exception;

abstract class DeliverException extends \Exception
{
    /** @var array<string, mixed> */
    protected array $context = [];

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /** @param array<string, mixed> $context */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function withContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }
}
