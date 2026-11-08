<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GeneratePasswordRequest extends FormRequest
{
    private const int DEFAULT_LENGTH = 16;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'length' => ['nullable', 'integer', 'between:8,64'],
        ];
    }

    public function length(): int
    {
        $length = $this->validated('length');

        return $length === null ? self::DEFAULT_LENGTH : (int) $length;
    }
}
