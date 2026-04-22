<?php

namespace App\Http\Controllers;

use App\Enums\CategoryKind;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $user = request()->user();
        $withHidden = request()->boolean('filter.with_hidden');

        $categories = QueryBuilder::for(
            Category::query()
                ->with([
                    'userPreference' => fn ($query) => $query->where('user_id', $user->id),
                ])
                ->where(function ($query) use ($user): void {
                    $query->whereNull('user_id')
                        ->orWhere('user_id', $user->id);
                })
                ->when(! $withHidden, function ($query) use ($user): void {
                    $query->whereDoesntHave('preferences', function ($preferenceQuery) use ($user): void {
                        $preferenceQuery
                            ->where('user_id', $user->id)
                            ->where('is_hidden', true);
                    });
                })
        )
            ->allowedFilters([
                AllowedFilter::exact('kind'),
                AllowedFilter::exact('direction'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::callback('scope', function ($query, $value): void {
                    if ($value === 'system') {
                        $query->whereNull('user_id');
                    }

                    if ($value === 'custom') {
                        $query->whereNotNull('user_id');
                    }
                }),
                AllowedFilter::callback('with_hidden', function (): void {
                    // The actual behavior is handled before QueryBuilder instantiation.
                }),
                AllowedFilter::partial('name'),
            ])
            ->allowedSorts(['display_order', 'name', 'created_at'])
            ->when(! request()->filled('sort'), function ($query) use ($user): void {
                $query
                    ->orderByRaw(
                        'COALESCE((SELECT cup.display_order_override FROM category_user_preferences cup WHERE cup.category_id = categories.id AND cup.user_id = ? LIMIT 1), categories.display_order) ASC',
                        [$user->id]
                    )
                    ->orderBy('name');
            })
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $parentId = $request->validated('parent_id');

        if ($parentId !== null) {
            $exists = Category::query()
                ->whereKey($parentId)
                ->where(function ($query) use ($request): void {
                    $query->whereNull('user_id')
                        ->orWhere('user_id', $request->user()->id);
                })
                ->exists();

            abort_unless($exists, 422, 'Invalid parent category.');
        }

        $category = Category::create([
            'user_id' => $request->user()->id,
            'kind' => CategoryKind::Custom,
            'direction' => $request->validated('direction'),
            'name' => $request->validated('name'),
            'slug' => Str::slug($request->validated('name')),
            'color' => $request->validated('color'),
            'icon' => $request->validated('icon'),
            'parent_id' => $parentId,
            'is_active' => true,
            'display_order' => 0,
        ]);

        return new CategoryResource($category);
    }
}
