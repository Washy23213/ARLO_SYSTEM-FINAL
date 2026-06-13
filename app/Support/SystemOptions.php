<?php

namespace App\Support;

class SystemOptions
{
    public static function programCatalogByDepartment(): array
    {
        return [
            'College of Agriculture' => [
                'undergraduate' => [
                    'Bachelor of Science in Agriculture',
                    'Bachelor of Science in Agribusiness Management',
                    'Bachelor of Science in Development Communication',
                ],
                'master' => [
                    'Master of Science in Agricultural Economics',
                    'Master of Science in Agricultural Education',
                    'Master of Science in Agricultural Extension',
                    'Master of Science in Agronomy',
                    'Master of Science in Animal Science',
                    'Master of Science in Entomology',
                    'Master of Science in Horticulture',
                    'Master of Science in Plant Pathology',
                    'Master of Science in Soil Science',
                    'Master of Science in Plant Breeding',
                    'Master of Science in Development Communication',
                    'Master of Science in Agribusiness Management',
                    'Master in Agribusiness Management',
                ],
                'doctorate' => [
                    'Doctor of Philosophy in Agricultural Economics',
                    'Doctor of Philosophy in Agricultural Education',
                    'Doctor of Philosophy in Agricultural Extension',
                    'Doctor of Philosophy in Agronomy',
                    'Doctor of Philosophy in Animal Science',
                    'Doctor of Philosophy in Entomology',
                    'Doctor of Philosophy in Horticulture',
                    'Doctor of Philosophy in Plant Pathology',
                    'Doctor of Philosophy in Soil Science',
                ],
                'nondegree' => [
                    'Short Course in Agriculture',
                    'Certificate of Short Course in Dairy Production and Management',
                    'Certificate of Short Course in Swine and Production',
                    'Certificate in Agricultural Sciences',
                    'Associate in Sugarcane Production and Management',
                ],
            ],
            'College of Arts and Sciences' => [
                'undergraduate' => [
                    'Bachelor of Arts in English',
                    'Bachelor of Arts in English Language Studies',
                    'Bachelor of Arts in History',
                    'Bachelor of Arts in Political Science',
                    'Bachelor of Arts in Psychology',
                    'Bachelor of Arts in Sociology',
                    'Bachelor of Science in Biology',
                    'Bachelor of Science in Chemistry',
                    'Bachelor of Science in Mathematics',
                    'Bachelor of Science in Mathematics minor in Statistics',
                    'Bachelor of Science in Physics',
                    'Bachelor of Science in Psychology',
                ],
                'master' => [
                    'Master of Science in Biology',
                    'Master of Arts in English',
                    'Master of Arts in English Language Studies',
                    'Master of Science in Mathematics',
                ],
                'doctorate' => [
                    'Doctor of Philosophy in Biology',
                    'Doctor of Philosophy in Mathematical Sciences',
                ],
                'nondegree' => [],
            ],
            'College of Business and Management' => [
                'undergraduate' => [
                    'Bachelor of Science in Accountancy',
                    'Bachelor of Science in Accounting Information System',
                    'Bachelor of Science in Business Administration',
                    'Bachelor of Science in Internal Auditing',
                    'Bachelor of Science in Management Accounting',
                    'Bachelor of Science in Office Administration',
                ],
                'master' => [
                    'Master in Business Administration',
                    'Master in Management (Business Management)',
                ],
                'doctorate' => [],
                'nondegree' => [
                    'Short Course in Business Administration',
                    'Certificate in Accounting Technology',
                ],
            ],
            'College of Education' => [
                'undergraduate' => [
                    'Bachelor of Secondary Education',
                    'Bachelor of Physical Education',
                    'Certificate in Teaching',
                ],
                'master' => [
                    'Master of Arts in Education',
                    'Master of Science in Biology Education',
                    'Master of Science in General Science Education',
                    'Master of Science in Mathematics Education',
                    'Master of Arts in Language Education',
                ],
                'doctorate' => [
                    'Doctor of Philosophy in Educational Administration',
                ],
                'nondegree' => [
                    'Short Course in Education',
                ],
            ],
            'College of Engineering' => [
                'undergraduate' => [
                    'Bachelor of Science in Agricultural Engineering',
                    'Bachelor of Science in Agricultural & Biosystems Engineering',
                    'Bachelor of Science in Civil Engineering',
                    'Bachelor of Science in Electrical Engineering',
                    'Bachelor of Science in Mechanical Engineering',
                ],
                'master' => [
                    'Master in Engineering',
                    'Master of Engineering in Civil Engineering',
                    'Master of Engineering in Energy and Environment',
                ],
                'doctorate' => [],
                'nondegree' => [],
            ],
            'College of Forestry and Environmental Science' => [
                'undergraduate' => [
                    'Bachelor of Science in Environmental Science',
                    'Bachelor of Science in Forestry',
                ],
                'master' => [
                    'Master of Science in Environmental Management',
                    'Master of Science in Forestry',
                ],
                'doctorate' => [],
                'nondegree' => [],
            ],
            'College of Human Ecology' => [
                'undergraduate' => [
                    'Bachelor of Science in Home Economics',
                    'Bachelor of Science in Food Technology',
                    'Bachelor of Science in Hotel and Restaurant Management',
                    'Bachelor of Science in Hospitality Management',
                    'Bachelor of Science in Nutrition and Dietetics',
                ],
                'master' => [
                    'Master of Science in Food Science',
                    'Master of Science in Home Economics Education',
                    'Master of Arts in Home Economics Education',
                    'Master of Arts in Home Economics Extension',
                ],
                'doctorate' => [],
                'nondegree' => [
                    'Certificate in Cookery NC II',
                    'Certificate in Food Processing NC II',
                    'Certificate in Food and Beverage Services NC II',
                ],
            ],
            'College of Information Sciences and Computing' => [
                'undergraduate' => [
                    'Bachelor of Science in Information Technology',
                ],
                'master' => [],
                'doctorate' => [],
                'nondegree' => [],
            ],
            'College of Nursing' => [
                'undergraduate' => [
                    'Bachelor of Science in Nursing',
                ],
                'master' => [],
                'doctorate' => [],
                'nondegree' => [
                    'Certificate in Health Care Services NC II',
                ],
            ],
            'College of Veterinary Medicine' => [
                'undergraduate' => [
                    'Doctor of Veterinary Medicine',
                ],
                'master' => [
                    'Master of Veterinary Science (Research Track)',
                    'Master of Science in Veterinary Medicine (Academic Track)',
                ],
                'doctorate' => [],
                'nondegree' => [],
            ],
        ];
    }

