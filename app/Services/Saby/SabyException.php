<?php

namespace App\Services\Saby;

class SabyException extends \RuntimeException
{
    private ?array $payload;

    public function __construct(string $message, ?array $payload = null)
    {
        parent::__construct($message);
        $this->payload = $payload;
    }

    public function payload(): ?array
    {
        return $this->payload;
    }
}
