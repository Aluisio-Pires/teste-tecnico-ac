<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'event_type',
        'is_active',
    ];
}
