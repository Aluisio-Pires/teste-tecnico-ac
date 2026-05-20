<?php

declare(strict_types=1);

namespace App\Enums;

enum FinancialOperation: string
{
    case Deposit = 'deposit';
    case Transfer = 'transfer';
    case Reversal = 'reversal';
}
