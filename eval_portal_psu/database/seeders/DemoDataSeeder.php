<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Course;
use App\Models\AcademicPeriod;
use App\Models\StudentProfile;
use App\Models\InstructorProfile;
use App\Models\ChairmanAssignment;
use App\Models\DeanAssignment;
use App\Models\CedAssignment;
use App\Models\Section;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------------
        // 0. Base structure configuration
        // -----------------------------------
        $structure = [
            'College of Computing' => [
                'code' => 'COC',
                'departments' => [
                    'Information Technology',
                    'Mathematics',
                ],
            ],
            'College of Arts and Education' => [
                'code' => 'CAE',
                'departments' => [
                    'English and Literature',
                    'Social Sciences',
                    'Teacher Education',
                ],
            ],
            'College of Engineering' => [
                'code' => 'COE',
                'departments' => [
                    'Civil Engineering',
                    'Electrical Engineering',
                    'Mechanical Engineering',
                    'Computer Engineering',
                    'Industrial Engineering',
                ],
            ],
        ];

        // -----------------------------------
        // 1. System admin + CED user
        // -----------------------------------
        $sys = User::factory()->create([
            'name'     => 'System Administrator',
            'email'    => 'sysadmin@example.com',
            'password' => bcrypt('password'),
        ]);
        $sys->assignRole('systemadmin');

        $cedUser = User::factory()->create([
            'name'     => 'CED Administrator',
            'email'    => 'ced@example.com',
            'password' => bcrypt('password'),
        ]);
        // Base role: instructor + high-level role
        $cedUser->assignRole(['instructor', 'ced']);

        // Give CED an instructor profile (no specific department)
        InstructorProfile::create([
            'user_id'        => $cedUser->id,
            'instructor_uid' => 'INST-CED',
            'department_id'  => null,
        ]);

        // We'll create CedAssignments per college later
        $deansByCollegeId = [];
        $instructorsByDepartmentId = []; // dept_id => Collection<User>
        $chairmenByDepartmentId = [];
        $academicPeriodsByDepartmentId = [];

        $globalInstructorCounter = 1;
        $globalStudentCounter    = 1;
        $globalCourseCounter     = 1;

        // -----------------------------------
        // 2. Colleges, Departments, Deans, Chairmen, Instructors, Periods
        // -----------------------------------
        foreach ($structure as $collegeName => $meta) {
            $college = College::firstOrCreate(['name' => $collegeName]);
            $collegeCode = $meta['code'];

            // Dean for this college (also instructor)
            $deanUser = User::factory()->create([
                'name'     => $collegeCode . ' Dean',
                'email'    => Str::slug($collegeCode . '-dean') . '@example.com',
                'password' => bcrypt('password'),
            ]);
            $deanUser->assignRole(['instructor', 'dean']);

            $deansByCollegeId[$college->id] = $deanUser;

            // Departments under this college
            $departmentModels = [];

            foreach ($meta['departments'] as $deptName) {
                $dept = Department::firstOrCreate([
                    'college_id' => $college->id,
                    'name'       => $deptName,
                ]);

                $departmentModels[] = $dept;

                // Chairman for this department (also instructor)
                $chairUser = User::factory()->create([
                    'name'     => $deptName . ' Chairman',
                    'email'    => Str::slug($deptName . '-chair') . '@example.com',
                    'password' => bcrypt('password'),
                ]);
                $chairUser->assignRole(['instructor', 'chairman']);

                // Instructor profile for chairman
                InstructorProfile::create([
                    'user_id'        => $chairUser->id,
                    'instructor_uid' => 'INST-' . str_pad($globalInstructorCounter++, 3, '0', STR_PAD_LEFT),
                    'department_id'  => $dept->id,
                ]);

                // Chairman assignment
                ChairmanAssignment::firstOrCreate([
                    'user_id'       => $chairUser->id,
                    'department_id' => $dept->id,
                ]);

                $chairmenByDepartmentId[$dept->id] = $chairUser;

                // Two regular instructors for this department
                $instructorsForDept = collect([$chairUser]); // include chairman as instructor

                for ($i = 1; $i <= 2; $i++) {
                    $instUser = User::factory()->create([
                        'name'     => $deptName . " Instructor {$i}",
                        'email'    => Str::slug("{$deptName}-instructor-{$i}") . '@example.com',
                        'password' => bcrypt('password'),
                    ]);
                    $instUser->assignRole('instructor');

                    InstructorProfile::create([
                        'user_id'        => $instUser->id,
                        'instructor_uid' => 'INST-' . str_pad($globalInstructorCounter++, 3, '0', STR_PAD_LEFT),
                        'department_id'  => $dept->id,
                    ]);

                    $instructorsForDept->push($instUser);
                }

                $instructorsByDepartmentId[$dept->id] = $instructorsForDept;

                // Academic period 2025-2026 first sem for this department, created by college dean
                $period = AcademicPeriod::firstOrCreate(
                    [
                        'college_id'    => $college->id,
                        'department_id' => $dept->id,
                        'year_start'    => 2025,
                        'year_end'      => 2026,
                        'term'          => 'first',
                    ],
                    [
                        'created_by' => $deanUser->id,
                    ]
                );

                $academicPeriodsByDepartmentId[$dept->id] = $period;
            }

            // Dean assignment – dean to this college
            DeanAssignment::firstOrCreate([
                'user_id'    => $deanUser->id,
                'college_id' => $college->id,
            ]);

            // CedAssignment – CED can be linked to all colleges
            CedAssignment::firstOrCreate([
                'user_id'    => $cedUser->id,
                'college_id' => $college->id,
            ]);

            // Now that departments exist, assign dean an instructor profile at the first department in this college
            if (!empty($departmentModels)) {
                $primaryDept = $departmentModels[0];
                InstructorProfile::create([
                    'user_id'        => $deanUser->id,
                    'instructor_uid' => 'INST-' . str_pad($globalInstructorCounter++, 3, '0', STR_PAD_LEFT),
                    'department_id'  => $primaryDept->id,
                ]);
            }
        }

        // -----------------------------------
        // 3. Programs, Courses, Sections, Students
        // -----------------------------------
        foreach ($academicPeriodsByDepartmentId as $deptId => $period) {
            $dept        = Department::find($deptId);
            $college     = $dept->college;
            $collegeCode = substr($college->name, 0, 3); // just for codes
            $deptCode    = substr($dept->name, 0, 3);

            // 1 program per academic period & department
            $program = Program::firstOrCreate(
                [
                    'academic_period_id' => $period->id,
                    'department_id'      => $dept->id,
                    'name'               => 'BS ' . $deptCode,
                    'major'              => null,
                ]
            );

            // 2 courses per program
            $courses = collect();
            for ($c = 1; $c <= 2; $c++) {
                $code = strtoupper($collegeCode . $deptCode) . sprintf('%03d', $globalCourseCounter++);
                $course = Course::firstOrCreate(
                    ['course_code' => $code],
                    ['course_name' => $dept->name . " Course {$c}"]
                );
                $courses->push($course);
            }

            // Attach courses to program
            $program->courses()->syncWithoutDetaching($courses->pluck('id')->all());

            // Sections + students
            $instructorsForDept = $instructorsByDepartmentId[$dept->id];

            foreach ($courses as $course) {
                // 1–2 sections per course (let's do 2 for richer data)
                for ($s = 1; $s <= 2; $s++) {
                    $sectionLabel = $s === 1 ? 'A' : 'B';

                    // Pick a random instructor from the department
                    $instructorUser = $instructorsForDept->random();

                    $section = Section::create([
                        'academic_period_id' => $period->id,
                        'program_id'         => $program->id,
                        'course_id'          => $course->id,
                        'section_label'      => $sectionLabel,
                        'instructor_user_id' => $instructorUser->id,
                    ]);

                    // 3 students per section
                    for ($st = 1; $st <= 3; $st++) {
                        $studentUser = User::factory()->create([
                            'name'     => $dept->name . " Student {$globalStudentCounter}",
                            'email'    => Str::slug($dept->name . "-student-{$globalStudentCounter}") . '@example.com',
                            'password' => bcrypt('password'),
                        ]);
                        $studentUser->assignRole('student');

                        // Student profile
                        StudentProfile::create([
                            'user_id'        => $studentUser->id,
                            'student_number' => '2025-' . str_pad($globalStudentCounter, 4, '0', STR_PAD_LEFT),
                            'program_id'     => $program->id,
                        ]);

                        // Enroll student into section (section_student pivot)
                        DB::table('section_student')->insert([
                            'section_id'       => $section->id,
                            'student_user_id'  => $studentUser->id,
                            'evaluated_at'     => null,
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);

                        $globalStudentCounter++;
                    }
                }
            }
        }

        $this->command->info('✅ Demo data seeded: colleges, departments, periods, programs, courses, sections, instructors, chairmen, deans, CED, and students.');
    }
}
