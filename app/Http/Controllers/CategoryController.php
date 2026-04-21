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

        $categories = QueryBuilder::for(
            Category::query()
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
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
                AllowedFilter::partial('name'),
            ])
            ->allowedSorts(['display_order', 'name', 'created_at'])
            ->defaultSort('display_order', 'name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $category = Category::create([
            'user_id' => $request->user()->id,
            'kind' => CategoryKind::Custom,
            'direction' => $request->validated('direction'),
            'name' => $request->validated('name'),
            'slug' => Str::slug($request->validated('name')),
            'color' => $request->validated('color'),
            'icon' => $request->validated('icon'),
            'parent_id' => $request->validated('parent_id'),
            'is_active' => true,
            'display_order' => 0,
        ]);

        return new CategoryResource($category);
    }
}
