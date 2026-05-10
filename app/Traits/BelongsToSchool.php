<?php

namespace App\Traits;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool()
    {
        static::addGlobalScope('school', function (Builder $builder) {
            if (config('school.id')) {
                $builder->where($builder->getModel()->getTable() . '.school_id', config('school.id'));
            }
        });

        static::creating(function ($model) {
            if (config('school.id') && !$model->school_id) {
                $model->school_id = config('school.id');
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
