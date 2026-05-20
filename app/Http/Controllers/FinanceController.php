<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Finance\DepositMoneyAction;
use App\Actions\Finance\ReverseTransactionAction;
use App\Actions\Finance\TransferMoneyAction;
use App\Enums\FinancialOperation;
use App\Http\Requests\Finance\DepositRequest;
use App\Http\Requests\Finance\TransferRequest;
use App\Models\Ledger;
use App\Models\Subledger;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class FinanceController extends Controller
{
    public function dashboard(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $recentLedgers = $user->ledgers()
            ->with(['subledger'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($ledger) {
                /** @var Ledger $ledger */
                $ledger->subledger->was_reversed = Subledger::query()
                    ->where('type', FinancialOperation::Reversal)
                    ->whereJsonContains('metadata->original_subledger_id', $ledger->subledger_id)
                    ->exists();

                return $ledger;
            });

        return Inertia::render('Dashboard', [
            'recentLedgers' => $recentLedgers,
        ]);
    }

    public function history(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $ledgers = $user->ledgers()
            ->with(['subledger.ledgers.user'])
            ->latest()
            ->paginate(10)
            ->through(function ($ledger) {
                /** @var Ledger $ledger */
                $ledger->subledger->was_reversed = Subledger::query()
                    ->where('type', FinancialOperation::Reversal)
                    ->whereJsonContains('metadata->original_subledger_id', $ledger->subledger_id)
                    ->exists();

                return $ledger;
            });

        return Inertia::render('finance/History', [
            'ledgers' => $ledgers,
        ]);
    }

    public function showDeposit(): Response
    {
        return Inertia::render('finance/Deposit');
    }

    public function showTransfer(): Response
    {
        return Inertia::render('finance/Transfer');
    }

    public function deposit(DepositRequest $request, DepositMoneyAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $amount = $request->integer('amount');

        if (!is_numeric($amount)) {
            return back()->withErrors(['amount' => 'Invalid amount.']);
        }

        $action->execute($user, Money::fromDecimal((float) $amount));

        return back()->with('status', 'Deposit successful!');
    }

    public function transfer(TransferRequest $request, TransferMoneyAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $email = $request->string('email')->value();
        $amount = $request->string('amount')->value();

        if (!is_string($email) || !is_numeric($amount)) {
            return back()->withErrors(['amount' => 'Invalid data provided.']);
        }

        $toUser = User::query()->where('email', $email)->firstOrFail();

        try {
            $action->execute($user, $toUser, Money::fromDecimal((float) $amount));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Transfer successful!');
    }

    public function reverse(Subledger $subledger, ReverseTransactionAction $action, Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $isOriginator = false;

        if ($subledger->type === FinancialOperation::Deposit) {
            $metadata = $subledger->metadata ?? [];
            $originatorId = $metadata['user_id'] ?? 0;
            $isOriginator = (is_numeric($originatorId) ? (int) $originatorId : 0) === $user->id;
        } elseif ($subledger->type === FinancialOperation::Transfer) {
            $metadata = $subledger->metadata ?? [];
            $originatorId = $metadata['from_user_id'] ?? 0;
            $isOriginator = (is_numeric($originatorId) ? (int) $originatorId : 0) === $user->id;
        }

        if (!$isOriginator) {
            return back()->withErrors(['error' => 'You can only reverse transactions you originated.']);
        }

        try {
            $action->execute($subledger);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('status', 'Transaction reversed successful!');
    }
}
