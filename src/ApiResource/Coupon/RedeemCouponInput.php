<?php

namespace App\ApiResource\Coupon;

use Symfony\Component\Validator\Constraints as Assert;

final class RedeemCouponInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public ?string $couponQrCode = null;
}

