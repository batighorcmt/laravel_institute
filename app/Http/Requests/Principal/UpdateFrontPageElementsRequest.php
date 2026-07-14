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
            'committee_members' => ['nullable', 'array'],
            'committee_members.*.serial' => ['nullable', 'string', 'max:20'],
            'committee_members.*.name' => ['nullable', 'string', 'max:255'],
            'committee_members.*.designation' => ['nullable', 'string', 'max:255'],
            'committee_members.*.mobile' => ['nullable', 'string', 'max:50'],
            'committee_members.*.address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
