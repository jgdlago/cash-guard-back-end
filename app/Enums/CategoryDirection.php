<?php

namespace App\Enums;

enum CategoryDirection: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Both = 'both';
}
