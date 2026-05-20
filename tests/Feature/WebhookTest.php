<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\FinancialOperationCompleted;
use App\Jobs\SendWebhookJob;
use App\Models\Subledger;
use App\Models\User;
use App\Models\Webhook;
use App\ValueObjects\Money;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('financial operation dispatches webhook job', function (): void {
    Queue::fake([SendWebhookJob::class]);

    $user = User::factory()->create();
    $webhook = Webhook::factory()->create([
        'user_id' => $user->id,
        'event_type' => 'financial_operation.completed',
    ]);

    // Manually trigger the event since the action dispatches a job that wouldn't run in a Queue::fake() environment
    $subledger = Subledger::factory()->create();
    $subledger->ledgers()->create([
        'user_id' => $user->id,
        'amount' => Money::fromDecimal(100),
        'balance_after' => Money::fromDecimal(100),
    ]);

    FinancialOperationCompleted::dispatch($subledger);

    Queue::assertPushed(SendWebhookJob::class, function ($job) use ($webhook) {
        return $job->webhook->id === $webhook->id;
    });
});

test('webhook job sends post request', function (): void {
    Http::fake();

    $webhook = Webhook::factory()->create([
        'url' => 'https://example.com/webhook',
        'event_type' => 'test.event',
    ]);

    $payload = ['foo' => 'bar'];

    $job = new SendWebhookJob($webhook, $payload);
    $job->handle();

    Http::assertSent(function ($request) use ($webhook, $payload) {
        return $request->url() === $webhook->url &&
               $request['event'] === $webhook->event_type &&
               $request['data'] === $payload;
    });
});

test('financial operation does not dispatch inactive webhook', function (): void {
    Queue::fake([SendWebhookJob::class]);

    $user = User::factory()->create();
    Webhook::factory()->create([
        'user_id' => $user->id,
        'event_type' => 'financial_operation.completed',
        'is_active' => false,
    ]);

    $subledger = Subledger::factory()->create();
    $subledger->ledgers()->create([
        'user_id' => $user->id,
        'amount' => Money::fromDecimal(100),
        'balance_after' => Money::fromDecimal(100),
    ]);

    FinancialOperationCompleted::dispatch($subledger);

    Queue::assertNotPushed(SendWebhookJob::class);
});

test('financial operation does not dispatch webhook with different event type', function (): void {
    Queue::fake([SendWebhookJob::class]);

    $user = User::factory()->create();
    Webhook::factory()->create([
        'user_id' => $user->id,
        'event_type' => 'other.event',
    ]);

    $subledger = Subledger::factory()->create();
    $subledger->ledgers()->create([
        'user_id' => $user->id,
        'amount' => Money::fromDecimal(100),
        'balance_after' => Money::fromDecimal(100),
    ]);

    FinancialOperationCompleted::dispatch($subledger);

    Queue::assertNotPushed(SendWebhookJob::class);
});

test('webhook job logs error on exception', function (): void {
    $webhook = Webhook::factory()->create([
        'url' => 'https://example.com/webhook',
    ]);

    Log::shouldReceive('error')->once()->withArgs(function ($message, $context) use ($webhook) {
        return str_contains($message, "Webhook error for URL: {$webhook->url}") &&
               $context['message'] === 'Connection timeout';
    });

    // Trigger an exception by passing an invalid URL or mocking Http to throw
    Http::fake(function (): void {
        throw new Exception('Connection timeout');
    });

    $job = new SendWebhookJob($webhook, []);

    try {
        $job->handle();
    } catch (Exception $e) {
        expect($e->getMessage())->toBe('Connection timeout');
    }
});

test('financial operation dispatches multiple webhooks', function (): void {
    Queue::fake();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $webhook1 = Webhook::factory()->create([
        'user_id' => $user1->id,
        'url' => 'https://example.com/w1',
        'event_type' => 'financial_operation.completed',
        'is_active' => true,
    ]);
    $webhook2 = Webhook::factory()->create([
        'user_id' => $user1->id,
        'url' => 'https://example.com/w2',
        'event_type' => 'financial_operation.completed',
        'is_active' => true,
    ]);
    $webhook3 = Webhook::factory()->create([
        'user_id' => $user2->id,
        'url' => 'https://example.com/w3',
        'event_type' => 'financial_operation.completed',
        'is_active' => true,
    ]);

    $subledger = Subledger::factory()->create();
    $subledger->ledgers()->createMany([
        ['user_id' => $user1->id, 'amount' => Money::fromDecimal(100), 'balance_after' => Money::fromDecimal(100)],
        ['user_id' => $user2->id, 'amount' => Money::fromDecimal(-100), 'balance_after' => Money::fromDecimal(0)],
    ]);

    FinancialOperationCompleted::dispatch($subledger);

    // Filter only SendWebhookJob to be sure
    $pushedWebhooks = Queue::pushed(SendWebhookJob::class);
    expect($pushedWebhooks)->toHaveCount(3);

    Queue::assertPushed(SendWebhookJob::class, fn ($job) => $job->webhook->id === $webhook1->id);
    Queue::assertPushed(SendWebhookJob::class, fn ($job) => $job->webhook->id === $webhook2->id);
    Queue::assertPushed(SendWebhookJob::class, fn ($job) => $job->webhook->id === $webhook3->id);
});

test('financial operation does not dispatch if no webhooks exist', function (): void {
    Queue::fake([SendWebhookJob::class]);

    $user = User::factory()->create();
    // No webhooks created

    $subledger = Subledger::factory()->create();
    $subledger->ledgers()->create([
        'user_id' => $user->id,
        'amount' => Money::fromDecimal(100),
        'balance_after' => Money::fromDecimal(100),
    ]);

    FinancialOperationCompleted::dispatch($subledger);

    Queue::assertNotPushed(SendWebhookJob::class);
});

test('webhook job logs warning on failure response', function (): void {
    $webhook = Webhook::factory()->create([
        'url' => 'https://example.com/webhook',
    ]);

    Log::shouldReceive('warning')->once()->withArgs(function ($message, $context) use ($webhook) {
        return str_contains($message, "Webhook failed for URL: {$webhook->url}") &&
               $context['status'] === 401 &&
               $context['body'] === 'Unauthorized';
    });

    Http::fake([
        '*' => Http::response('Unauthorized', 401),
    ]);

    $job = new SendWebhookJob($webhook, []);

    // It will call release, so we expect an exception in manual run or just let it fail
    try {
        $job->handle();
    } catch (Exception $e) {
        // InteractsWithQueue might throw if not running in worker
    }
});
