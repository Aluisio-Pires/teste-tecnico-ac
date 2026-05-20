<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Finance\DepositMoneyAction;
use App\Actions\Finance\ReverseTransactionAction;
use App\Actions\Finance\TransferMoneyAction;
use App\Http\Requests\Finance\DepositRequest;
use App\Http\Requests\Finance\TransferRequest;
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
                $ledger->subledger->was_reversed = Subledger::where('type', \App\Enums\FinancialOperation::Reversal)
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
                $ledger->subledger->was_reversed = Subledger::where('type', \App\Enums\FinancialOperation::Reversal)
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
        /** @var float|int|string $amount */
        $amount = $request->validated('amount');

        $action->execute($user, Money::fromDecimal($amount));

        return back()->with('status', 'Deposit successful!');
    }

    public function transfer(TransferRequest $request, TransferMoneyAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var string $email */
        $email = $request->validated('email');
        /** @var float|int|string $amount */
        $amount = $request->validated('amount');

        $toUser = User::where('email', $email)->firstOrFail();

        try {
            $action->execute($user, $toUser, Money::fromDecimal($amount));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Transfer successful!');
    }

    public function reverse(Subledger $subledger, ReverseTransactionAction $action, Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Check if the user is the originator of the transaction
        // For transfers, the originator is the one who was debited (from_user_id)
        // For deposits, the originator is the user who made the deposit
        $isOriginator = false;

        if ($subledger->type->value === 'deposit') {
            $isOriginator = (int) ($subledger->metadata['user_id'] ?? 0) === $user->id;
        } elseif ($subledger->type->value === 'transfer') {
            $isOriginator = (int) ($subledger->metadata['from_user_id'] ?? 0) === $user->id;
        }

        if (! $isOriginator) {
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
