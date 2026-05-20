<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Finance\DepositMoneyAction;
use App\Actions\Finance\ReverseTransactionAction;
use App\Actions\Finance\TransferMoneyAction;
use App\Http\Requests\Finance\DepositRequest;
use App\Http\Requests\Finance\TransferRequest;
use App\Http\Resources\Finance\LedgerResource;
use App\Models\Subledger;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class FinanceController extends Controller
{
    use AuthorizesRequests;

    public function dashboard(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $recentLedgers = $user->ledgers()
            ->with(['subledger'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'recentLedgers' => LedgerResource::collection($recentLedgers),
        ]);
    }

    public function history(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $ledgers = $user->ledgers()
            ->with(['subledger'])
            ->latest()
            ->paginate(10);

        return Inertia::render('finance/History', [
            'ledgers' => LedgerResource::collection($ledgers),
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

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Deposit of R$ {$amount} requested successfully!",
        ]);

        return back();
    }

    public function transfer(TransferRequest $request, TransferMoneyAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var string $email */
        $email = $request->validated('email');
        /** @var float|int|string $amount */
        $amount = $request->validated('amount');

        $toUser = User::query()->where('email', $email)->firstOrFail();

        try {
            $action->execute($user, $toUser, Money::fromDecimal($amount));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Transfer of R$ {$amount} to {$toUser->name} requested successfully!",
        ]);

        return back();
    }

    public function reverse(Subledger $subledger, ReverseTransactionAction $action): RedirectResponse
    {
        $this->authorize('reverse', $subledger);

        try {
            $action->execute($subledger);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Transaction reversal requested successfully!',
        ]);

        return back();
    }
}
