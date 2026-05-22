<?php

namespace App\Http\Requests\Principal;

use App\Models\CmsPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCmsPostRequest extends FormRequest
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
        $schoolId = $this->route('school')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('cms_posts', 'slug')->where('school_id', $schoolId),
            ],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'max:4096'],
            'status' => ['required', Rule::in([CmsPost::STATUS_DRAFT, CmsPost::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'max:2048'],
            'robots' => ['nullable', 'string', 'max:100'],
        ];
    }
}
