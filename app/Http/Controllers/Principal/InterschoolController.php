<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\InterschoolEvent;
use App\Models\InterschoolPlayer;
use App\Models\InterschoolSeason;
use App\Models\InterschoolSeasonEvent;
use App\Models\InterschoolSubEvent;
use App\Models\School;
use App\Services\InterschoolAppendixKaService;
use Illuminate\Http\Request;

class InterschoolController extends Controller
{
    public function __construct(private InterschoolAppendixKaService $appendixKaService) {}

    public function index($school)
    {
        $schoolModel = is_object($school) ? $school : School::findOrFail($school);

        return view('principal.game_and_sports.interschool.index', [
            'school' => $schoolModel,
        ]);
    }

    public function settings($school)
    {
        $schoolModel = is_object($school) ? $school : School::findOrFail($school);

        return view('principal.game_and_sports.interschool.settings', [
            'school' => $schoolModel,
        ]);
    }

    public function getSeasons(Request $request, $school)
    {
        $schoolId = is_object($school) ? $school->id : $school;

        return InterschoolSeason::where('school_id', $schoolId)->orderBy('id', 'desc')->get();
    }

    public function storeSeason(Request $request, $school)
    {
        $schoolId = is_object($school) ? $school->id : $school;
        $request->validate(['name' => 'required|string|max:255', 'age_date' => 'nullable|date']);

        $season = InterschoolSeason::create([
            'school_id' => $schoolId,
            'name' => $request->name,
            'age_date' => $request->age_date,
        ]);

        return response()->json($season);
    }

    public function updateSeason(Request $request, $school, $season)
    {
        $schoolId = is_object($school) ? $school->id : $school;
        $request->validate([
            'name' => 'required|string|max:255',
            'age_date' => 'nullable|date',
        ]);

        $seasonModel = InterschoolSeason::where('school_id', $schoolId)->findOrFail($season);
        $seasonModel->update([
            'name' => $request->name,
            'age_date' => $request->age_date,
        ]);

        return response()->json($seasonModel);
    }

    public function getEventsSettings(Request $request, $school)
    {
        $schoolId = is_object($school) ? $school->id : $school;

        return InterschoolEvent::where('school_id', $schoolId)->with('subEvents')->get();
    }

