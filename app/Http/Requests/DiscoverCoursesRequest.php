<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscoverCoursesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'categories' => ['required', 'array', 'min:1', 'max:10'],
            'categories.*' => ['string', 'min:2', 'max:40'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function categories(): array
    {
        /** @var array<int, string> $categories */
        $categories = $this->validated('categories');

        return array_values(array_unique(array_map('trim', $categories)));
    }
}
