<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    protected $fillable = [
        'school_id','title','body','publish_at','expiry_at','status','created_by',
        'audience_type','reply_required','attachment_path'
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'expiry_at' => 'datetime',
        'reply_required' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }

    public function targets()
    {
        return $this->hasMany(NoticeTarget::class);
    }

    public function reads()
    {
        return $this->hasMany(NoticeRead::class);
    }

    public function replies()
    {
        return $this->hasMany(NoticeReply::class);
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

    public function scopeActive($q)
    {
        return $q->where(function($qq){
            $qq->whereNull('expiry_at')->orWhere('expiry_at','>', now());
        });
    }
}
