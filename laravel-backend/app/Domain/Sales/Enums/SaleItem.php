<?php

namespace App\Domain\Sales\Enums;

enum SaleItem : string
{
    case PRODUCT = 'product';
    case SERVICE = 'service';
}