    public function storeEventSetting(Request $request, $school)
    {
        $schoolId = is_object($school) ? $school->id : $school;
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:single,team',
        ]);

        $event = InterschoolEvent::create([
            'school_id' => $schoolId,
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return response()->json($event->load('subEvents'));
    }

    public function updateEventSetting(Request $request, $school, $id)
    {
        $schoolId = is_object($school) ? $school->id : $school;
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $event = InterschoolEvent::where('school_id', $schoolId)->findOrFail($id);
        $event->update(['name' => $request->name]);

        return response()->json($event->load('subEvents'));
    }

    public function deleteEventSetting($school, $id)
    {
        InterschoolEvent::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function storeSubEventSetting(Request $request, $school)
    {
        $request->validate([
            'interschool_event_id' => 'required|exists:interschool_events,id',
            'name' => 'required|string|max:255',
        ]);

        $subEvent = InterschoolSubEvent::create([
            'interschool_event_id' => $request->interschool_event_id,
            'name' => $request->name,
        ]);

        return response()->json($subEvent);
    }

    public function updateSubEventSetting(Request $request, $school, $id)
    {
        $schoolId = is_object($school) ? $school->id : $school;
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $subEvent = InterschoolSubEvent::whereHas('event', function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->findOrFail($id);

        $subEvent->update(['name' => $request->name]);

        return response()->json($subEvent);
    }

    public function deleteSubEventSetting($school, $id)
    {
        InterschoolSubEvent::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function getSeasonEvents($school, $season)
    {
        return InterschoolSeasonEvent::where('interschool_season_id', $season)
            ->with(['event', 'subEvent'])
            ->withCount('players')
            ->get();
    }

    public function storeSeasonEvent(Request $request, $school, $season)
    {
        $request->validate([
            'interschool_event_id' => 'required|exists:interschool_events,id',
            'interschool_sub_event_ids' => 'nullable|array',
            'interschool_sub_event_ids.*' => 'exists:interschool_sub_events,id',
            'age_group' => 'nullable|string|max:255',
        ]);

        $created = [];

        if (! empty($request->interschool_sub_event_ids)) {
            foreach ($request->interschool_sub_event_ids as $subEventId) {
                $seasonEvent = InterschoolSeasonEvent::create([
                    'interschool_season_id' => $season,
                    'interschool_event_id' => $request->interschool_event_id,
                    'interschool_sub_event_id' => $subEventId,
                    'age_group' => $request->age_group,
                ]);
                $created[] = $seasonEvent->load(['event', 'subEvent']);
            }
        } else {
            $seasonEvent = InterschoolSeasonEvent::create([
                'interschool_season_id' => $season,
                'interschool_event_id' => $request->interschool_event_id,
                'interschool_sub_event_id' => null,
                'age_group' => $request->age_group,
            ]);
            $created[] = $seasonEvent->load(['event', 'subEvent']);
        }

        return response()->json($created);
    }

    public function deleteSeasonEvent($school, $season, $id)
    {
        InterschoolSeasonEvent::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function getPlayers($school, $seasonEvent)
    {
        return InterschoolPlayer::where('interschool_season_event_id', $seasonEvent)
            ->with(['student.currentEnrollment.class', 'student.currentEnrollment.section'])
            ->get();
    }

    public function storePlayer(Request $request, $school, $seasonEvent)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'group_name' => 'nullable|string|max:255',
            'height' => 'nullable|string|max:255',
            'weight' => 'nullable|string|max:255',
            'is_captain' => 'boolean',
            'attendance_days' => 'nullable|string',
        ]);

        $player = InterschoolPlayer::create([
            'interschool_season_event_id' => $seasonEvent,
            'student_id' => $request->student_id,
            'group_name' => $request->group_name,
            'height' => $request->height,
            'weight' => $request->weight,
            'is_captain' => $request->is_captain ?? false,
            'attendance_days' => $request->attendance_days,
        ]);

        return response()->json($player->load(['student.currentEnrollment.class', 'student.currentEnrollment.section']));
    }

    public function updatePlayer(Request $request, $school, $seasonEvent, $player)
    {
        $playerModel = InterschoolPlayer::findOrFail($player);
        $playerModel->update($request->only(['group_name', 'height', 'weight', 'is_captain', 'attendance_days']));

        return response()->json($playerModel->load(['student.currentEnrollment.class', 'student.currentEnrollment.section']));
    }

    public function deletePlayer($school, $seasonEvent, $player)
    {
        InterschoolPlayer::findOrFail($player)->delete();

        return response()->json(['success' => true]);
    }

    public function getClasses($school)
    {
        $schoolId = is_object($school) ? $school->id : $school;

        return \App\Models\SchoolClass::where('school_id', $schoolId)->with('sections')->orderBy('numeric_value')->get();
    }

    public function searchStudents(Request $request, $school)
    {
        $schoolId = is_object($school) ? $school->id : $school;
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');

        if (! $classId) {
            return response()->json([]);
        }

        $query = \DB::table('students')
            ->join('student_enrollments', function ($join) {
                $join->on('students.id', '=', 'student_enrollments.student_id');
            })
            ->where('students.school_id', $schoolId)
            ->where('student_enrollments.class_id', $classId)
            ->where('students.status', 'active')
            ->where('student_enrollments.status', 'active')
            ->select(
                'students.id',
                'students.student_id',
                'students.student_name_bn',
                'students.student_name_en',
                'student_enrollments.roll_no',
                'student_enrollments.section_id',
                'student_enrollments.class_id',
                'student_enrollments.status as enrollment_status'
            )
            ->orderByRaw('CAST(student_enrollments.roll_no AS UNSIGNED)');

        if ($sectionId) {
            $query->where('student_enrollments.section_id', $sectionId);
        }

        $students = $query->get()->map(function ($student) {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->student_name_bn ?: $student->student_name_en,
                'roll_no' => $student->roll_no ?? '-',
            ];
        });

        return response()->json($students);
    }

    // Print Appendixes
    public function printAppendixKa(Request $request, $school)
    {
        $schoolModel = is_object($school) ? $school : School::findOrFail($school);
        $studentId = $request->filled('student_id') ? (int) $request->student_id : null;

        if ($request->filled('season_id')) {
            return $this->printGroupedSingleEventAppendixKa($schoolModel, (int) $request->season_id, $studentId);
        }

        $request->validate(['season_event_id' => 'required|exists:interschool_season_events,id']);

        $seasonEvent = InterschoolSeasonEvent::with(['event', 'subEvent', 'players.student.currentEnrollment.class', 'season'])
            ->findOrFail($request->season_event_id);

        if ($seasonEvent->event->type === 'single') {
            return $this->printGroupedSingleEventAppendixKa($schoolModel, $seasonEvent->interschool_season_id, $studentId);
        }

        return $this->printTeamEventAppendixKa($schoolModel, $seasonEvent);
    }

    private function printGroupedSingleEventAppendixKa(School $schoolModel, int $seasonId, ?int $studentId = null)
    {
        $season = InterschoolSeason::where('school_id', $schoolModel->id)->findOrFail($seasonId);
        $ageDate = $season->age_date ?? \Carbon\Carbon::now()->format('Y-m-d');
        $ageDateCarbon = \Carbon\Carbon::parse($ageDate);
        $competitionName = $season->name ?? 'জাতীয় ক্রীড়া প্রতিযোগিতা';

        $grouped = $this->appendixKaService->groupSingleEventPlayersForSeason($season, $ageDateCarbon);
        $groupedPlayers = $grouped['groupedPlayers'];

        if ($studentId !== null) {
            $groupedPlayers = $groupedPlayers
                ->filter(fn (array $entry) => ($entry['student']?->id ?? null) === $studentId)
                ->values();
        }

        return view('principal.game_and_sports.interschool.print.appendix-ka', [
            'school' => $schoolModel,
            'isGroupedSingleEvents' => true,
            'groupedPlayers' => $groupedPlayers,
            'competitionName' => $competitionName,
            'ageDate' => $ageDate,
        ]);
    }

    private function printTeamEventAppendixKa(School $schoolModel, InterschoolSeasonEvent $seasonEvent)
    {
        $ageDate = $seasonEvent->season->age_date ?? \Carbon\Carbon::now()->format('Y-m-d');
        $ageDateCarbon = \Carbon\Carbon::parse($ageDate);
        $competitionName = $seasonEvent->season->name ?? 'জাতীয় ক্রীড়া প্রতিযোগিতা';

        $this->appendixKaService->enrichTeamEventPlayers($seasonEvent, $ageDateCarbon);

        return view('principal.game_and_sports.interschool.print.appendix-ka', [
            'school' => $schoolModel,
            'isGroupedSingleEvents' => false,
            'seasonEvent' => $seasonEvent,
            'competitionName' => $competitionName,
            'ageDate' => $ageDate,
        ]);
    }

    public function printAppendixKha(Request $request, $school)
    {
        $schoolModel = is_object($school) ? $school : School::findOrFail($school);
        $seasonEventId = $request->season_event_id;
        $seasonEvent = InterschoolSeasonEvent::with(['event', 'subEvent'])->findOrFail($seasonEventId);
        $player = InterschoolPlayer::with(['student.currentEnrollment.class'])->findOrFail($request->player_id);

        return view('principal.game_and_sports.interschool.print.appendix-kha', [
            'school' => $schoolModel,
            'seasonEvent' => $seasonEvent,
            'player' => $player,
        ]);
    }

    public function printAppendixGa(Request $request, $school)
    {
        $schoolModel = is_object($school) ? $school : School::findOrFail($school);
        $seasonEventId = $request->season_event_id;
        $seasonEvent = InterschoolSeasonEvent::with(['event', 'subEvent'])->findOrFail($seasonEventId);
        $player = InterschoolPlayer::with(['student.currentEnrollment.class'])->findOrFail($request->player_id);

        return view('principal.game_and_sports.interschool.print.appendix-ga', [
            'school' => $schoolModel,
            'seasonEvent' => $seasonEvent,
            'player' => $player,
        ]);
    }

    public function printAppendixGha(Request $request, $school)
    {
        $schoolModel = is_object($school) ? $school : School::findOrFail($school);
        $seasonEventId = $request->season_event_id;
        $seasonEvent = InterschoolSeasonEvent::with(['event', 'subEvent'])->findOrFail($seasonEventId);
        $player = InterschoolPlayer::with(['student.currentEnrollment.class'])->findOrFail($request->player_id);

        return view('principal.game_and_sports.interschool.print.appendix-gha', [
            'school' => $schoolModel,
            'seasonEvent' => $seasonEvent,
            'player' => $player,
        ]);
    }

    public function printAppendixUmo(Request $request, $school)
    {
        $schoolModel = is_object($school) ? $school : School::findOrFail($school);
        $seasonEventId = $request->season_event_id;
        $seasonEvent = InterschoolSeasonEvent::with(['event', 'subEvent', 'players.student.currentEnrollment.class'])->findOrFail($seasonEventId);

        return view('principal.game_and_sports.interschool.print.appendix-umo', [
            'school' => $schoolModel,
            'seasonEvent' => $seasonEvent,
        ]);
    }
}
