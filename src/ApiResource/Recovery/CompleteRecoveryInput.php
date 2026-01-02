<?php

namespace App\ApiResource\Recovery;

use Symfony\Component\Validator\Constraints as Assert;

final class CompleteRecoveryInput
{
    #[Assert\NotBlank]
    #[Assert\Range(min: 0.1, max: 110)]
    public ?float $actualWeightKg = null;
}

