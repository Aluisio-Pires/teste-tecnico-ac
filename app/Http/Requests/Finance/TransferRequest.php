<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransferRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $userId = $user ? $user->id : 0;

        return [
            'email' => [
                'required',
                'email',
                Rule::exists(User::class, 'email')->whereNot('id', $userId),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'The recipient user was not found or is yourself.',
        ];
    }
}
