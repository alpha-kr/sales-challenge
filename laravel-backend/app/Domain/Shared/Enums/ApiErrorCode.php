<?php

namespace App\Domain\Shared\Enums;

enum ApiErrorCode: string
{
    // Generic
    case InternalError = 'INTERNAL_ERROR';
    case Unauthorized = 'UNAUTHORIZED';
    case Forbidden = 'FORBIDDEN';
    case ResourceNotFound = 'RESOURCE_NOT_FOUND';
    case ValidationFailed = 'VALIDATION_FAILED';

    // Business
    case InsufficientStock = 'INSUFFICIENT_STOCK';
    case DailyLimitReached = 'DAILY_LIMIT_REACHED';
    case ServiceDependencyFailed = 'SERVICE_DEPENDENCY_FAILED';
    case HasActiveSales = 'HAS_ACTIVE_SALES';
}
