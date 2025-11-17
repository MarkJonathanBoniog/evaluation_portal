<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function studentProfile(){ return $this->hasOne(StudentProfile::class); }
    public function instructorProfile(){ return $this->hasOne(InstructorProfile::class); }
    public function chairedDepartments(){
        return $this->belongsToMany(Department::class, 'chairman_assignments');
    }
    public function deanColleges()
    {
        return $this->belongsToMany(College::class, 'dean_assignments');
    }
    public function cedColleges(){
        return $this->belongsToMany(College::class, 'ced_assignments');
    }

    public function taughtSections()
    {
        return $this->hasMany(Section::class, 'instructor_user_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_student', 'student_user_id', 'section_id')
            ->withTimestamps();
    }

    public function chairmanAssignments()
    {
        return $this->hasMany(ChairmanAssignment::class);
    }

    public function deanAssignments()
    {
        return $this->hasMany(DeanAssignment::class);
    }

    public function cedAssignments()
    {
        return $this->hasMany(CedAssignment::class);
    }

}
