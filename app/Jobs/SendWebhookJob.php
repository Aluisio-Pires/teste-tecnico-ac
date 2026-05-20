<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Webhook;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 600];

    public function __construct(
        public Webhook $webhook,
        public array $payload,
    ) {}

    public function handle(): void
    {
        try {
            $response = Http::timeout(5)
                ->post($this->webhook->url, [
                    'event' => $this->webhook->event_type,
                    'data' => $this->payload,
                    'timestamp' => now()->toIso8601String(),
                ]);

            if ($response->failed()) {
                Log::warning("Webhook failed for URL: {$this->webhook->url}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->release(60);
            }
        } catch (Exception $e) {
            Log::error("Webhook error for URL: {$this->webhook->url}", [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
