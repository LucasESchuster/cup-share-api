<?php

namespace App\Http\Requests\Auth;

use App\Rules\TurnstileToken;
use Illuminate\Foundation\Http\FormRequest;

class MagicLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'cf_turnstile_response' => ['required', 'string', new TurnstileToken()],
        ];
    }
}
