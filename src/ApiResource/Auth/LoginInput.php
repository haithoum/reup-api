<?php

namespace App\ApiResource\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    public ?string $password = null;
}

