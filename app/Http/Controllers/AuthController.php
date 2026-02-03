<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login'); // your login Blade
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
    }

    public function showSignup()
    {
        return view('auth.registration'); // optional signup page
    }
    public function register(Request $request)
    {
        // Validation - add all required fields
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'yearGraduated' => 'required|integer',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            // Add other validations as needed
        ]);

        // Create user with alumni data
        $user = User::create([
            'name' => $request->firstname . ' ' . $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            // Personal Information
            'firstname' => $request->firstname,
            'middlename' => $request->middlename,
            'lastname' => $request->lastname,
            'suffix' => $request->suffix,
            'yearGraduated' => $request->yearGraduated,
            'birthdate' => $request->birthdate,
            'gender' => $request->gender,
            'civilstatus' => $request->civilstatus,
            // Address
            'country' => $request->country,
            'countryOther' => $request->countryOther,
            'regionOfOrigin' => $request->regionOfOrigin,
            'municipalityCity' => $request->municipalityCity,
            // Education
            'highschool' => $request->highschool,
            'yearGraduatedHS' => $request->yearGraduatedHS,
            'department' => $request->department,
            'degree' => $request->degree,
            'major' => $request->major,
            'otherMajor' => $request->otherMajor,
            'ugyearGraduated' => $request->ugyearGraduated,
            'master' => $request->master,
            'otherMaster' => $request->otherMaster,
            'mastmajor' => $request->mastmajor,
            'othermastMajor' => $request->othermastMajor,
            'mastyearGraduated' => $request->mastyearGraduated,
            'doctorate' => $request->doctorate,
            'otherDoctorate' => $request->otherDoctorate,
            'doctmajor' => $request->doctmajor,
            'otherdoctMajor' => $request->otherdoctMajor,
            'doctyearGraduated' => $request->doctyearGraduated,
            'nondegree' => $request->nondegree,
            'nondegreeOther' => $request->nondegreeOther,
            'nondegreeyearGraduated' => $request->nondegreeyearGraduated,
            // Educational Background
            'proExam' => json_encode($request->input('proExam', [])),
            'proExamOther' => $request->proExamOther,
            'proExamyear' => $request->proExamyear,
            'proExamreason' => $request->proExamreason,
            'trainingcount' => $request->trainingcount,
            // Scholarship
            'scholarship' => json_encode($request->input('scholarship', [])),
            // Employment
            'curriculumRelevant' => $request->curriculumRelevant,
            'competencies' => $request->competencies,
            'competenciesOther' => $request->competenciesOther,
            'curriculumNoReason' => $request->curriculumNoReason,
            'employedAfterGrad' => $request->employedAfterGrad,
            'firstJobTime' => $request->firstJobTime,
            'firstJobTimeOther' => $request->firstJobTimeOther,
            'firstJobRelated' => $request->firstJobRelated,
            'firstJobHow' => $request->firstJobHow,
            // Additional
            'skills' => $request->skills,
            'achievements' => $request->achievements,
            'remarks' => $request->remarks,
        ]);

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Registration successful! Welcome to the alumni system.');
    }

}

