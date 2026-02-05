<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Only apply if the model has a "status" column
        try {
            if (\Schema::hasColumn($model->getTable(), 'status')) {
                $builder->where($model->getTable() . '.status', 'active');
            }
        } catch (\Throwable $e) {
            // If schema inspection fails, don't break application queries
        }
    }
}
