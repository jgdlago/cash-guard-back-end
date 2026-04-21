<?php

namespace App\Enums;

enum PaymentSourceType: string
{
    case Cash = 'cash';
    case BankAccount = 'bank_account';
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case Wallet = 'wallet';
    case Other = 'other';
}
