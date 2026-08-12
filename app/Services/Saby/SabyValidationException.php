<?php

namespace App\Services\Saby;

class SabyValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('Не хватает данных для формирования накладной');
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
