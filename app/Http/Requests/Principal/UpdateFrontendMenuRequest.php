<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFrontendMenuRequest extends FormRequest
{
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
            'menus' => ['required', 'array'],
            'menus.*.id' => ['required', 'string', 'max:100'],
            'menus.*.name' => ['required', 'string', 'max:255'],
            'menus.*.items' => ['nullable', 'array'],
            'locations' => ['required', 'array'],
            'locations.header' => ['nullable', 'string', 'max:100'],
            'locations.footer' => ['nullable', 'string', 'max:100'],
        ];
    }
}
