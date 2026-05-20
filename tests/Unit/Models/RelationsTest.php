<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Ledger;
use App\Models\Subledger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_relations(): void
    {
        $user = User::factory()->create();
        $subledger = Subledger::factory()->create();
        $ledger = Ledger::factory()->create([
            'user_id' => $user->id,
            'subledger_id' => $subledger->id,
        ]);

        $this->assertInstanceOf(User::class, $ledger->user);
        $this->assertInstanceOf(Subledger::class, $ledger->subledger);
        $this->assertEquals($user->id, $ledger->user->id);
        $this->assertEquals($subledger->id, $ledger->subledger->id);
    }

    public function test_subledger_relations(): void
    {
        $subledger = Subledger::factory()->create();
        Ledger::factory()->create(['subledger_id' => $subledger->id]);

        $this->assertCount(1, $subledger->ledgers);
        $this->assertInstanceOf(Ledger::class, $subledger->ledgers->first());
    }

    public function test_user_relations(): void
    {
        $user = User::factory()->create();
        Ledger::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->ledgers);
        $this->assertInstanceOf(Ledger::class, $user->ledgers->first());
        $this->assertInstanceOf(Ledger::class, $user->latestLedger);
    }
}