    public static function departments(): array
    {
        return array_keys(self::programCatalogByDepartment());
    }

    public static function departmentProgramOptions(): array
    {
        $catalog = [];

        foreach (self::programCatalogByDepartment() as $department => $groups) {
            $catalog[$department] = self::flattenUniquePrograms($groups);
        }

        return $catalog;
    }

    public static function programsForDepartment(?string $department): array
    {
        $department = self::canonical($department, self::departments());

        if ($department === null) {
            return [];
        }

        return self::departmentProgramOptions()[$department] ?? [];
    }

    public static function programBelongsToDepartment(?string $department, ?string $program): bool
    {
        $department = self::canonical($department, self::departments());
        $program = self::clean($program);

        if ($department === null || $program === null) {
            return false;
        }

        foreach (self::programsForDepartment($department) as $candidate) {
            if (self::programsMatch($program, $candidate)) {
                return true;
            }
        }

        foreach (self::legacyProgramsForDepartment($department) as $candidate) {
            if (self::programsMatch($program, $candidate)) {
                return true;
            }
        }

        return false;
    }

    public static function undergraduatePrograms(): array
    {
        return collect(self::programCatalogByDepartment())
            ->pluck('undergraduate')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    public static function industries(): array
    {
        return [
            'Information Technology',
            'Education',
            'Healthcare',
            'Finance',
            'Government',
            'Manufacturing',
            'Agriculture',
            'Retail',
            'Business Services',
            'Hospitality',
            'Others',
        ];
    }

    public static function locations(): array
    {
        return [
            'Bukidnon',
            'Cagayan de Oro',
            'Davao City',
            'Manila',
            'Cebu',
            'Remote',
            'Abroad',
            'Others',
        ];
    }

    public static function jobTypes(): array
    {
        return [
            'Full-time',
            'Part-time',
            'Contract',
            'Internship',
            'Freelance',
            'Temporary',
        ];
    }

    public static function employmentTypes(): array
    {
        return [
            'Full-time',
            'Part-time',
            'Employed',
            'Self-Employed',
            'Unemployed',
        ];
    }

    public static function salaryRanges(): array
    {
        return [
            'Below 15,000.00',
            '15,000.00 - 24,999.00',
            '25,000.00 - 34,999.00',
            '35,000.00 - 49,999.00',
            '50,000.00 and above',
            'Negotiable',
            'Prefer not to say',
        ];
    }

    public static function educationLevels(): array
    {
        return [
            "Bachelor's Degree",
            "Master's Degree",
            'Doctorate Degree',
            'Vocational / Technical',
            'Certificate Program',
            'Others',
        ];
    }

    public static function scholarshipNames(): array
    {
        return [
            'Institutional Scholarship',
            'ACEF - GIAHEP',
            'CHED Full Merit',
            'CHED Half Merit',
            'Crop Protection Association of the Philippines',
            'Del Monte Foundation',
            'DOST - RA 7687 - RA 10612',
            'CHED Tulong Dunong',
            'CHED Tertiary Education Subsidy',
            'GSIS',
            'Insular Foundation',
            'CHED-SSGP-SRA',
            'CHED K-12 Transition Program',
            'CHED SIKAP',
            'DOLE',
            'Other',
        ];
    }

    public static function curriculumCompetencies(): array
    {
        return [
            'Communication skills',
            'Human relations skills',
            'Entrepreneurial skills',
            'Information Technology skills',
            'Problem-solving skills',
            'Critical Thinking skills',
            'Other',
        ];
    }

    public static function curriculumNotRelevantReasons(): array
    {
        return [
            'Job was not related to my course',
            'The job needed different skills',
            'I learned more on the job',
            'There was no matching job available',
            'Other',
        ];
    }

    public static function firstJobTimeOptions(): array
    {
        return [
            'Less than a month',
            '1 to 6 months',
            '7 to 11 months',
            '1 year to less than 2 years',
            '2 years to less than 3 years',
            '3 years to less than 4 years',
            'N/A',
            'Other',
        ];
    }

    public static function firstJobRelatedOptions(): array
    {
        return ['Yes', 'No', 'N/A'];
    }

    public static function firstJobFindingMethods(): array
    {
        return [
            'Response to an advertisement',
            'As walk-in applicants',
            'Recommended by someone',
            'Information from friends',
            "Arranged by school's job placement officer",
            'Family Business',
            'Job Fair of Public Employment Service Office (PESO)',
            'N/A',
            'Other',
        ];
    }

    public static function firstJobEarningsOptions(): array
    {
        return [
            'Below P5,000.00',
            'P5,000.00 to less than P10,000.00',
            'P10,000.00 to less than P15,000.00',
            'P15,000.00 to less than P20,000.00',
            'P20,000.00 to less than P25,000.00',
            'P25,000.00 and above',
            'N/A',
        ];
    }

    public static function firstJobDurationOptions(): array
    {
        return [
            'Less than a month',
            '1 to 6 months',
            '7 to 11 months',
            '1 year to less than 2 years',
            '2 years to less than 3 years',
            '3 years to less than 4 years',
            'More than 4 years',
            'N/A',
            'Other',
        ];
    }

    public static function presentEmploymentStatuses(): array
    {
        return [
            'Regular or Permanent',
            'Temporary',
            'Casual',
            'Contractual',
            'Self-employed',
            'Job Order',
        ];
    }

    public static function businessLines(): array
    {
        return [
            'Agriculture, Hunting, and Forestry',
            'Fishing',
            'Mining and Quarrying',
            'Electricity, Gas and Water Supply',
            'Construction',
            'Wholesale and Retail Trade',
            'Hotels and Restaurants',
            'Transport Storage and Communication',
            'Financial Intermediation',
            'Real Estate, Renting and Business Activities',
            'Public Administration and Defense; Compulsory Social Security',
            'Education',
            'Health and Social Work',
            'Other Community, Social and Personal Service Activities',
            'Private Households with Employed Persons',
            'Other',
        ];
    }

    public static function jobLevels(): array
    {
        return [
            'Rank or Clerical',
            'Professional, Technical or Supervisory',
            'Managerial or Executive',
            'Self-employed',
            'Other',
        ];
    }

    public static function presentJobAcceptanceReasons(): array
    {
        return [
            'Salaries & Benefits',
            'Career challenge',
            'Related to special skills',
            'Related to course or program of study',
            'Proximity of residence',
            'Peer influence',
            'Other',
        ];
    }

    public static function presentJobEarningsOptions(): array
    {
        return [
            'Below P10,000.00',
            'P10,000.00 to less than P20,000.00',
            'P20,000.00 to less than P30,000.00',
            'P30,000.00 to less than P40,000.00',
            'P40,000.00 to less than P50,000.00',
            'P50,000.00 to less than P60,000.00',
            'P60,000.00 to less than P70,000.00',
            'P70,000.00 to less than P80,000.00',
            'P80,000.00 to less than P90,000.00',
            'P90,000.00 to less than P100,000.00',
            'P100,000.00 and above',
        ];
    }

    public static function salaryRangeLabel(null|string $value): ?string
    {
        $cleaned = self::clean($value);
        if ($cleaned === null) {
            return null;
        }

        $canonical = self::canonical($cleaned, self::salaryRanges());
        if (in_array($canonical, self::salaryRanges(), true)) {
            return $canonical;
        }

        $aliases = [
            'below 15,000' => 'Below 15,000.00',
            '15,000 - 24,999' => '15,000.00 - 24,999.00',
            '25,000 - 34,999' => '25,000.00 - 34,999.00',
            '35,000 - 49,999' => '35,000.00 - 49,999.00',
            '50,000 and above' => '50,000.00 and above',
        ];

        $lower = mb_strtolower($cleaned);
        if (isset($aliases[$lower])) {
            return $aliases[$lower];
        }

        if (preg_match('/^(?:P\s*)?([\d,]+(?:\.\d{1,2})?)\s*-\s*(?:P\s*)?([\d,]+(?:\.\d{1,2})?)$/i', $cleaned, $matches)) {
            return self::formatMoneyLabel($matches[1]) . ' - ' . self::formatMoneyLabel($matches[2]);
        }

        if (preg_match('/^Below\s+(?:P\s*)?([\d,]+(?:\.\d{1,2})?)$/i', $cleaned, $matches)) {
            return 'Below ' . self::formatMoneyLabel($matches[1]);
        }

        if (preg_match('/^(?:P\s*)?([\d,]+(?:\.\d{1,2})?)\s+and above$/i', $cleaned, $matches)) {
            return self::formatMoneyLabel($matches[1]) . ' and above';
        }

        return $cleaned;
    }

    public static function firstJobEarningsLabel(null|string $value): ?string
    {
        return self::earningsLabel($value, self::firstJobEarningsOptions(), [
            'p5,000.00 to less than 10,000.00' => 'P5,000.00 to less than P10,000.00',
        ]);
    }

    public static function presentJobEarningsLabel(null|string $value): ?string
    {
        return self::earningsLabel($value, self::presentJobEarningsOptions(), [
            'p10,000.00 to less than 20,000.00' => 'P10,000.00 to less than P20,000.00',
        ]);
    }

    public static function presentJobEarningsRangeForIncome(null|int|float|string $income): ?string
    {
        if ($income === null || $income === '') {
            return null;
        }

        $cleaned = self::clean((string) $income);
        if ($cleaned === null) {
            return null;
        }

        $earningsLabel = self::presentJobEarningsLabel($cleaned);
        if (in_array($earningsLabel, self::presentJobEarningsOptions(), true)) {
            return $earningsLabel;
        }

        $numericIncome = str_replace(',', '', $cleaned);
        if (! preg_match('/^\d+(?:\.\d+)?$/', $numericIncome)) {
            return $earningsLabel;
        }

        $amount = (float) $numericIncome;

        return match (true) {
            $amount < 10000 => 'Below P10,000.00',
            $amount < 20000 => 'P10,000.00 to less than P20,000.00',
            $amount < 30000 => 'P20,000.00 to less than P30,000.00',
            $amount < 40000 => 'P30,000.00 to less than P40,000.00',
            $amount < 50000 => 'P40,000.00 to less than P50,000.00',
            $amount < 60000 => 'P50,000.00 to less than P60,000.00',
            $amount < 70000 => 'P60,000.00 to less than P70,000.00',
            $amount < 80000 => 'P70,000.00 to less than P80,000.00',
            $amount < 90000 => 'P80,000.00 to less than P90,000.00',
            $amount < 100000 => 'P90,000.00 to less than P100,000.00',
            default => 'P100,000.00 and above',
        };
    }

    public static function facilitiesSatisfactionOptions(): array
    {
        return [
            'Very Satisfied',
            'Satisfied',
            'Not Satisfied',
        ];
    }

    public static function agreementScaleOptions(): array
    {
        return [
            5 => 'Strongly Agree',
            4 => 'Mostly Agree',
            3 => 'Neither Agree nor Disagree',
            2 => 'Mostly Disagree',
            1 => 'Strongly Disagree',
        ];
    }

    public static function announcementTypes(): array
    {
        return ['Event', 'News', 'Update', 'Alert', 'Job'];
    }

    public static function announcementPriorities(): array
    {
        return ['Low', 'Medium', 'High', 'Urgent'];
    }

    public static function audiences(): array
    {
        return ['All', 'Students', 'Alumni', 'Faculty', 'Staff', 'Employers'];
    }

    public static function feedbackTypes(): array
    {
        return ['Bug Report', 'Feature Request', 'General Feedback', 'Support'];
    }

    public static function feedbackStatuses(): array
    {
        return ['Open', 'In Progress', 'Resolved', 'Closed'];
    }

    public static function feedbackPriorities(): array
    {
        return ['Low', 'Medium', 'High'];
    }

    public static function canonical(?string $value, array $options): ?string
    {
        $normalized = self::clean($value);
        if ($normalized === null) {
            return null;
        }

        foreach ($options as $option) {
            if (mb_strtolower($normalized) === mb_strtolower($option)) {
                return $option;
            }
        }

        return $normalized;
    }

    public static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/\s+/', ' ', trim($value));

        return $cleaned === '' ? null : $cleaned;
    }

