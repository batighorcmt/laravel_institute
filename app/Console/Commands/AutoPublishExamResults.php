<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\Result;
use App\Services\ExamResultSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoPublishExamResults extends Command
{
    /**
     * The name and signature of the console command.
     * Run every 5 minutes via scheduler.
     */
    protected $signature = 'app:auto-publish-exam-results';

    /**
     * The console command description.
     */
    protected $description = 'Publish exam results once an exam is marked completed and its result_publish_date has arrived — the exam edit form collects this date but nothing previously acted on it.';

    public function handle(ExamResultSyncService $syncService): void
    {
        $today = Carbon::today()->toDateString();

        $exams = Exam::where('status', 'completed')
            ->whereNotNull('result_publish_date')
            ->whereDate('result_publish_date', '<=', $today)
            ->whereHas('results', fn ($q) => $q->where('is_published', false))
            ->with('school')
            ->get();

        foreach ($exams as $exam) {
            if (! $exam->school) {
                continue;
            }

            try {
                // Recalculate first so what gets published reflects the latest marks.
                $syncService->syncExamClass($exam->school, $exam);

                Result::forExam($exam->id)->update([
                    'is_published' => true,
                    'published_at' => now(),
                ]);

                $this->info("Published results for exam #{$exam->id} ({$exam->name}).");
            } catch (\Throwable $e) {
                Log::error("Auto-publish failed for exam #{$exam->id}: ".$e->getMessage());
            }
        }
    }
}
