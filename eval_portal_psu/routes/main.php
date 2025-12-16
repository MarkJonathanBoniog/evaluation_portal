<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboards\InstructorDashboardController;
use App\Http\Controllers\Dashboards\ChairmanDashboardController;
use App\Http\Controllers\Dashboards\DeanDashboardController;
use App\Http\Controllers\Dashboards\CedDashboardController;
use App\Http\Controllers\Dashboards\EvaluationSummaryController;

Route::middleware(['auth'])->group(function () {
    // Dashboards
    Route::get('/dashboard/student', fn () => view('dashboards.student.index'))
        ->name('dashboard.student')->middleware('role:student');

    Route::get('/dashboard/instructor', [InstructorDashboardController::class, 'index'])
        ->name('dashboard.instructor')
        ->middleware(['auth','role:instructor']);

    Route::get('/dashboard/chairman', [ChairmanDashboardController::class, 'index'])
        ->name('dashboard.chairman')
        ->middleware('role:chairman');

    Route::get('/dashboard/dean', [DeanDashboardController::class, 'index'])
        ->name('dashboard.dean')->middleware('role:dean');

    Route::get('/dashboard/ced', [CedDashboardController::class, 'index'])
        ->name('dashboard.ced')->middleware('role:ced');

    Route::get('/dashboard/evaluation-summary', [EvaluationSummaryController::class, 'index'])
        ->name('dashboard.evaluation-summary')
        ->middleware('role:systemadmin|chairman');

    Route::get('/dashboard/evaluation-summary/{instructor}', [EvaluationSummaryController::class, 'show'])
        ->name('dashboard.evaluation-summary.show')
        ->middleware('role:systemadmin|chairman');

    Route::post('/dashboard/evaluation-summary/{instructor}/plan', [EvaluationSummaryController::class, 'storeDevelopmentPlan'])
        ->name('dashboard.evaluation-summary.plan')
        ->middleware('role:chairman');

    Route::get('/dashboard/systemadmin', [EvaluationSummaryController::class, 'index'])
        ->name('dashboard.systemadmin')->middleware('role:systemadmin');
});

use App\Http\Controllers\Manage\AcademicPeriodController;
use App\Http\Controllers\Manage\ProgramController;
use App\Http\Controllers\Manage\CourseController;
use App\Http\Controllers\Manage\SectionController;
use App\Http\Controllers\Manage\RosterController;
use App\Http\Controllers\Manage\SuperiorEvaluationController;
use App\Http\Controllers\Manage\StudentAccountController;
use App\Http\Controllers\Manage\InstructorAccountController;
use App\Http\Controllers\Manage\SystemSettingsController;

Route::middleware(['auth','role:chairman|dean|ced|systemadmin'])
    ->prefix('manage')->name('manage.')
    ->group(function () {

        // 1) Academic Periods
        Route::resource('periods', AcademicPeriodController::class)
            ->only(['index','create','store','show','destroy']);

        // 2) Programs
        Route::get('periods/{period}/programs', [ProgramController::class,'index'])
            ->name('programs.index');
        Route::post('periods/{period}/programs', [ProgramController::class,'store'])
            ->name('programs.store');
        Route::delete('periods/{period}/programs/{program}', [ProgramController::class,'destroy'])
            ->name('programs.destroy');

        // 3) Courses
        Route::get('periods/{period}/programs/{program}/courses', [CourseController::class,'index'])
            ->name('courses.index');
        Route::post('periods/{period}/programs/{program}/courses', [CourseController::class,'store'])
            ->name('courses.store');
        Route::delete('periods/{period}/programs/{program}/courses/{course}', [CourseController::class,'destroy'])
            ->name('courses.destroy');
        Route::get('periods/{period}/programs/{program}/courses/export', [CourseController::class,'export'])
            ->name('courses.export');
        Route::post('periods/{period}/programs/{program}/courses/import', [CourseController::class,'import'])
            ->name('courses.import');

        // 4) Sections
        Route::get('periods/{period}/programs/{program}/courses/{course}/sections', [SectionController::class,'index'])
            ->name('sections.index');
        Route::post('periods/{period}/programs/{program}/courses/{course}/sections', [SectionController::class,'store'])
            ->name('sections.store');
        Route::delete('periods/{period}/programs/{program}/courses/{course}/sections/{section}', [SectionController::class,'destroy'])
            ->name('sections.destroy');
        Route::get('periods/{period}/programs/{program}/courses/{course}/sections/download-template',
            [SectionController::class, 'downloadTemplate'])
            ->name('sections.download-template');
        Route::post('periods/{period}/programs/{program}/courses/{course}/sections/upload-csv',
            [SectionController::class, 'uploadCsv'])
            ->name('sections.upload-csv');

        // 5) Roster
        Route::get('periods/{period}/programs/{program}/courses/{course}/sections/{section}/roster',
            [RosterController::class, 'index'])
            ->name('roster.index');

        Route::post('periods/{period}/programs/{program}/courses/{course}/sections/{section}/roster',
            [RosterController::class,'store'])
            ->name('roster.store');

        Route::delete('periods/{period}/programs/{program}/courses/{course}/sections/{section}/roster/{student}',
            [RosterController::class,'destroy'])
            ->name('roster.destroy');

        Route::get('periods/{period}/programs/{program}/courses/{course}/sections/{section}/roster/download-template',
            [RosterController::class, 'downloadTemplate'])
            ->name('roster.download-template');

        Route::post('periods/{period}/programs/{program}/courses/{course}/sections/{section}/roster/upload-csv',
            [RosterController::class, 'uploadCsv'])
            ->name('roster.upload-csv');

            // Superior evaluations (chairman, dean, CED)
        Route::get('periods/{period}/superior-evaluations/{subject}',
            [SuperiorEvaluationController::class, 'edit'])
            ->name('superior-evaluations.edit');

        Route::post('periods/{period}/superior-evaluations/{subject}',
            [SuperiorEvaluationController::class, 'store'])
            ->name('superior-evaluations.store');
    });

Route::middleware(['auth','role:chairman|systemadmin'])
    ->prefix('manage')->name('manage.')
    ->group(function () {
        Route::resource('students', StudentAccountController::class)
            ->only(['index','update','destroy']);
        Route::post('students/generate', [StudentAccountController::class, 'generate'])
            ->name('students.generate');
        Route::resource('instructors', InstructorAccountController::class)
            ->only(['index','store','update','destroy']);
    });

Route::middleware(['auth','role:systemadmin'])
    ->prefix('manage')->name('manage.')
    ->group(function () {
        Route::get('system-settings', [SystemSettingsController::class, 'index'])
            ->name('system-settings.index');

        // Colleges
        Route::post('system-settings/colleges', [SystemSettingsController::class, 'storeCollege'])
            ->name('system-settings.colleges.store');
        Route::put('system-settings/colleges/{college}', [SystemSettingsController::class, 'updateCollege'])
            ->name('system-settings.colleges.update');
        Route::delete('system-settings/colleges/{college}', [SystemSettingsController::class, 'destroyCollege'])
            ->name('system-settings.colleges.destroy');

        // Departments
        Route::post('system-settings/departments', [SystemSettingsController::class, 'storeDepartment'])
            ->name('system-settings.departments.store');
        Route::put('system-settings/departments/{department}', [SystemSettingsController::class, 'updateDepartment'])
            ->name('system-settings.departments.update');
        Route::delete('system-settings/departments/{department}', [SystemSettingsController::class, 'destroyDepartment'])
            ->name('system-settings.departments.destroy');
    });

    //Routes for forms
