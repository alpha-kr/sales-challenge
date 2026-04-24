<?php

namespace App\Domain\Identity\Enums;

enum LoginType: string
{
    case Session = 'session';
    case Token = 'token';
}
