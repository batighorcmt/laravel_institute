<?php

namespace App\Jobs;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Group;
use App\Models\AcademicYear;
use App\Models\User;
use App\Models\Role;
use App\Models\UserSchoolRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ProcessStudentBulkImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;
    public $schoolId;
    public $importId;

    public function __construct(string $filePath, int $schoolId, string $importId)
    {
        $this->filePath = $filePath;
        $this->schoolId = $schoolId;
        $this->importId = $importId;
    }

    public function handle()
    {
        $cacheKey = "bulk_import:{$this->importId}:status";
        $reportKey = "bulk_import:{$this->importId}:report";
        Cache::put($cacheKey, ['status' => 'running', 'processed' => 0, 'total' => 0], 3600);

        $failures = [];
        $success = 0;
        $total = 0;
        $warnings = [];

        // Read file from storage
        $diskPath = storage_path('app/' . $this->filePath);
        if (!file_exists($diskPath)) {
            Cache::put($cacheKey, ['status' => 'failed', 'message' => 'File not found'], 3600);
            return;
        }

        $ext = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));

        // Load rows
        $rows = [];
        $header = null;
        if (in_array($ext, ['xlsx','xls','ods'])) {
            // use maatwebsite toArray
            $import = new class implements \Maatwebsite\Excel\Concerns\ToArray { public $sheets = []; public function array(array $array){ $this->sheets[] = $array; } };
            $sheets = \Maatwebsite\Excel\Facades\Excel::toArray($import, $diskPath);
            if (!empty($sheets) && !empty($sheets[0])) {
                $header = array_map(function($h){ return trim(strtolower($h)); }, $sheets[0][0]);
                $rows = array_slice($sheets[0],1);
            }
        } else {
            $handle = fopen($diskPath, 'r');
            if ($handle) {
                while (($data = fgetcsv($handle)) !== false) {
                    if (!$header) { $header = array_map(function($h){ return trim(strtolower($h)); }, $data); continue; }
                    $rows[] = $data;
                }
                fclose($handle);
            }
        }

        $total = count($rows);
        Cache::put($cacheKey, ['status' => 'running', 'processed' => 0, 'total' => $total], 3600);

        // Preload class/section/group lists for fuzzy matching
        $classes = SchoolClass::where('school_id', $this->schoolId)->get()->mapWithKeys(fn($c)=>[$c->id=>$c->name])->toArray();

        // Load sections grouped by class_id: [class_id => [section_id => section_name]]
        $sectionsByClass = Section::where('school_id', $this->schoolId)
            ->get()
            ->groupBy('class_id')
            ->map(fn($sections) => $sections->mapWithKeys(fn($s) => [$s->id => $s->name])->toArray())
            ->toArray();

        // Load groups grouped by class_id so matching happens within the same class
        $groupsByClass = Group::where('school_id', $this->schoolId)
            ->get()
            ->groupBy('class_id')
            ->map(fn($gs) => $gs->mapWithKeys(fn($g) => [$g->id => $g->name])->toArray())
            ->toArray();

        $rowNo = 1;
        foreach ($rows as $cols) {
            $rowNo++;
            $assoc = [];
            foreach ($header as $i => $colName) {
                $raw = $cols[$i] ?? null;
                // Preserve raw cell value. Don't coerce numeric cells here;
                // we'll parse date columns explicitly after the row is read.
                if ($raw instanceof \DateTimeInterface) {
                    $assoc[$colName] = $raw->format('Y-m-d');
                } else {
                    $assoc[$colName] = is_null($raw) ? null : $raw;
                }
            }

            // Normalize numeric-like fields to guard against hidden chars from Excel/CSV
            // Note: keep `enroll_roll_no` out of the generic strip so we can preserve the
            // original input and apply more permissive parsing later.
            foreach (['enroll_academic_year','enroll_class_id','enroll_section_id','enroll_group_id'] as $nf) {
                if (array_key_exists($nf, $assoc) && $assoc[$nf] !== null) {
                    if (is_string($assoc[$nf])) {
                        $digits = preg_replace('/[^0-9]/u', '', $assoc[$nf]);
                        if ($digits !== '') { $assoc[$nf] = $digits; }
                    }
                }
            }

            // Preserve guardian phone exactly as provided (trim only)
            if (array_key_exists('guardian_phone', $assoc)) {
                $assoc['guardian_phone'] = is_null($assoc['guardian_phone']) ? null : trim((string)$assoc['guardian_phone']);
            }

            // Minimal required fields per new spec
            $errors = [];
            if (empty($assoc['student_name_en'])) $errors[] = 'student_name_en required';
            if (empty($assoc['enroll_academic_year'])) $errors[] = 'enroll_academic_year required';
            if (empty($assoc['enroll_roll_no'])) $errors[] = 'enroll_roll_no required';
            if (empty($assoc['enroll_class_id']) && empty($assoc['enroll_class_name'])) $errors[] = 'enroll_class_id or enroll_class_name required';
            // status optional; default active

            if (!empty($errors)) {
                $failures[] = array_merge($assoc, ['__error' => implode('; ', $errors)]);
                Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures], 3600);
                Cache::put($cacheKey, ['status' => 'running', 'processed' => $rowNo-1, 'total' => $total], 3600);
                continue;
            }

            // Parse dates robustly: accept DateTime objects, Excel serials,
            // and common string formats (ISO or d/m/Y).
            $dob = null;
            if (array_key_exists('date_of_birth', $assoc) && $assoc['date_of_birth'] !== null && $assoc['date_of_birth'] !== '') {
                $rawDob = $assoc['date_of_birth'];
                if ($rawDob instanceof \DateTimeInterface) {
                    $dob = $rawDob->format('Y-m-d');
                } elseif (is_numeric($rawDob)) {
                    if (class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
                        try {
                            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDob);
                            $dob = $dt->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $dob = null;
                        }
                    } else {
                        // Manual Excel serial -> Unix timestamp conversion fallback
                        try {
                            $serial = (float)$rawDob;
                            $ts = ($serial - 25569) * 86400; // days between 1899-12-30 and 1970-01-01
                            $dob = Carbon::createFromTimestampUTC((int)round($ts))->toDateString();
                        } catch (\Throwable $e) {
                            $dob = null;
                        }
                    }
                }
                if (empty($dob)) {
                    // try flexible string parsing including several common formats
                    $formats = ['Y-m-d','Y/m/d','d/m/Y','d-m-Y','d.m.Y','j M Y','j F Y'];
                    foreach ($formats as $fmt) {
                        try {
                            $d = Carbon::createFromFormat($fmt, (string)$rawDob);
                            if ($d) { $dob = $d->toDateString(); break; }
                        } catch (\Throwable $_) { }
                    }
                    if (empty($dob)) {
                        try {
                            $ts = strtotime((string)$rawDob);
                            if ($ts !== false) { $dob = date('Y-m-d', $ts); }
                        } catch (\Throwable $_) { $dob = null; }
                    }
                }
            }

            $admission_date = null;
            if (array_key_exists('admission_date', $assoc) && $assoc['admission_date'] !== null && $assoc['admission_date'] !== '') {
                $rawAdm = $assoc['admission_date'];
                if ($rawAdm instanceof \DateTimeInterface) {
                    $admission_date = $rawAdm->format('Y-m-d');
                } elseif (is_numeric($rawAdm)) {
                    if (class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
                        try {
                            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawAdm);
                            $admission_date = $dt->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $admission_date = null;
                        }
                    } else {
                        try {
                            $serial = (float)$rawAdm;
                            $ts = ($serial - 25569) * 86400;
                            $admission_date = Carbon::createFromTimestampUTC((int)round($ts))->toDateString();
                        } catch (\Throwable $e) {
                            $admission_date = null;
                        }
                    }
                }
                if (empty($admission_date)) {
                    $formats = ['Y-m-d','Y/m/d','d/m/Y','d-m-Y','d.m.Y','j M Y','j F Y'];
                    foreach ($formats as $fmt) {
                        try {
                            $d = Carbon::createFromFormat($fmt, (string)$rawAdm);
                            if ($d) { $admission_date = $d->toDateString(); break; }
                        } catch (\Throwable $_) { }
                    }
                    if (empty($admission_date)) {
                        try {
                            $ts = strtotime((string)$rawAdm);
                            if ($ts !== false) { $admission_date = date('Y-m-d', $ts); }
                        } catch (\Throwable $_) { $admission_date = null; }
                    }
                }
            }

            if (!empty($assoc['date_of_birth']) && empty($dob)) {
                $warnings[] = array_merge($assoc, ['__warning' => 'date_of_birth parse failed']);
                Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures, 'warnings'=>$warnings], 3600);
            }
            if (!empty($assoc['admission_date']) && empty($admission_date)) {
                $warnings[] = array_merge($assoc, ['__warning' => 'admission_date parse failed']);
                Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures, 'warnings'=>$warnings], 3600);
            }

            // Resolve class for student_id generation
            $enClass = null;
            if (!empty($assoc['enroll_class_id']) && is_numeric($assoc['enroll_class_id'])) {
                $enClass = intval($assoc['enroll_class_id']);
            }
            if (empty($enClass) && !empty($assoc['enroll_class_name'])) {
                $enClass = $this->findBestMatch($classes, $assoc['enroll_class_name']);
            }
            $classModel = $enClass ? SchoolClass::find($enClass) : null;
            $classNumeric = $classModel ? $classModel->numeric_value : 1;

            $studentData = [
                'student_name_en' => $assoc['student_name_en'] ?? null,
                'student_name_bn' => $assoc['student_name_bn'] ?? null,
                'date_of_birth' => $dob,
                'gender' => $assoc['gender'] ?? null,
                'blood_group' => $assoc['blood_group'] ?? null,
                'religion' => $assoc['religion'] ?? null,
                'father_name' => $assoc['father_name'] ?? null,
                'mother_name' => $assoc['mother_name'] ?? null,
                'father_name_bn' => $assoc['father_name_bn'] ?? ($assoc['father_name'] ?? null),
                'mother_name_bn' => $assoc['mother_name_bn'] ?? ($assoc['mother_name'] ?? null),
                'guardian_phone' => $assoc['guardian_phone'] ?? null,
                'guardian_relation' => $assoc['guardian_relation'] ?? null,
                'guardian_name_en' => $assoc['guardian_name_en'] ?? null,
                'guardian_name_bn' => $assoc['guardian_name_bn'] ?? null,
                // Address components - prefer component fields, fallback to composed address
                'present_village' => $assoc['present_village'] ?? null,
                'present_para_moholla' => $assoc['present_para_moholla'] ?? null,
                'present_post_office' => $assoc['present_post_office'] ?? null,
                'present_upazilla' => $assoc['present_upazilla'] ?? null,
                'present_district' => $assoc['present_district'] ?? null,
                'permanent_village' => $assoc['permanent_village'] ?? null,
                'permanent_para_moholla' => $assoc['permanent_para_moholla'] ?? null,
                'permanent_post_office' => $assoc['permanent_post_office'] ?? null,
                'permanent_upazilla' => $assoc['permanent_upazilla'] ?? null,
                'permanent_district' => $assoc['permanent_district'] ?? null,
                // previous education
                'previous_school' => $assoc['previous_school'] ?? null,
                'pass_year' => $assoc['pass_year'] ?? null,
                'previous_result' => $assoc['previous_result'] ?? null,
                'previous_remarks' => $assoc['previous_remarks'] ?? null,
                'admission_date' => $admission_date,
                'status' => $assoc['status'] ?? 'active',
                'school_id' => $this->schoolId,
                'class_id' => null,
                'student_id' => Student::generateStudentId($this->schoolId, $classNumeric),
            ];

            try {
                $student = Student::create($studentData);
            } catch (\Throwable $e) {
                $failures[] = array_merge($assoc, ['__error'=>'create student failed: '.$e->getMessage()]);
                Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures], 3600);
                Cache::put($cacheKey, ['status' => 'running', 'processed' => $rowNo-1, 'total' => $total], 3600);
                continue;
            }

            // Ensure a user exists for this student (username = student_id, default password 123456)
            try {
                if (!empty($student->student_id)) {
                    DB::transaction(function() use ($student) {
                        $user = User::where('username', $student->student_id)->first();
                        if (!$user) {
                            $user = User::create([
                                'name' => $student->student_name_en ?: $student->student_name_bn ?: 'Student',
                                'username' => $student->student_id,
                                'email' => $student->student_id . '@institute.local',
                                'password' => Hash::make('123456'),
                            ]);
                        }
                        // link student->user
                        $student->update(['user_id' => $user->id]);
                        // assign parent role for this school
                        $parentRole = Role::where('name', Role::PARENT)->first();
                        if ($parentRole) {
                            UserSchoolRole::firstOrCreate([
                                'user_id' => $user->id,
                                'school_id' => $this->schoolId,
                                'role_id' => $parentRole->id,
                            ], [
                                'status' => 'active',
                            ]);
                        }
                    });
                }
            } catch (\Throwable $e) {
                // non-fatal: record but continue
                $failures[] = array_merge($assoc, ['__error' => 'ensure user failed: '.$e->getMessage()]);
                Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures], 3600);
            }

            // Enrollment mapping: fuzzy match names if provided
            $enYear = $assoc['enroll_academic_year'] ?? null;
            $enClass = null; $enSection = null; $enGroup = null; $enRoll = null;
            if (!empty($assoc['enroll_class_id']) && is_numeric($assoc['enroll_class_id'])) { $enClass = intval($assoc['enroll_class_id']); }
            if (empty($enClass) && !empty($assoc['enroll_class_name'])) { $enClass = $this->findBestMatch($classes, $assoc['enroll_class_name']); }

            // Get sections for this specific class (sections are class-specific)
            $sectionsForClass = $enClass && isset($sectionsByClass[$enClass]) ? $sectionsByClass[$enClass] : [];
            // Get groups for this specific class (groups are class-specific)
            $groupsForClass = $enClass && isset($groupsByClass[$enClass]) ? $groupsByClass[$enClass] : [];

            if (!empty($assoc['enroll_section_id']) && is_numeric($assoc['enroll_section_id'])) { $enSection = intval($assoc['enroll_section_id']); }
            if (empty($enSection) && !empty($assoc['enroll_section_name'])) { $enSection = $this->findBestMatch($sectionsForClass, $assoc['enroll_section_name']); }
            if (!empty($assoc['enroll_group_id']) && is_numeric($assoc['enroll_group_id'])) { $enGroup = intval($assoc['enroll_group_id']); }
            if (empty($enGroup) && !empty($assoc['enroll_group_name'])) { $enGroup = $this->findBestMatch($groupsForClass, $assoc['enroll_group_name']); }
            // Accept enroll_roll_no if provided; try to extract digits but do not
            // discard the provided value too early. This ensures an uploaded roll
            // number is used instead of skipping enrollment and letting another
            // process auto-generate it.
            if (array_key_exists('enroll_roll_no', $assoc) && $assoc['enroll_roll_no'] !== null && trim((string)$assoc['enroll_roll_no']) !== '') {
                $rawRoll = (string)$assoc['enroll_roll_no'];
                // Try to extract digits (common case), but fall back to numeric check.
                $digits = preg_replace('/[^0-9]/u', '', $rawRoll);
                if ($digits !== '') {
                    $enRoll = intval($digits);
                } elseif (is_numeric($rawRoll)) {
                    $enRoll = intval($rawRoll);
                } else {
                    $enRoll = null; // cannot derive a numeric roll
                }
            }

            // Validate existence of section/group IDs for this class, else null them (foreign key safety)
            if ($enSection && !array_key_exists($enSection, $sectionsForClass)) { $enSection = null; }
            if ($enGroup && !array_key_exists($enGroup, $groupsForClass)) { $enGroup = null; }

            // If mandatory enrollment fields missing or class name not resolved -> log failure
            if ($enYear && !$enClass) {
                $failures[] = array_merge($assoc, ['__error'=>'class name/id not resolved']);
                Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures], 3600);
                Cache::put($cacheKey, ['status' => 'running', 'processed' => $rowNo-1, 'total' => $total], 3600);
            }

            if ($enYear && $enClass && $enRoll) {
                $class = SchoolClass::find($enClass);
                if ($class && !$class->usesGroups()) { $enGroup = null; }
                // duplicate check
                // Resolve academic_year_id: only use existing academic years
                $yearNumber = intval($enYear);
                $yearModel = AcademicYear::where('school_id', $this->schoolId)
                    ->where('name', (string)$yearNumber)
                    ->first();
                if (!$yearModel) {
                    $failures[] = array_merge($assoc, ['__error' => "academic year {$yearNumber} not found"]);
                    Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures, 'warnings'=>$warnings], 3600);
                    Cache::put($cacheKey, ['status' => 'running', 'processed' => $rowNo-1, 'total' => $total], 3600);
                    continue;
                }
                $dupQuery = StudentEnrollment::where('school_id',$this->schoolId)->where('academic_year_id', $yearModel->id)->where('class_id', $enClass);
                if ($enSection) { $dupQuery->where('section_id', $enSection); } else { $dupQuery->whereNull('section_id'); }
                if ($enGroup) { $dupQuery->where('group_id', $enGroup); } else { $dupQuery->whereNull('group_id'); }
                $dupQuery->where('roll_no', $enRoll);
                if ($dupQuery->exists()) {
                    $failures[] = array_merge($assoc, ['__error'=>"Enrollment roll {$enRoll} exists in {$enYear}"]);
                    Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures], 3600);
                    Cache::put($cacheKey, ['status' => 'running', 'processed' => $rowNo-1, 'total' => $total], 3600);
                } else {
                    try {
                        StudentEnrollment::create([
                            'student_id' => $student->id,
                            'school_id' => $this->schoolId,
                            'academic_year_id' => $yearModel->id,
                            'class_id' => $enClass,
                            'section_id' => $enSection ?: null,
                            'group_id' => $enGroup ?: null,
                            'roll_no' => $enRoll,
                            'status' => 'active'
                        ]);
                    } catch (\Throwable $e) {
                        $failures[] = array_merge($assoc, ['__error'=>'enrollment failed: '.$e->getMessage()]);
                        Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures], 3600);
                    }
                }
            }

            $success++;
            Cache::put($cacheKey, ['status' => 'running', 'processed' => $rowNo-1, 'total' => $total], 3600);
        }

        // Write failure CSV if any
        $reportPath = "bulk_reports/{$this->importId}.csv";
        if (!empty($failures)) {
            $fh = fopen(storage_path('app/' . $reportPath), 'w');
            // header
            $colsHdr = array_keys($failures[0]);
            fputcsv($fh, $colsHdr);
            foreach ($failures as $r) { fputcsv($fh, $r); }
            fclose($fh);
        }

        Cache::put($reportKey, ['success'=>$success, 'errors'=>$failures, 'warnings'=>$warnings, 'report_path'=> (empty($failures) ? null : $reportPath)], 3600*6);
        Cache::put($cacheKey, ['status' => 'finished', 'processed' => $total, 'total' => $total], 3600*6);
    }

    // Helper: fuzzy match name -> id from associative array id=>name
    protected function findBestMatch(array $map, string $query)
    {
        $query = mb_strtolower(trim($query));
        $bestId = null; $bestScore = -1;
        foreach ($map as $id => $name) {
            $n = mb_strtolower($name);
            // use similar_text percentage
            similar_text($query, $n, $perc);
            if ($perc > $bestScore) { $bestScore = $perc; $bestId = $id; }
        }
        // threshold: require at least 40% similarity
        return ($bestScore >= 40) ? $bestId : null;
    }
}
