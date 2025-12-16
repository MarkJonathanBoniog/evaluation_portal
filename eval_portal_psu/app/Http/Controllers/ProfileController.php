<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\College;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load([
            'instructorProfile.department.college',
            'studentProfile.program.department.college',
            'studentProfile.department.college',
        ]);

        $departments = Department::with('college')->orderBy('name')->get();
        $colleges    = College::orderBy('name')->get();

        $departmentName = $user->instructorProfile?->department?->name
            ?? $user->studentProfile?->department?->name
            ?? $user->studentProfile?->program?->department?->name;

        $collegeName = $user->instructorProfile?->department?->college?->name
            ?? $user->studentProfile?->department?->college?->name
            ?? $user->studentProfile?->program?->department?->college?->name;

        return view('profile.edit', [
            'user'                => $user,
            'departmentName'      => $departmentName,
            'collegeName'         => $collegeName,
            'facultyRank'         => $user->instructorProfile?->faculty_rank ?? '',
            'departments'         => $departments,
            'colleges'            => $colleges,
            'selectedDepartmentId'=> $user->instructorProfile?->department_id
                ?? $user->studentProfile?->department_id
                ?? $user->studentProfile?->program?->department_id,
            'selectedCollegeId'   => $user->instructorProfile?->department?->college_id
                ?? $user->studentProfile?->department?->college_id
                ?? $user->studentProfile?->program?->department?->college_id,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->fill(Arr::only($validated, ['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $departmentId = $validated['department_id'] ?? null;

        if ($user->hasRole('instructor')) {
            $profile = $user->instructorProfile ?? $user->instructorProfile()->create([
                'user_id'        => $user->id,
                'instructor_uid' => $user->instructorProfile?->instructor_uid ?? '',
            ]);

            $profile->update([
                'department_id' => $departmentId ?: $profile->department_id,
                'faculty_rank'  => $validated['faculty_rank'] ?? ($profile->faculty_rank ?? ''),
            ]);
        }

        if ($user->hasRole('student')) {
            $profile = $user->studentProfile ?? $user->studentProfile()->create([
                'user_id'        => $user->id,
                'student_number' => $user->studentProfile?->student_number ?? '',
            ]);

            if ($departmentId) {
                $profile->update(['department_id' => $departmentId]);
            }
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
