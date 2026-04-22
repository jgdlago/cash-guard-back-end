<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertCategoryUserPreferenceRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\CategoryUserPreference;
use App\Support\FinancialAudit;

class CategoryUserPreferenceController extends Controller
{
    public function upsert(
        UpsertCategoryUserPreferenceRequest $request,
        Category $category
    ): CategoryResource {
        abort_unless($category->user_id === null || $category->user_id === $request->user()->id, 404);

        $preference = CategoryUserPreference::query()->firstOrNew(
            [
                'user_id' => $request->user()->id,
                'category_id' => $category->id,
            ]
        );
        $before = $preference->exists ? FinancialAudit::attributes($preference) : null;

        $preference->fill([
            'is_hidden' => $request->validated('is_hidden', false),
            'display_order_override' => $request->validated('display_order_override'),
        ]);
        $preference->save();

        FinancialAudit::log(
            $request->user()->id,
            $preference,
            $before === null ? 'category_preference.created' : 'category_preference.updated',
            $before,
            FinancialAudit::attributes($preference),
            ['source' => 'api']
        );

        $category->load([
            'userPreference' => fn ($query) => $query->where('user_id', $request->user()->id),
        ]);

        return new CategoryResource($category);
    }

    public function destroy(Category $category): \Illuminate\Http\JsonResponse
    {
        abort_unless($category->user_id === null || $category->user_id === request()->user()->id, 404);

        $preference = CategoryUserPreference::query()
            ->where('user_id', request()->user()->id)
            ->where('category_id', $category->id)
            ->first();

        if ($preference !== null) {
            FinancialAudit::log(
                request()->user()->id,
                $preference,
                'category_preference.deleted',
                FinancialAudit::attributes($preference),
                null,
                ['source' => 'api']
            );

            $preference->delete();
        }

        return response()->json(status: 204);
    }
}
