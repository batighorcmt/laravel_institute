<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFrontPageElementsRequest extends FormRequest
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
            'mission.title' => ['nullable', 'string', 'max:255'],
            'mission.body' => ['nullable', 'string'],
            'vision.title' => ['nullable', 'string', 'max:255'],
            'vision.body' => ['nullable', 'string'],
            'blog_section.title' => ['nullable', 'string', 'max:255'],
            'blog_section.subtitle' => ['nullable', 'string', 'max:500'],
            'achievements' => ['nullable', 'array'],
            'achievements.*.year' => ['nullable', 'string', 'max:50'],
            'achievements.*.title' => ['nullable', 'string', 'max:255'],
            'achievements.*.description' => ['nullable', 'string'],
            'achievements.*.icon' => ['nullable', 'string', 'max:100'],
            'achievements.*.color' => ['nullable', 'string', 'max:100'],
            'facilities' => ['nullable', 'array'],
            'facilities.*.title' => ['nullable', 'string', 'max:255'],
            'facilities.*.description' => ['nullable', 'string'],
            'facilities.*.icon' => ['nullable', 'string', 'max:100'],
            'facilities.*.color' => ['nullable', 'string', 'max:100'],
            'gallery_existing' => ['nullable', 'array'],
            'gallery_existing.*' => ['nullable', 'string', 'max:500'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }
}
