<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracer_records', function (Blueprint $table) {
            $table->boolean('curriculum_relevant_to_first_job')->nullable()->after('skills_used');
            $table->json('first_job_useful_competencies')->nullable()->after('curriculum_relevant_to_first_job');
            $table->string('first_job_useful_competencies_other', 200)->nullable()->after('first_job_useful_competencies');
            $table->string('curriculum_not_relevant_reason', 200)->nullable()->after('first_job_useful_competencies_other');
            $table->string('curriculum_not_relevant_reason_other', 200)->nullable()->after('curriculum_not_relevant_reason');
            $table->boolean('employed_after_graduation')->nullable()->after('curriculum_not_relevant_reason_other');
            $table->string('time_to_first_job', 100)->nullable()->after('employed_after_graduation');
            $table->string('time_to_first_job_other', 200)->nullable()->after('time_to_first_job');
            $table->string('first_job_related_to_course', 20)->nullable()->after('time_to_first_job_other');
            $table->string('first_job_finding_method', 150)->nullable()->after('first_job_related_to_course');
            $table->string('first_job_finding_method_other', 200)->nullable()->after('first_job_finding_method');
            $table->string('first_job_monthly_earnings', 100)->nullable()->after('first_job_finding_method_other');
            $table->string('first_job_duration', 100)->nullable()->after('first_job_monthly_earnings');
            $table->string('first_job_duration_other', 200)->nullable()->after('first_job_duration');
            $table->string('presently_employed', 30)->nullable()->after('first_job_duration_other');
            $table->string('present_employment_status', 100)->nullable()->after('presently_employed');
            $table->string('direct_supervisor_name', 200)->nullable()->after('present_employment_status');
            $table->string('employer_email', 200)->nullable()->after('direct_supervisor_name');
            $table->boolean('is_first_job_after_college')->nullable()->after('employer_email');
            $table->string('employment_proof_path', 255)->nullable()->after('is_first_job_after_college');
            $table->string('job_level', 100)->nullable()->after('employment_proof_path');
            $table->string('job_level_other', 200)->nullable()->after('job_level');
            $table->json('present_job_acceptance_reasons')->nullable()->after('job_level_other');
            $table->string('present_job_acceptance_other', 200)->nullable()->after('present_job_acceptance_reasons');
            $table->string('present_job_monthly_earnings', 100)->nullable()->after('present_job_acceptance_other');
            $table->boolean('has_employee_awards')->nullable()->after('present_job_monthly_earnings');
            $table->text('employee_awards')->nullable()->after('has_employee_awards');
            $table->unsignedTinyInteger('teaching_faculty_enthusiastic')->nullable()->after('employee_awards');
            $table->unsignedTinyInteger('teaching_concepts_clear')->nullable()->after('teaching_faculty_enthusiastic');
            $table->unsignedTinyInteger('teaching_faculty_knowledgeable')->nullable()->after('teaching_concepts_clear');
            $table->unsignedTinyInteger('teaching_commitment')->nullable()->after('teaching_faculty_knowledgeable');
            $table->unsignedTinyInteger('degree_program_quality')->nullable()->after('teaching_commitment');
            $table->boolean('has_curriculum_suggestions')->nullable()->after('degree_program_quality');
            $table->text('curriculum_suggestions')->nullable()->after('has_curriculum_suggestions');
            $table->boolean('has_employability_suggestions')->nullable()->after('curriculum_suggestions');
            $table->text('employability_suggestions')->nullable()->after('has_employability_suggestions');
            $table->string('facilities_satisfaction', 50)->nullable()->after('employability_suggestions');
            $table->boolean('has_facility_suggestions')->nullable()->after('facilities_satisfaction');
            $table->text('facility_suggestions')->nullable()->after('has_facility_suggestions');
            $table->unsignedTinyInteger('overall_recommend_institution')->nullable()->after('facility_suggestions');
            $table->unsignedTinyInteger('overall_objectives_met')->nullable()->after('overall_recommend_institution');
            $table->unsignedTinyInteger('overall_university_experience')->nullable()->after('overall_objectives_met');
        });
    }

    public function down(): void
    {
        Schema::table('tracer_records', function (Blueprint $table) {
            $table->dropColumn([
                'curriculum_relevant_to_first_job',
                'first_job_useful_competencies',
                'first_job_useful_competencies_other',
                'curriculum_not_relevant_reason',
                'curriculum_not_relevant_reason_other',
                'employed_after_graduation',
                'time_to_first_job',
                'time_to_first_job_other',
                'first_job_related_to_course',
                'first_job_finding_method',
                'first_job_finding_method_other',
                'first_job_monthly_earnings',
                'first_job_duration',
                'first_job_duration_other',
                'presently_employed',
                'present_employment_status',
                'direct_supervisor_name',
                'employer_email',
                'is_first_job_after_college',
                'employment_proof_path',
                'job_level',
                'job_level_other',
                'present_job_acceptance_reasons',
                'present_job_acceptance_other',
                'present_job_monthly_earnings',
                'has_employee_awards',
                'employee_awards',
                'teaching_faculty_enthusiastic',
                'teaching_concepts_clear',
                'teaching_faculty_knowledgeable',
                'teaching_commitment',
                'degree_program_quality',
                'has_curriculum_suggestions',
                'curriculum_suggestions',
                'has_employability_suggestions',
                'employability_suggestions',
                'facilities_satisfaction',
                'has_facility_suggestions',
                'facility_suggestions',
                'overall_recommend_institution',
                'overall_objectives_met',
                'overall_university_experience',
            ]);
        });
    }
};
