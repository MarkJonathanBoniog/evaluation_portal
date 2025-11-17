<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Course;
use App\Models\AcademicPeriod;
use App\Models\StudentProfile;
use App\Models\InstructorProfile;
use App\Models\ChairmanAssignment;
use App\Models\CedAssignment;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------
        // 1. Core users with roles
        // ---------------------------
        $sys = User::factory()->create([
            'name' => 'Sys Admin',
            'email' => 'sys@example.com',
            'password' => bcrypt('password'),
        ]);
        $sys->assignRole('systemadmin');

        $ced = User::factory()->create([
            'name' => 'CED Admin',
            'email' => 'ced@example.com',
            'password' => bcrypt('password'),
        ]);
        $ced->assignRole('ced');

        $dean = User::factory()->create([
            'name' => 'Dean IT',
            'email' => 'dean@example.com',
            'password' => bcrypt('password'),
        ]);
        $ced->assignRole('ced');

        $chair = User::factory()->create([
            'name' => 'IT Chairman',
            'email' => 'chair@example.com',
            'password' => bcrypt('password'),
        ]);
        $chair->assignRole('chairman');

        $instructors = collect([
            ['name' => 'Jane Instructor', 'email' => 'inst@example.com'],
            ['name' => 'Mark Instructor', 'email' => 'markinst@example.com'],
            ['name' => 'Rica Instructor', 'email' => 'ricainst@example.com'],
        ])->map(function ($data) {
            $u = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
            ]);
            $u->assignRole('instructor');
            return $u;
        });

        $students = collect([
            ['name' => 'John Student', 'email' => 'stud@example.com'],
            ['name' => 'Anna Student', 'email' => 'anna@example.com'],
            ['name' => 'Leo Student', 'email' => 'leo@example.com'],
            ['name' => 'Mina Student', 'email' => 'mina@example.com'],
        ])->map(function ($data) {
            $u = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
            ]);
            $u->assignRole('student');
            return $u;
        });

        // ---------------------------
        // 2. Organization structure
        // ---------------------------
        $college = College::firstOrCreate(['name' => 'College of Computing']);
        $dept = Department::firstOrCreate([
            'college_id' => $college->id,
            'name' => 'IT Department',
        ]);

        // ---------------------------
        // 3. Academic period
        // ---------------------------
        $period = AcademicPeriod::firstOrCreate(
            [
                'college_id'    => $college->id,
                'department_id' => $dept->id,
                'year_start'    => 2025,
                'year_end'      => 2026,
                'term'          => 'first',
            ],
            ['created_by' => $chair->id]
        );

        // ---------------------------
        // 4. Programs & Courses
        // ---------------------------
        $program = Program::create([
            'academic_period_id' => $period->id,
            'department_id'      => $dept->id,
            'name'               => 'BS Information Technology',
            'major'              => 'Web & Mobile Dev',
        ]);

        $sia = Course::firstOrCreate(
            ['course_code' => 'SIA101'],
            ['course_name' => 'System Integration & Analysis']
        );

        $dsa = Course::firstOrCreate(
            ['course_code' => 'DSA102'],
            ['course_name' => 'Data Structures & Algorithms']
        );

        $program->courses()->syncWithoutDetaching([$sia->id, $dsa->id]);

        // ---------------------------
        // 5. Profiles
        // ---------------------------
        // Instructors
        $instructors->each(function ($inst, $i) use ($dept) {
            InstructorProfile::create([
                'user_id' => $inst->id,
                'instructor_uid' => 'INST-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'department_id' => $dept->id,
            ]);
        });

        // Students
        $students->each(function ($stud, $i) use ($program) {
            StudentProfile::create([
                'user_id' => $stud->id,
                'student_number' => '2025-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'program_id' => $program->id,
            ]);
        });

        // ---------------------------
        // 6. Admin role assignments
        // ---------------------------
        // ChairmanAssignment::create([
        //     'user_id' => $chair->id,
        //     'department_id' => $dept->id,
        // ]);

        // CedAssignment::create([
        //     'user_id' => $ced->id,
        //     'college_id' => $college->id,
        // ]);

        $this->command->info('✅ Demo users, profiles, and org structure seeded successfully.');
    }
}
