<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    protected $fillable = [
        'school_id','title','body','publish_at','status','created_by'
    ];

    protected $casts = [
        'publish_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }

    // Scopes
    public function scopePublished($q)
    {
        return $q->where('status','published')->where(function($qq){
            $qq->whereNull('publish_at')->orWhere('publish_at','<=', now());
        });
    }

    public function scopeForSchool($q,$schoolId)
    {
        return $q->where(function($qq) use ($schoolId){
            $qq->whereNull('school_id')->orWhere('school_id',$schoolId);
        });
    }
}
