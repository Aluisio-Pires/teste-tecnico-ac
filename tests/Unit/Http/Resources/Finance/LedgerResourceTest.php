<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources\Finance;

use App\Enums\FinancialOperation;
use App\Http\Resources\Finance\LedgerResource;
use App\Models\Ledger;
use App\Models\Subledger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class LedgerResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_transforms_ledger_to_array(): void
    {
        $user = User::factory()->create();
        $subledger = Subledger::factory()->create([
            'type' => FinancialOperation::Deposit,
            'metadata' => ['user_id' => $user->id],
        ]);
        $ledger = Ledger::factory()->create([
            'subledger_id' => $subledger->id,
            'user_id' => $user->id,
            'amount' => 100,
            'balance_after' => 100,
        ]);

        $resource = new LedgerResource($ledger);
        $request = Request::create('/dashboard', 'GET');

        $array = $resource->toArray($request);

        $this->assertEquals($ledger->id, $array['id']);
        $this->assertEquals(0.0001, $array['amount']); // 100 microns
        $this->assertEquals(0.0001, $array['balance_after']);
        $this->assertEquals('deposit', $array['subledger']['type']);
        $this->assertFalse($array['subledger']['was_reversed']);
    }

    public function test_it_detects_reversed_transaction(): void
    {
        $user = User::factory()->create();
        $subledger = Subledger::factory()->create([
            'type' => FinancialOperation::Deposit,
            'metadata' => ['user_id' => $user->id],
        ]);
        $ledger = Ledger::factory()->create([
            'subledger_id' => $subledger->id,
            'user_id' => $user->id,
        ]);

        Subledger::factory()->create([
            'type' => FinancialOperation::Reversal,
            'metadata' => ['original_subledger_id' => $subledger->id],
        ]);

        $resource = new LedgerResource($ledger);
        $array = $resource->toArray(Request::create('/', 'GET'));

        $this->assertTrue($array['subledger']['was_reversed']);
    }

    public function test_it_returns_false_for_reversal_type(): void
    {
        $user = User::factory()->create();
        $subledger = Subledger::factory()->create([
            'type' => FinancialOperation::Reversal,
        ]);
        $ledger = Ledger::factory()->create([
            'subledger_id' => $subledger->id,
            'user_id' => $user->id,
        ]);

        $resource = new LedgerResource($ledger);
        $array = $resource->toArray(Request::create('/', 'GET'));

        $this->assertFalse($array['subledger']['was_reversed']);
    }
}
