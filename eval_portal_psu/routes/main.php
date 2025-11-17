<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboards\ChairmanDashboardController;
use App\Http\Controllers\Dashboards\DeanDashboardController;
use App\Http\Controllers\Dashboards\CedDashboardController;

Route::middleware(['auth'])->group(function () {
    // Dashboards
    Route::get('/dashboard/student', fn () => view('dashboards.student.index'))
        ->name('dashboard.student')->middleware('role:student');

    Route::get('/dashboard/instructor', fn () => view('dashboards.instructor.index'))
        ->name('dashboard.instructor')->middleware('role:instructor');

    Route::get('/dashboard/chairman', [ChairmanDashboardController::class, 'index'])
        ->name('dashboard.chairman')
        ->middleware('role:chairman');

    Route::get('/dashboard/dean', [DeanDashboardController::class, 'index'])
        ->name('dashboard.dean')->middleware('role:dean');

    Route::get('/dashboard/ced', [CedDashboardController::class, 'index'])
        ->name('dashboard.ced')->middleware('role:ced');

    Route::get('/dashboard/systemadmin', fn () => view('dashboards.systemadmin.index'))
        ->name('dashboard.systemadmin')->middleware('role:systemadmin');
});

use App\Http\Controllers\Manage\AcademicPeriodController;
use App\Http\Controllers\Manage\ProgramController;
use App\Http\Controllers\Manage\CourseController;
use App\Http\Controllers\Manage\SectionController;
use App\Http\Controllers\Manage\RosterController;

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
    });

    //Routes for forms