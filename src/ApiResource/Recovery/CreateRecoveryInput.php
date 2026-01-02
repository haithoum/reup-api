<?php

namespace App\ApiResource\Recovery;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateRecoveryInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public ?string $merchantQrCode = null;

    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 100)]
    public ?float $plannedWeightKg = null;

    #[Assert\NotBlank]
    #[Assert\DateTime]
    public ?string $plannedDate = null;
}