    public static function salaryRangeForIncome(null|int|float|string $income): ?string
    {
        if ($income === null || $income === '') {
            return null;
        }

        $cleaned = self::clean((string) $income);
        if ($cleaned === null) {
            return null;
        }

        $numericIncome = str_replace(',', '', $cleaned);
        if (! preg_match('/^\d+(?:\.\d+)?$/', $numericIncome)) {
            return self::salaryRangeLabel($cleaned);
        }

        $amount = (float) $numericIncome;

        return match (true) {
            $amount < 15000 => 'Below 15,000.00',
            $amount < 25000 => '15,000.00 - 24,999.00',
            $amount < 35000 => '25,000.00 - 34,999.00',
            $amount < 50000 => '35,000.00 - 49,999.00',
            default => '50,000.00 and above',
        };
    }

    protected static function earningsLabel(null|string $value, array $options, array $aliases = []): ?string
    {
        $cleaned = self::clean($value);
        if ($cleaned === null) {
            return null;
        }

        $canonical = self::canonical($cleaned, $options);
        if (in_array($canonical, $options, true)) {
            return $canonical;
        }

        return $aliases[mb_strtolower($cleaned)] ?? $cleaned;
    }

    protected static function formatMoneyLabel(int|float|string $amount): string
    {
        $numeric = str_replace(',', '', (string) $amount);

        return number_format((float) $numeric, 2, '.', ',');
    }

