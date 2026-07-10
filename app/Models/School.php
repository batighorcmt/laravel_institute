<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $name_bn
 * @property string|null $code
 * @property string|null $address
 * @property string|null $address_bn
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $logo
 * @property int|null $admission_academic_year_id
 */
class School extends Model
{
    protected $fillable = [
        'name', 'name_bn', 'code', 'eiin', 'mpo_code', 'address', 'address_bn',
        'short_address_bn', 'short_address_en', 'founding_year', 'school_code',
        'agent_token', 'agent_last_seen',
        'phone', 'mobile', 'email', 'website', 'domain',
        'description', 'logo', 'status', 'admissions_enabled',
        'admission_academic_year_id', 'fine_enabled',
        'division_id', 'district_id', 'thana_id', 'union_id',
    ];

    protected $casts = [
        'status' => 'string',
        'admissions_enabled' => 'boolean',
        'fine_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (School $school): void {
            if (! Schema::hasTable('modules')) {
                return;
            }

            $syncData = Module::query()
                ->where('status', 'active')
                ->pluck('id')
                ->mapWithKeys(fn (int $moduleId): array => [$moduleId => ['is_enabled' => true]])
                ->all();

            if ($syncData !== []) {
                $school->modules()->sync($syncData);
            }
        });

        // Clear domains cache when a school is created, updated, or deleted
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('school_domains');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('school_domains');
        });
    }

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(UserSchoolRole::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function publicExams(): HasMany
    {
        return $this->hasMany(PublicExam::class);
    }

    public function admissionAcademicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'admission_academic_year_id');
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function weeklyHolidays(): HasMany
    {
        return $this->hasMany(WeeklyHoliday::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function modules(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'school_modules')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    public function hasModule(string $slug): bool
    {
        return $this->modules()
            ->where('slug', $slug)
            ->where('school_modules.is_enabled', true)
            ->exists();
    }

    // Location relationships
    public function division()
    {
        return $this->belongsTo(\App\Models\Division::class, 'division_id');
    }

    public function district()
    {
        return $this->belongsTo(\App\Models\District::class, 'district_id');
    }

    public function thana()
    {
        return $this->belongsTo(\App\Models\Thana::class, 'thana_id');
    }

    public function union()
    {
        return $this->belongsTo(\App\Models\Union::class, 'union_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
