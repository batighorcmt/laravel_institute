<?php

namespace App\Http\Requests\Principal;

use App\Models\CmsPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCmsPageRequest extends FormRequest
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
        $pageId = $this->route('page')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('cms_pages', 'slug')->where('school_id', $schoolId)->ignore($pageId),
            ],
            'content' => ['nullable', 'string'],
            'content_mode' => ['nullable', Rule::in([CmsPage::MODE_DYNAMIC, CmsPage::MODE_STATIC])],
            'data_source' => ['nullable', 'string', Rule::in(\App\Services\DynamicPageContentService::SUPPORTED_SOURCES)],
            'status' => ['required', Rule::in([CmsPage::STATUS_DRAFT, CmsPage::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'max:2048'],
            'robots' => ['nullable', 'string', 'max:100'],
        ];
    }
}
