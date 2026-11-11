<?php

namespace App;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LearningOutcomeManager
{
    public function assignments(int $memberId): Builder
    {
        if (Schema::hasTable('student_leeruitkomsten')) {
            return DB::table('student_leeruitkomsten as assignment')
                ->join('student', 'student.idstudent', '=', 'assignment.student_idstudent')
                ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_leeruitkomsten_id')
                ->where('student.lid_idlid', $memberId)
                ->select('outcome.leeruitkomsten_id', 'outcome.titel', 'outcome.code', 'assignment.status', 'assignment.opmerking');
        }

        return DB::table('leeruitkomsten_has_lid as assignment')
            ->join('leeruitkomsten as outcome', 'outcome.leeruitkomsten_id', '=', 'assignment.leeruitkomsten_id')
            ->where('assignment.lid_idlid', $memberId)
            ->select('outcome.leeruitkomsten_id', 'outcome.titel', 'outcome.code', 'assignment.status')
            ->selectRaw('NULL as opmerking');
    }

    public function remove(int $memberId, int $outcomeId): void
    {
        DB::transaction(function () use ($memberId, $outcomeId): void {
            DB::table('lid')->where('idlid', $memberId)->lockForUpdate()->first();
            abort_unless($this->assignments($memberId)->where('outcome.leeruitkomsten_id', $outcomeId)->exists(), 404);
            if (Schema::hasTable('student_leeruitkomsten')) {
                $query = DB::table('student_leeruitkomsten')
                    ->whereIn('student_idstudent', DB::table('student')->where('lid_idlid', $memberId)->select('idstudent'))
                    ->where('leeruitkomsten_leeruitkomsten_id', $outcomeId);
                if (Schema::hasColumn('reflectie', 'student_leeruitkomsten_id_leeruitkomsten')
                    && DB::table('reflectie')->whereIn('student_leeruitkomsten_id_leeruitkomsten', (clone $query)->select('id_leeruitkomsten'))->exists()) {
                    throw ValidationException::withMessages(['leeruitkomst' => 'Deze leeruitkomst bevat reflecties en kan niet worden verwijderd. De reflecties blijven bewaard.']);
                }
                $query->delete();
            }
            if (Schema::hasTable('leeruitkomsten_has_lid')) {
                DB::table('leeruitkomsten_has_lid')->where('lid_idlid', $memberId)->where('leeruitkomsten_id', $outcomeId)->delete();
            }
        });
    }

    public function save(int $memberId, int $outcomeId, string $status, ?string $comment, bool $assign = false): void
    {
        DB::transaction(function () use ($memberId, $outcomeId, $status, $comment, $assign): void {
            DB::table('lid')->where('idlid', $memberId)->lockForUpdate()->first();
            if (! $assign) {
                abort_unless($this->assignments($memberId)->where('outcome.leeruitkomsten_id', $outcomeId)->exists(), 404);
            }
            if (Schema::hasTable('student_leeruitkomsten')) {
                $studentId = DB::table('student')->where('lid_idlid', $memberId)->value('idstudent');
                if (! $studentId) {
                    throw ValidationException::withMessages(['leeruitkomst' => 'Sla eerst de studentgegevens op om een studentprofiel aan te maken.']);
                }
                $key = ['student_idstudent' => $studentId, 'leeruitkomsten_leeruitkomsten_id' => $outcomeId];
                $data = ['status' => $status, 'opmerking' => $comment, 'vooruitgang' => $status === 'Behaald' ? 100 : 0, 'laatste_update' => now()];
                $query = DB::table('student_leeruitkomsten')->where($key);
                if ($assign && $query->exists()) {
                    return;
                }
                DB::table('student_leeruitkomsten')->updateOrInsert($key, $data);
            }
            if (Schema::hasTable('leeruitkomsten_has_lid')) {
                $key = ['lid_idlid' => $memberId, 'leeruitkomsten_id' => $outcomeId];
                $query = DB::table('leeruitkomsten_has_lid')->where($key);
                if (! $assign || ! $query->exists()) {
                    DB::table('leeruitkomsten_has_lid')->updateOrInsert($key, ['status' => $status]);
                }
            }
        });
    }
}
