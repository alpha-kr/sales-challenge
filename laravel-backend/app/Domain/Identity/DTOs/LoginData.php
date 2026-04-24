<?php

namespace App\Domain\Identity\DTOs;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class LoginData extends Data
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        #[Required]
        public readonly string $password,
    ) {}
}
