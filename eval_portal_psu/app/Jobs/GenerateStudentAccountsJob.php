<?php

namespace App\Jobs;

use App\Models\AcademicPeriod;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GenerateStudentAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $startNumber;
    public string $endNumber;
    public string $password;
    public int $collegeId;
    public int $departmentId;
    public int $requestedBy;

    public function __construct(string $startNumber, string $endNumber, string $password, int $collegeId, int $departmentId, int $requestedBy)
    {
        $this->startNumber  = $startNumber;
        $this->endNumber    = $endNumber;
        $this->password     = $password;
        $this->collegeId    = $collegeId;
        $this->departmentId = $departmentId;
        $this->requestedBy  = $requestedBy;
    }

    public function handle(): void
    {
        $start = (int) $this->startNumber;
        $end   = (int) $this->endNumber;

        $yearPrefix = $this->determineYearPrefix();

        for ($i = $start; $i <= $end; $i++) {
            $studentNumber = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $email = sprintf('%sUR%s@psu.edu.ph', $yearPrefix, $studentNumber);

            $numberExists = StudentProfile::where('student_number', $studentNumber)->exists();
            $emailExists  = User::where('email', $email)->exists();

            if ($numberExists || $emailExists) {
                continue;
            }

            DB::transaction(function () use ($studentNumber, $email) {
                $user = User::create([
                    'name'     => 'Student Number '.$studentNumber,
                    'email'    => $email,
                    'password' => Hash::make($this->password),
                ]);

                $user->assignRole('student');

                StudentProfile::create([
                    'user_id'         => $user->id,
                    'student_number'  => $studentNumber,
                    'department_id'   => $this->departmentId,
                ]);
            });
        }
    }

    protected function determineYearPrefix(): string
    {
        $period = AcademicPeriod::orderByDesc('year_start')->orderByDesc('year_end')->first();

        if ($period && $period->year_start) {
            return substr((string) $period->year_start, -2);
        }

        return now()->format('y');
    }
}
