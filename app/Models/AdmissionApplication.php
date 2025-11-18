<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int|null $academic_year_id
 * @property string $app_id
 * @property string|null $name_en
 * @property string|null $name_bn
 * @property string|null $father_name_en
 * @property string|null $father_name_bn
 * @property string|null $mother_name_en
 * @property string|null $mother_name_bn
 * @property string|null $guardian_name_en
 * @property string|null $guardian_name_bn
 * @property string|null $gender
 * @property string|null $religion
 * @property string|null $blood_group
 * @property \Carbon\CarbonInterface|null $dob
 * @property string|null $birth_reg_no
 * @property string|null $photo
 * @property string|null $mobile
 * @property string|null $present_address
 * @property string|null $permanent_address
 * @property string|null $last_school
 * @property string|null $result
 * @property string|null $pass_year
 * @property string|null $achievement
 * @property string|null $payment_status
 * @property string|null $status
 * @property string|null $cancellation_reason
 * @property \Carbon\CarbonInterface|null $accepted_at
 * @property int|null $admission_roll_no
 * @property \Carbon\CarbonInterface|null $exam_datetime
 */
class AdmissionApplication extends Model
{
    protected $fillable = [
        'school_id','academic_year_id','app_id','applicant_name','phone','class_name','data','status',
        'name_en','name_bn','father_name_en','father_name_bn','mother_name_en','mother_name_bn',
        'guardian_name_en','guardian_name_bn','gender','religion','blood_group','dob','birth_reg_no','photo',
        'mobile','present_address','permanent_address','last_school','result','pass_year','achievement','payment_status',
        'cancellation_reason'
    ];

    protected $casts = [
        'data' => 'array',
        'status' => 'string',
        'dob' => 'date',
        'payment_status' => 'string',
        'accepted_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class,'academic_year_id');
    }

    public function payments()
    {
        return $this->hasMany(AdmissionPayment::class,'admission_application_id');
    }

    public function scopeAccepted($q)
    {
        return $q->whereNotNull('accepted_at');
    }
}
