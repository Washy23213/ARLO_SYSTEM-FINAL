<x-layouts.app title="CMU ARLO | Alumni Registration">

    <div class="min-h-screen bg-gradient-to-b from-[#e7ece5] to-[#f4f4f4] flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-6xl bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200">

            {{-- Header --}}
            <div class="px-8 py-6 bg-[#003300] text-white">
                <h1 class="text-2xl font-bold">Alumni Registration</h1>
                <p class="text-sm text-green-100 mt-1">Please complete the multi-step form.</p>
            </div>

            {{-- Progress Bar --}}
            <div class="px-8 pt-6">
                <div class="flex items-center justify-between text-xs font-semibold text-gray-500 mb-2">
                    <span id="stepText">Step 1 of 12</span>
                    <span id="stepTitle">Verification</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div id="progressBar" class="h-2 bg-[#1c4000] w-[8%] transition-all duration-300"></div>
                </div>
            </div>

            {{-- Form --}}
            <form action="{{ route('register.submit') }}" method="POST" id="multiStepForm" class="px-8 py-8">
                @csrf

                {{-- STEP 1: Verification --}}
                <fieldset class="form-step active space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Verification</legend>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="firstname" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="firstname" name="firstname" required class="mt-1">
                        </div>
                        <div>
                            <label for="middlename" class="block text-sm font-medium text-gray-700">Middle Name</label>
                            <input type="text" id="middlename" name="middlename" required class="mt-1">
                        </div>
                        <div>
                            <label for="lastname" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="lastname" name="lastname" required class="mt-1">
                        </div>
                        <div>
                            <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                            <input type="text" id="suffix" name="suffix" class="mt-1">
                        </div>
                    </div>

                    <div>
                        <label for="yearGraduated" class="block text-sm font-medium text-gray-700">Year Graduated</label>
                        <select id="yearGraduated" name="yearGraduated" required class="mt-1"></select>
                    </div>

                    <div class="flex justify-end pt-6">
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 2: Personal Information --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Personal Information</legend>

                    <div>
                        <label for="birthdate" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                        <input type="date" id="birthdate" name="birthdate" required class="mt-1">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                            <select id="gender" name="gender" required class="mt-1">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="civilstatus" class="block text-sm font-medium text-gray-700">Civil Status</label>
                            <select id="civilstatus" name="civilstatus" required class="mt-1">
                                <option value="">Select Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 3: Address --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Address</legend>

                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <select id="country" name="country" required class="mt-1"></select>
                        <div id="countryOtherContainer" class="hidden mt-3">
                            <label for="countryOther" class="block text-sm font-medium text-gray-700">Specify Country</label>
                            <input type="text" id="countryOther" name="countryOther" class="mt-1" placeholder="Enter country">
                        </div>
                    </div>

                    <div>
                        <label for="regionOfOrigin" class="block text-sm font-medium text-gray-700">Region of Origin</label>
                        <select id="regionOfOrigin" name="regionOfOrigin" required class="mt-1"></select>
                    </div>

                    <div>
                        <label for="municipalityCity" class="block text-sm font-medium text-gray-700">Municipality/City</label>
                        <input type="text" id="municipalityCity" name="municipalityCity" required class="mt-1">
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 4: High School --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">High School Program</legend>
                    <select id="highschool" name="highschool" required class="hidden">
                        <option value="">Select Program</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <label for="yearGraduatedHS">Year Graduated:</label>
                    <select id="yearGraduatedHS" name="yearGraduatedHS" required>
                        <option value="">Select Year</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 5: Undergraduate --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Undergraduate Degree Data</legend>

                    <label for="department">College:</label>
                    <select id="department" name="department" required>
                        <option value="">Select College</option>
                    </select>

                    <label for="degree">Bachelor’s Degree Graduated:</label>
                    <select id="degree" name="degree" required>
                        <option value="">Select Degree</option>
                    </select>

                    <label for="major">Degree Major:</label>
                    <select id="major" name="major" required>
                        <option value="">Select Major</option>
                        <option value="Other">Other</option>
                    </select>

                    <label for="otherMajor">If Other, Please Specify:</label>
                    <input type="text" id="otherMajor" name="otherMajor" placeholder="Specify Major">

                    <label for="ugyearGraduated">Year Graduated:</label>
                    <select id="ugyearGraduated" name="ugyearGraduated" required>
                        <option value="">Select Year</option>
                    </select>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 6: Master --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Master’s Degree Data</legend>

                    <label for="master">Master of:</label>
                    <select id="master" name="master" required>
                        <option value="">Select Master's Program</option>
                        <option value="N/A">N/A</option>
                        <option value="Other">Other</option>
                    </select>

                    <div id="masterOtherContainer" class="hidden mt-3">
                        <label for="otherMaster">Specify Master's Program:</label>
                        <input type="text" id="otherMaster" name="otherMaster" placeholder="Enter other master's program">
                    </div>

                    <label for="mastmajor">Major in:</label>
                    <select id="mastmajor" name="mastmajor">
                        <option value="">Select Major</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <label for="othermastMajor">Others (specify):</label>
                    <input type="text" id="othermastMajor" name="othermastMajor" placeholder="Enter other major">

                    <label for="mastyearGraduated">Year Graduated:</label>
                    <select id="mastyearGraduated" name="mastyearGraduated" required>
                        <option value="">Select Year</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 7: Doctorate --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Doctorate Degree Data</legend>

                    <label for="doctorate">Doctor of:</label>
                    <select id="doctorate" name="doctorate" required>
                        <option value="">Select Doctorate Program</option>
                        <option value="N/A">N/A</option>
                        <option value="Other">Other</option>
                    </select>

                    <div id="doctorateOtherContainer" class="hidden mt-3">
                        <label for="otherDoctorate">Specify Doctorate Program:</label>
                        <input type="text" id="otherDoctorate" name="otherDoctorate" placeholder="Enter other doctorate program">
                    </div>

                    <label for="doctmajor">Major in:</label>
                    <select id="doctmajor" name="doctmajor">
                        <option value="">Select Major</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <label for="otherdoctMajor">Others (specify):</label>
                    <input type="text" id="otherdoctMajor" name="otherdoctMajor" placeholder="Enter other major">

                    <label for="doctyearGraduated">Year Graduated:</label>
                    <select id="doctyearGraduated" name="doctyearGraduated" required>
                        <option value="">Select Year</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 8: Non-Degree --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Non-Degree Data</legend>

                    <label for="nondegree">Non-Degree Program Graduated:</label>
                    <select id="nondegree" name="nondegree" required>
                        <option value="">Select Program</option>
                        <option value="N/A">N/A</option>
                        <option value="Other">Other</option>
                    </select>

                    <div id="nondegreeOtherContainer" class="hidden mt-3">
                        <label for="nondegreeOther">If Other, specify:</label>
                        <input type="text" id="nondegreeOther" name="nondegreeOther" placeholder="Specify other program">
                    </div>

                    <label for="nondegreeyearGraduated">Year Graduated:</label>
                    <select id="nondegreeyearGraduated" name="nondegreeyearGraduated" required>
                        <option value="">Select Year</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 9: Educational Background --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Educational Background</legend>

                    <label>Professional Examination Passed:</label>
                    <div id="proExamContainer"></div>

                    <div id="proExamOtherContainer" class="hidden mt-3">
                        <label for="proExamOther">If Other, specify:</label>
                        <input type="text" id="proExamOther" name="proExamOther" placeholder="Enter other exam">
                    </div>

                    <label for="proExamyear">Year Taken:</label>
                    <select id="proExamyear" name="proExamyear" required>
                        <option value="">Select Year</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <label for="proExamreason">Reason for Taking the Course:</label>
                    <select id="proExamreason" name="proExamreason" required></select>

                    <label for="trainingcount">Number of Trainings Attended After Graduation:</label>
                    <select id="trainingcount" name="trainingcount" required></select>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 10: Scholarship --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Scholarship Data</legend>
                    <div id="scholarshipContainer"></div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 11: Employment --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Employment Data</legend>

                    <label>Was the curriculum you had in college relevant to your first job? *</label>
                    <div id="curriculumRelevantContainer" class="employment-radio">
                        <input type="radio" id="curriculumYes" name="curriculumRelevant" value="Yes" required>
                        <label for="curriculumYes">Yes</label>
                        <input type="radio" id="curriculumNo" name="curriculumRelevant" value="No" required>
                        <label for="curriculumNo">No</label>
                    </div>

                    <div id="curriculumYesContainer" class="hidden mt-3">
                        <label>Which competencies learned in college you find very useful in your first job? *</label>
                        <select id="competenciesSelect" name="competencies" required>
                            <option value="">Select competency</option>
                        </select>
                        <input type="text" id="competenciesOther" name="competenciesOther" placeholder="Specify other competency" class="hidden mt-2">
                    </div>

                    <div id="curriculumNoContainer" class="hidden mt-3">
                        <label>If no, please state the reason *</label>
                        <textarea id="curriculumNoReason" name="curriculumNoReason" rows="2"></textarea>
                    </div>

                    <label>Were you employed after you graduated? *</label>
                    <div id="employedAfterGradContainer" class="employment-radio">
                        <input type="radio" id="employedYes" name="employedAfterGrad" value="Yes" required>
                        <label for="employedYes">Yes</label>
                        <input type="radio" id="employedNo" name="employedAfterGrad" value="No" required>
                        <label for="employedNo">No</label>
                    </div>

                    <label for="firstJobTime">How long did it take you to land your first job? *</label>
                    <select id="firstJobTime" name="firstJobTime" required></select>
                    <input type="text" id="firstJobTimeOther" name="firstJobTimeOther" placeholder="Specify Other" class="hidden mt-2">

                    <label for="firstJobRelated">Was your first job related to your degree? *</label>
                    <select id="firstJobRelated" name="firstJobRelated" required></select>

                    <label for="firstJobHow">How did you get your first job? *</label>
                    <select id="firstJobHow" name="firstJobHow" required class="mt-1">
                        <option value="">Select Method</option>
                    </select>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 12: Additional Information / Submit --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Additional Information</legend>

                    <div>
                        <label for="skills">Skills or Certifications Acquired:</label>
                        <textarea id="skills" name="skills" rows="3" placeholder="List your skills or certifications" class="mt-1"></textarea>
                    </div>

                    <div>
                        <label for="achievements">Achievements / Awards:</label>
                        <textarea id="achievements" name="achievements" rows="3" placeholder="List your achievements" class="mt-1"></textarea>
                    </div>

                    <div>
                        <label for="remarks">Other Remarks:</label>
                        <textarea id="remarks" name="remarks" rows="3" placeholder="Any additional remarks" class="mt-1"></textarea>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button id="submitBtn" type="submit" style="position:relative;z-index:9999;pointer-events:auto;" class="relative bg-[#003300] text-white px-6 py-2 rounded-lg hover:bg-[#1c4000] transition-colors">
                            Submit
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</x-layouts.app>