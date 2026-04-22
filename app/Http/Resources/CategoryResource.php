<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userPreference = $this->relationLoaded('userPreference')
            ? $this->userPreference
            : null;

        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'direction' => $this->direction->value,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'display_order' => $this->display_order,
            'is_hidden' => $userPreference?->is_hidden ?? false,
            'display_order_override' => $userPreference?->display_order_override,
        ];
    }
}
