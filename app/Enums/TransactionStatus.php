<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
}
