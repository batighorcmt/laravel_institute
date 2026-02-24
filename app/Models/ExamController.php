<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\School;
use App\Models\User;

class ExamController extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
