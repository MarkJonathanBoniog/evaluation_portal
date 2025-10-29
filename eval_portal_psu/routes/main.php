<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Dashboards
    Route::get('/dashboard/student', fn () => view('dashboards.student.index'))
        ->name('dashboard.student')->middleware('role:student');

    Route::get('/dashboard/instructor', fn () => view('dashboards.instructor.index'))
        ->name('dashboard.instructor')->middleware('role:instructor');

    Route::get('/dashboard/chairman', fn () => view('dashboards.instructor.chairman.index'))
        ->name('dashboard.chairman')->middleware('role:chairman');

    Route::get('/dashboard/ced', fn () => view('dashboards.instructor.ced.index'))
        ->name('dashboard.ced')->middleware('role:ced');

    Route::get('/dashboard/systemadmin', fn () => view('dashboards.systemadmin.index'))
        ->name('dashboard.systemadmin')->middleware('role:systemadmin');
});

use App\Http\Controllers\Manage\AcademicPeriodController;
use App\Http\Controllers\Manage\ProgramController;
use App\Http\Controllers\Manage\CourseController;
use App\Http\Controllers\Manage\SectionController;
use App\Http\Controllers\Manage\RosterController;

Route::middleware(['auth','role:chairman|ced'])
    ->prefix('manage')->name('manage.')
    ->group(function () {

    // 1) Academic Periods
    Route::resource('periods', AcademicPeriodController::class)
        ->only(['index','create','store','show','destroy']); // routes: manage.periods.*

    // 2) Programs (scoped by Period)
    Route::get('periods/{period}/programs', [ProgramController::class,'index'])
        ->name('programs.index');
    Route::post('periods/{period}/programs', [ProgramController::class,'store'])
        ->name('programs.store');
    Route::delete('periods/{period}/programs/{program}', [ProgramController::class,'destroy'])
        ->name('programs.destroy');

    // 3) Courses (scoped by Period + Program)
    Route::get('periods/{period}/programs/{program}/courses', [CourseController::class,'index'])
        ->name('courses.index');
    Route::post('periods/{period}/programs/{program}/courses', [CourseController::class,'store'])
        ->name('courses.store');
    Route::delete('periods/{period}/programs/{program}/courses/{course}', [CourseController::class,'destroy'])
        ->name('courses.destroy');

    // 4) Sections (scoped by Period + Program + Course)
    Route::get('periods/{period}/programs/{program}/courses/{course}/sections', [SectionController::class,'index'])
        ->name('sections.index');
    Route::post('periods/{period}/programs/{program}/courses/{course}/sections', [SectionController::class,'store'])
        ->name('sections.store');
    Route::delete('periods/{period}/programs/{program}/courses/{course}/sections/{section}', [SectionController::class,'destroy'])
        ->name('sections.destroy');

    // 5) Roster (students under a Section) — keep full chain for breadcrumbs/redirects
    Route::get('periods/{period}/programs/{program}/courses/{course}/sections/{section}/roster', [RosterController::class, 'index'])
        ->name('roster.index');

    Route::post('periods/{period}/programs/{program}/courses/{course}/sections/{section}/roster',
        [RosterController::class,'store'])->name('roster.store');

    Route::delete('periods/{period}/programs/{program}/courses/{course}/sections/{section}/roster/{student}',
        [RosterController::class,'destroy'])->name('roster.destroy');

});
