<?php

namespace App\ApiResource\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterProInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        message: 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre'
    )]
    public ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $organizationName = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['FARM', 'ANIMAL_SHELTER', 'RECYCLING_CENTER', 'NGO'])]
    public ?string $organizationType = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\+[1-9]\d{1,14}$/', message: 'Format de téléphone international invalide')]
    public ?string $phone = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    public ?string $address = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?int $cityId = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 14, max: 14)]
    public ?string $siret = null;
}

