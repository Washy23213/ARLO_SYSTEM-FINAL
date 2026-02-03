@extends('components.layouts.dashboard')

@section('title', 'My Profile')

@section('content')
<div class="pl-8 pr-6 pt-6 pb-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
        <p class="text-gray-600 mt-1">View and manage your personal information</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Overview -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-center">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class='bx bx-user text-green-700 text-3xl'></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">{{ Auth::user()->firstname ?? 'Maria' }} {{ Auth::user()->middlename ?? 'S.' }} {{ Auth::user()->lastname ?? 'Dela Cruz' }}</h2>
                    <p class="text-gray-600">{{ Auth::user()->email ?? 'maria.delacruz@email.com' }}</p>
                    <p class="text-sm text-green-600 mt-2">{{ Auth::user()->degree ?? 'Bachelor of Science in Computer Engineering' }}</p>
                </div>

                <div class="mt-6 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Student ID:</span>
                        <span class="text-sm font-medium">{{ Auth::user()->student_id ?? '2021-00123' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">College:</span>
                        <span class="text-sm font-medium">{{ Auth::user()->department ?? 'College of Engineering' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Graduation Year:</span>
                        <span class="text-sm font-medium">{{ Auth::user()->yearGraduated ?? '2022' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Degree:</span>
                        <span class="text-sm font-medium">{{ Auth::user()->degree ?? 'BS Computer Engineering' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Employment Status:</span>
                        <span class="text-sm font-medium">
                            @if(Auth::user()->employedAfterGrad ?? 'Yes' == 'Yes')
                                <span class="text-green-600">Employed</span>
                            @else
                                <span class="text-red-600">Unemployed</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Location:</span>
                        <span class="text-sm font-medium">{{ Auth::user()->municipalityCity ?? 'Cebu City' }}, {{ Auth::user()->country ?? 'Philippines' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Information -->
        <div class="lg:col-span-2">
            <!-- Personal Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->firstname ?? 'Maria' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->middlename ?? 'Santos' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->lastname ?? 'Dela Cruz' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Suffix</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->suffix ?? '' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->email ?? 'maria.delacruz@email.com' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->phone ?? '+63 917 123 4567' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->birthdate ?? 'March 15, 1998' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->gender ?? 'Female' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Civil Status</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->civilstatus ?? 'Single' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->country ?? 'Philippines' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Region of Origin</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->regionOfOrigin ?? 'Region VII (Central Visayas)' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Municipality/City</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->municipalityCity ?? 'Cebu City' }}</p>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Academic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year Graduated (College)</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->yearGraduated ?? '2022' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">College/Department</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->department ?? 'College of Engineering' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Degree Program</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->degree ?? 'Bachelor of Science in Computer Engineering' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Degree Major</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->major ?? 'Computer Engineering' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Undergraduate Year Graduated</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->ugyearGraduated ?? '2022' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Master's Degree</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->master ?? 'Master of Science in Information Technology' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Master's Major</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->mastmajor ?? 'Information Technology' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Master's Year Graduated</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->mastyearGraduated ?? '2024' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Doctorate Degree</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->doctorate ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">High School</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->highschool ?? 'Cebu City National Science High School' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">High School Year Graduated</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->yearGraduatedHS ?? '2018' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Professional Exam Passed</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->proExam ?? 'Engineer Licensure Examination' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Professional Exam Year</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->proExamyear ?? '2023' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Scholarship</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->scholarship ?? 'Academic Excellence Scholarship' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Skills & Certifications</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->skills ?? 'AWS Certified Solutions Architect, Cisco CCNA' }}</p>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Employment Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employment Status</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">
                            @if(Auth::user()->employedAfterGrad ?? 'Yes' == 'Yes')
                                <span class="text-green-600 font-medium">Employed</span>
                            @elseif(Auth::user()->employedAfterGrad ?? 'Yes' == 'No')
                                <span class="text-red-600 font-medium">Unemployed</span>
                            @else
                                <span class="text-gray-600 font-medium">Unknown</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Curriculum Relevant to First Job</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->curriculumRelevant ?? 'Yes' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Useful Competencies</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->competencies ?? 'Programming, Database Management, System Analysis' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time to Land First Job</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->firstJobTime ?? '3-6 months' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Job Related to Degree</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->firstJobRelated ?? 'Yes' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">How Got First Job</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->firstJobHow ?? 'Through campus recruitment' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Position</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->current_position ?? 'Software Engineer' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->company ?? 'Tech Solutions Inc.' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->industry ?? 'Information Technology' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->location ?? 'Makati City, Philippines' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Number of Trainings Attended</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->trainingcount ?? '5' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Achievements/Awards</label>
                        <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ Auth::user()->achievements ?? 'Employee of the Year 2023, Best Project Award' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection