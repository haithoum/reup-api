<?php

namespace App\ApiResource\Deposit;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateDepositInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public ?string $citizenQrCode = null;

    #[Assert\NotBlank]
    #[Assert\Range(min: 0.1, max: 50)]
    public ?float $weightKg = null;
}

