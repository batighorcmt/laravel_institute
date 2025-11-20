<?php

namespace App\Jobs;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Group;
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
        $sections = Section::where('school_id', $this->schoolId)->get()->mapWithKeys(fn($s)=>[$s->id=>$s->name])->toArray();
        $groups = Group::where('school_id', $this->schoolId)->get()->mapWithKeys(fn($g)=>[$g->id=>$g->name])->toArray();

        $rowNo = 1;
        foreach ($rows as $cols) {
            $rowNo++;
            $assoc = [];
            foreach ($header as $i => $colName) {
                $assoc[$colName] = isset($cols[$i]) ? trim((string)$cols[$i]) : null;
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

            // Parse dates
            // Parse optional dates if provided
            $dob = null; if (!empty($assoc['date_of_birth'])) { try { $dob = Carbon::parse($assoc['date_of_birth'])->toDateString(); } catch (\Throwable $e) { try { $dob = Carbon::createFromFormat('d/m/Y', $assoc['date_of_birth'])->toDateString(); } catch (\Throwable $e) { /* ignore invalid */ } } }
            $admission_date = null; if (!empty($assoc['admission_date'])) { try { $admission_date = Carbon::parse($assoc['admission_date'])->toDateString(); } catch (\Throwable $e) { try { $admission_date = Carbon::createFromFormat('d/m/Y', $assoc['admission_date'])->toDateString(); } catch (\Throwable $e) { /* ignore invalid */ } } }

            $studentData = [
                'student_name_en' => $assoc['student_name_en'] ?? null,
                'student_name_bn' => $assoc['student_name_bn'] ?? null,
                'date_of_birth' => $dob,
                'gender' => $assoc['gender'] ?? null,
                'blood_group' => $assoc['blood_group'] ?? null,
                'father_name' => $assoc['father_name'] ?? null,
                'mother_name' => $assoc['mother_name'] ?? null,
                'father_name_bn' => $assoc['father_name_bn'] ?? ($assoc['father_name'] ?? null),
                'mother_name_bn' => $assoc['mother_name_bn'] ?? ($assoc['mother_name'] ?? null),
                'guardian_phone' => $assoc['guardian_phone'] ?? null,
                'guardian_relation' => $assoc['guardian_relation'] ?? null,
                'guardian_name_en' => $assoc['guardian_name_en'] ?? null,
                'guardian_name_bn' => $assoc['guardian_name_bn'] ?? null,
                // address: legacy 'address' fallback to present_address if supplied
                'address' => $assoc['address'] ?? ($assoc['present_address'] ?? null),
                'present_address' => $assoc['present_address'] ?? null,
                'permanent_address' => $assoc['permanent_address'] ?? null,
                // previous education
                'previous_school' => $assoc['previous_school'] ?? null,
                'pass_year' => $assoc['pass_year'] ?? null,
                'previous_result' => $assoc['previous_result'] ?? null,
                'previous_remarks' => $assoc['previous_remarks'] ?? null,
                'admission_date' => $admission_date,
                'status' => $assoc['status'] ?? 'active',
                'school_id' => $this->schoolId,
                'class_id' => null,
            ];

            try {
                $student = Student::create($studentData);
            } catch (\Throwable $e) {
                $failures[] = array_merge($assoc, ['__error'=>'create student failed: '.$e->getMessage()]);
                Cache::put($reportKey, ['success'=> $success, 'errors'=>$failures], 3600);
                Cache::put($cacheKey, ['status' => 'running', 'processed' => $rowNo-1, 'total' => $total], 3600);
                continue;
            }

            // Enrollment mapping: fuzzy match names if provided
            $enYear = $assoc['enroll_academic_year'] ?? null;
            $enClass = null; $enSection = null; $enGroup = null; $enRoll = null;
            if (!empty($assoc['enroll_class_id']) && is_numeric($assoc['enroll_class_id'])) { $enClass = intval($assoc['enroll_class_id']); }
            if (empty($enClass) && !empty($assoc['enroll_class_name'])) { $enClass = $this->findBestMatch($classes, $assoc['enroll_class_name']); }
            if (!empty($assoc['enroll_section_id']) && is_numeric($assoc['enroll_section_id'])) { $enSection = intval($assoc['enroll_section_id']); }
            if (empty($enSection) && !empty($assoc['enroll_section_name'])) { $enSection = $this->findBestMatch($sections, $assoc['enroll_section_name']); }
            if (!empty($assoc['enroll_group_id']) && is_numeric($assoc['enroll_group_id'])) { $enGroup = intval($assoc['enroll_group_id']); }
            if (empty($enGroup) && !empty($assoc['enroll_group_name'])) { $enGroup = $this->findBestMatch($groups, $assoc['enroll_group_name']); }
            if (!empty($assoc['enroll_roll_no']) && is_numeric($assoc['enroll_roll_no'])) { $enRoll = intval($assoc['enroll_roll_no']); }

            // Validate existence of section/group IDs for this school, else null them (foreign key safety)
            if ($enSection && !array_key_exists($enSection, $sections)) { $enSection = null; }
            if ($enGroup && !array_key_exists($enGroup, $groups)) { $enGroup = null; }

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
                $dupQuery = StudentEnrollment::where('school_id',$this->schoolId)->where('academic_year', intval($enYear))->where('class_id', $enClass);
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
                            'academic_year' => intval($enYear),
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

        Cache::put($reportKey, ['success'=>$success, 'errors'=>$failures, 'report_path'=> (empty($failures) ? null : $reportPath)], 3600*6);
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
