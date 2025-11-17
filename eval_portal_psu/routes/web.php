<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Manage\StudentEvaluationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/debug', function () {
    $admin = User::where('email', 'sys@example.com')->first();
    return [
        'roles' => \Spatie\Permission\Models\Role::pluck('name')->all(),
        'sys_admin_roles' => $admin?->getRoleNames()->all(),
        'can_manage_users' => $admin?->can('users.manage'),
    ];
});

// Student Evaluation Routes
Route::middleware(['auth'])->prefix('dashboard/student')->group(function () {

    // Show evaluation form for a specific section-student record
    Route::get('/evaluate/{sectionStudentId}', [StudentEvaluationController::class, 'show'])
        ->name('student.evaluation.show');

    // Submit evaluation
    Route::post('/evaluate/{sectionStudentId}', [StudentEvaluationController::class, 'store'])
        ->name('student.evaluation.store');
});

use App\Http\Controllers\Dashboards\InstructorClassRosterController;

Route::middleware(['auth','role:instructor'])
    ->prefix('dashboard/instructor')
    ->name('instructor.')
    ->group(function () {

        // List all sections the instructor teaches
        Route::get('class-rosters', [InstructorClassRosterController::class, 'index'])
            ->name('class-rosters.index');

        // Manage roster for a specific section
        Route::get('class-rosters/{section}', [InstructorClassRosterController::class, 'show'])
            ->name('class-rosters.show');

        // Add/remove students (manual add)
        Route::post('class-rosters/{section}/students', [InstructorClassRosterController::class, 'store'])
            ->name('class-rosters.store');

        Route::delete('class-rosters/{section}/students/{student}', [InstructorClassRosterController::class, 'destroy'])
            ->name('class-rosters.destroy');

        // (Optional) CSV download/upload for instructors – if you want them:
        Route::get('class-rosters/{section}/download-template', [InstructorClassRosterController::class, 'downloadTemplate'])
            ->name('class-rosters.download-template');
        Route::post('class-rosters/{section}/upload-csv', [InstructorClassRosterController::class, 'uploadCsv'])
            ->name('class-rosters.upload-csv');
    });



require __DIR__.'/auth.php';
require __DIR__.'/main.php';
