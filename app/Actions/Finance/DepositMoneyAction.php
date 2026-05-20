<?php

declare(strict_types=1);

namespace App\Actions\Finance;

use App\Jobs\ProcessDepositJob;
use App\Models\User;
use App\ValueObjects\Money;

final class DepositMoneyAction
{
    public function execute(User $user, Money $amount): void
    {
        ProcessDepositJob::dispatch($user, $amount->microns);
    }
}
