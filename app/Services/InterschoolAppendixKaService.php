<?php

namespace App\Services;

use App\Models\InterschoolSeason;
use App\Models\InterschoolSeasonEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InterschoolAppendixKaService
{
    /**
     * Group single-event players by student for a season (one appendix Ka per student).
     *
     * @return array{
     *     groupedPlayers: Collection<int, array{
     *         student: \App\Models\Student|null,
     *         attendance_days: string|null,
     *         calculated_age: array{years: int, months: int, days: int}|null,
     *         events: array<int, string>
     *     }>
     * }
     */
    public function groupSingleEventPlayersForSeason(InterschoolSeason $season, Carbon $ageDateCarbon): array
    {
        $seasonEvents = InterschoolSeasonEvent::query()
            ->where('interschool_season_id', $season->id)
            ->whereHas('event', fn ($query) => $query->where('type', 'single'))
            ->with(['event', 'subEvent', 'players.student.currentEnrollment.class'])
            ->get();

        $grouped = [];

        foreach ($seasonEvents as $seasonEvent) {
            $eventLabel = $this->formatEventLabel($seasonEvent);

            foreach ($seasonEvent->players as $player) {
                $studentId = $player->student_id;

                if (! isset($grouped[$studentId])) {
                    $grouped[$studentId] = [
                        'student' => $player->student,
                        'attendance_days' => $player->attendance_days,
                        'calculated_age' => $this->calculateAge($player->student, $ageDateCarbon),
                        'events' => [],
                    ];
                }

                if (! in_array($eventLabel, $grouped[$studentId]['events'], true)) {
                    $grouped[$studentId]['events'][] = $eventLabel;
                }

                if ($player->attendance_days !== null && $player->attendance_days !== '') {
                    $existing = $grouped[$studentId]['attendance_days'];
                    if ($existing === null || (int) $player->attendance_days > (int) $existing) {
                        $grouped[$studentId]['attendance_days'] = $player->attendance_days;
                    }
                }
            }
        }

        $groupedPlayers = collect($grouped)
            ->sortBy(function (array $entry) {
                $roll = $entry['student']?->currentEnrollment?->roll_no ?? 999999;

                return (int) $roll;
            })
            ->values();

        return [
            'groupedPlayers' => $groupedPlayers,
        ];
    }

    public function formatEventLabel(InterschoolSeasonEvent $seasonEvent): string
    {
        $label = $seasonEvent->event->name;

        if ($seasonEvent->subEvent) {
            $label .= ' ('.$seasonEvent->subEvent->name.')';
        }

        return $label;
    }

    /**
     * @return array{years: int, months: int, days: int}|null
     */
    public function calculateAge(?\App\Models\Student $student, Carbon $ageDateCarbon): ?array
    {
        if (! $student || ! $student->date_of_birth) {
            return null;
        }

        $dob = Carbon::parse($student->date_of_birth);
        $diff = $dob->diff($ageDateCarbon);

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
        ];
    }

    public function enrichTeamEventPlayers(InterschoolSeasonEvent $seasonEvent, Carbon $ageDateCarbon): void
    {
        foreach ($seasonEvent->players as $player) {
            if ($player->student) {
                $player->calculated_age = $this->calculateAge($player->student, $ageDateCarbon);
            }
        }
    }
}
