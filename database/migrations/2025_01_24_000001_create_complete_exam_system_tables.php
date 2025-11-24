<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Exams table (main exam information)
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('name'); // পরীক্ষার নাম (প্রথম সাময়িক, বার্ষিক ইত্যাদি)
            $table->string('name_bn')->nullable(); // বাংলা নাম
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'class_id']);
        });

        // 2. Exam Subjects table (subjects for each exam with marks configuration)
        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Mark configuration
            $table->smallInteger('creative_full_mark')->default(0);
            $table->smallInteger('creative_pass_mark')->default(0);
            $table->smallInteger('mcq_full_mark')->default(0);
            $table->smallInteger('mcq_pass_mark')->default(0);
            $table->smallInteger('practical_full_mark')->default(0);
            $table->smallInteger('practical_pass_mark')->default(0);
            
            // Pass type: 'each' = প্রতিটিতে পাস করতে হবে, 'combined' = মোট নম্বরে পাস হলেই হবে
            $table->enum('pass_type', ['each', 'combined'])->default('combined');
            $table->smallInteger('total_full_mark')->default(0);
            $table->smallInteger('total_pass_mark')->default(0);
            
            // Mark entry deadline
            $table->dateTime('mark_entry_deadline')->nullable();
            $table->boolean('mark_entry_completed')->default(false);
            
            $table->date('exam_date')->nullable();
            $table->time('exam_start_time')->nullable();
            $table->time('exam_end_time')->nullable();
            $table->smallInteger('display_order')->default(0);
            
            $table->timestamps();

            $table->unique(['exam_id', 'subject_id']);
            $table->index(['exam_id', 'teacher_id']);
        });

        // 3. Marks table (student marks for each subject)
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('exam_subject_id')->constrained('exam_subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            
            // Marks obtained
            $table->decimal('creative_marks', 5, 2)->nullable();
            $table->decimal('mcq_marks', 5, 2)->nullable();
            $table->decimal('practical_marks', 5, 2)->nullable();
            $table->decimal('total_marks', 6, 2)->nullable();
            
            // Result calculation
            $table->string('letter_grade', 5)->nullable(); // A+, A, A-, B etc
            $table->decimal('grade_point', 3, 2)->nullable(); // 5.00, 4.00 etc
            $table->enum('pass_status', ['pass', 'fail', 'absent'])->default('absent');
            
            // Additional info
            $table->boolean('is_absent')->default(false);
            $table->text('remarks')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('entered_at')->nullable();
            
            $table->timestamps();

            $table->unique(['exam_id', 'subject_id', 'student_id']);
            $table->index(['exam_id', 'student_id']);
            $table->index(['exam_subject_id', 'student_id']);
        });

        // 4. Exam Results Summary table (overall result for each student)
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
            
            // Total marks and GPA
            $table->decimal('total_marks', 7, 2)->default(0);
            $table->decimal('total_possible_marks', 7, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('gpa', 3, 2)->nullable();
            $table->string('letter_grade', 5)->nullable();
            
            // Pass/Fail status
            $table->enum('result_status', ['pass', 'fail', 'incomplete'])->default('incomplete');
            $table->integer('failed_subjects_count')->default(0);
            $table->integer('absent_subjects_count')->default(0);
            
            // Merit position
            $table->integer('class_position')->nullable();
            $table->integer('section_position')->nullable();
            $table->integer('merit_position')->nullable(); // Overall school position
            
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['exam_id', 'class_id', 'section_id']);
            $table->index(['exam_id', 'result_status', 'is_published']);
        });

        // 5. Seat Plans table (seat plan header)
        Schema::create('seat_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // সিট প্ল্যানের নাম
            $table->string('shift')->nullable(); // প্রভাতী/দিবা
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });

        // 6. Seat Plan Classes (which classes are included)
        Schema::create('seat_plan_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained('seat_plans')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['seat_plan_id', 'class_id']);
        });

        // 7. Seat Plan Exams (which exams are in this seat plan)
        Schema::create('seat_plan_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained('seat_plans')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['seat_plan_id', 'exam_id']);
        });

        // 8. Seat Plan Rooms (room configuration)
        Schema::create('seat_plan_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained('seat_plans')->onDelete('cascade');
            $table->string('room_no', 50); // রুম নম্বর (01, 02, 101 ইত্যাদি)
            $table->string('title')->nullable(); // রুমের নাম (বিজ্ঞান ভবন ১০১)
            $table->tinyInteger('columns_count')->default(3); // সাধারণত 3 কলাম
            $table->integer('col1_benches')->default(0); // কলাম ১ এ কতগুলো বেঞ্চ
            $table->integer('col2_benches')->default(0); // কলাম ২ এ কতগুলো বেঞ্চ
            $table->integer('col3_benches')->default(0); // কলাম ৩ এ কতগুলো বেঞ্চ
            $table->timestamps();

            $table->unique(['seat_plan_id', 'room_no']);
        });

        // 9. Seat Plan Allocations (actual seat assignments)
        Schema::create('seat_plan_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained('seat_plans')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('seat_plan_rooms')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->integer('col_no'); // কলাম নম্বর (1, 2, 3)
            $table->integer('bench_no'); // বেঞ্চ নম্বর (1, 2, 3, ...)
            $table->enum('position', ['Left', 'Right']); // বেঞ্চের বাম বা দান পাশ
            $table->timestamps();

            // একই সিটে একাধিক student assign করা যাবে না
            $table->unique(['room_id', 'col_no', 'bench_no', 'position']);
            // একই student একই seat plan এ একবারই থাকবে
            $table->unique(['seat_plan_id', 'student_id']);
            
            $table->index(['seat_plan_id', 'room_id']);
        });

        // 10. Subject Group Mapping (কোন বিষয় কোন গ্রুপে আছে)
        Schema::create('subject_group_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['subject_id', 'class_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_plan_allocations');
        Schema::dropIfExists('seat_plan_rooms');
        Schema::dropIfExists('seat_plan_exams');
        Schema::dropIfExists('seat_plan_classes');
        Schema::dropIfExists('seat_plans');
        Schema::dropIfExists('subject_group_map');
        Schema::dropIfExists('results');
        Schema::dropIfExists('marks');
        Schema::dropIfExists('exam_subjects');
        Schema::dropIfExists('exams');
    }
};
