<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;

Route::get('/', [CourseController::class, 'index'])->name('courses.index');
Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
Route::get('/courses/download-template', [CourseController::class, 'downloadTemplate'])->name('courses.download-template');
Route::post('/courses/upload-csv', [CourseController::class, 'uploadCsv'])->name('courses.upload-csv');
Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');