    protected static function flattenUniquePrograms(array $groups): array
    {
        return collect($groups)
            ->flatten()
            ->filter(fn ($program) => is_string($program) && trim($program) !== '')
            ->map(fn ($program) => trim($program))
            ->unique()
            ->values()
            ->all();
    }

    protected static function programsMatch(string $submitted, string $candidate): bool
    {
        $submittedAliases = self::programAliases($submitted);
        $candidateAliases = self::programAliases($candidate);

        return $submittedAliases->intersect($candidateAliases)->isNotEmpty();
    }

    protected static function programAliases(string $program)
    {
        $clean = self::clean($program);

        if ($clean === null) {
            return collect();
        }

        $aliases = collect([$clean]);

        if (preg_match('/^Bachelor of Science in (.+)$/i', $clean, $matches)) {
            $aliases->push('BS ' . trim($matches[1]));
        }

        if (preg_match('/^BS (.+)$/i', $clean, $matches)) {
            $aliases->push('Bachelor of Science in ' . trim($matches[1]));
        }

        if (mb_strtolower($clean) === 'bsit') {
            $aliases->push('BS Information Technology');
            $aliases->push('Bachelor of Science in Information Technology');
        }

        return $aliases
            ->map(fn ($alias) => mb_strtolower((string) self::clean($alias)))
            ->filter()
            ->unique()
            ->values();
    }

    protected static function legacyProgramsForDepartment(string $department): array
    {
        return [
            'College of Engineering' => [
                'BS Information Technology',
                'Bachelor of Science in Information Technology',
                'BSIT',
            ],
            'College of Education' => [
                'BS Education',
                'Bachelor of Science in Education',
            ],
        ][$department] ?? [];
    }
}
