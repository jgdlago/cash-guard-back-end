<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertCategoryUserPreferenceRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\CategoryUserPreference;

class CategoryUserPreferenceController extends Controller
{
    public function upsert(
        UpsertCategoryUserPreferenceRequest $request,
        Category $category
    ): CategoryResource {
        abort_unless($category->user_id === null || $category->user_id === $request->user()->id, 404);

        CategoryUserPreference::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'category_id' => $category->id,
            ],
            [
                'is_hidden' => $request->validated('is_hidden', false),
                'display_order_override' => $request->validated('display_order_override'),
            ]
        );

        $category->load([
            'userPreference' => fn ($query) => $query->where('user_id', $request->user()->id),
        ]);

        return new CategoryResource($category);
    }

    public function destroy(Category $category): \Illuminate\Http\JsonResponse
    {
        abort_unless($category->user_id === null || $category->user_id === request()->user()->id, 404);

        CategoryUserPreference::query()
            ->where('user_id', request()->user()->id)
            ->where('category_id', $category->id)
            ->delete();

        return response()->json(status: 204);
    }
}
