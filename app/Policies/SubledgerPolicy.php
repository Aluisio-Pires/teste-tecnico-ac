<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\FinancialOperation;
use App\Models\Subledger;
use App\Models\User;

final class SubledgerPolicy
{
    public function reverse(User $user, Subledger $subledger): bool
    {
        if ($subledger->type === FinancialOperation::Deposit) {
            $metadata = $subledger->metadata ?? [];
            $originatorId = $metadata['user_id'] ?? 0;

            return (is_numeric($originatorId) ? (int) $originatorId : 0) === $user->id;
        }

        if ($subledger->type === FinancialOperation::Transfer) {
            $metadata = $subledger->metadata ?? [];
            $originatorId = $metadata['from_user_id'] ?? 0;

            return (is_numeric($originatorId) ? (int) $originatorId : 0) === $user->id;
        }

        return false;
    }
}
