<?php

namespace Database\Seeders\Demo;

/**
 * The raw material for PRUK demo data: fictional places, data custodians,
 * study archetypes and a bank of longitudinal population study (LPS)
 * variables. Every name here is invented; none is a real study or person.
 *
 * Option values (wave types, access routes, ...) match what the search UI
 * offers, so "Refine further" conditions find the demo data.
 */
final class PrukDemoCatalogue
{
    public const DELIMITER = ';,;';

    /** Fictional custodians, by the nation their studies cover. */
    public const CUSTODIANS = [
        'England' => [
            'Northshore Population Research Institute',
            'Eastvale Centre for Longitudinal Studies',
            'Harrowmere Institute of Child Health',
            'Brindlemoor Ageing Research Group',
            'Silverbirch Institute for Women\'s Health',
            'Wrenford Mental Health Observatory',
        ],
        'Scotland' => ['Glenmarrow Population Health Unit'],
        'Wales' => ['Taffridge Cohort Collaboration'],
        'Northern Ireland' => ['Loughmere Lifecourse Centre'],
        'United Kingdom' => [
            'Four Nations Social Research Centre',
            'Halberton Biobank Consortium',
            'Kestrel Twin Registry Partnership',
        ],
    ];

    /** Fictional places, by nation. */
    public const PLACES = [
        'England' => [
            'Northshore', 'Eastvale', 'Harrowmere', 'Kestrel Bay', 'Ashby Fen', 'Brindlemoor',
            'Wrenford', 'Silverbirch', 'Coldmere', 'Ravensholt', 'Pellingham', 'Oakhurst Vale',
        ],
        'Scotland' => ['Glenmarrow', 'Strathcairn', 'Ardvallen', 'Lochbrae'],
        'Wales' => ['Taffridge', 'Cwmderyn', 'Penrhosfa', 'Aberwennol'],
        'Northern Ireland' => ['Loughmere', 'Carrowdene', 'Ballyfern', 'Drumcarra'],
    ];

    /**
     * Kinds of study. `ages` is the age range at baseline, `size` the
     * baseline sample, `interval` years between waves, `domains` the variable
     * domains it collects (the first ones most often), `ukWide` whether it
     * may cover the whole UK.
     */
    public const ARCHETYPES = [
        'birth' => [
            'name' => 'Birth Cohort',
            'ages' => [0, 0], 'size' => [2000, 18000], 'interval' => [2, 5], 'start' => [1972, 2014],
            'familyBased' => true, 'ukWide' => true,
            'domains' => ['maternal', 'child', 'anthropometry', 'mental', 'education', 'socioeconomic', 'lifestyle', 'biomarkers'],
            'keywords' => ['birth cohort', 'child health', 'life course', 'intergenerational'],
            'population' => 'babies born and their parents',
        ],
        'lifecourse' => [
            'name' => 'Life Course Study',
            'ages' => [20, 55], 'size' => [5000, 25000], 'interval' => [2, 5], 'start' => [1985, 2016],
            'familyBased' => false, 'ukWide' => false,
            'domains' => ['mental', 'cardio', 'lifestyle', 'socioeconomic', 'education', 'healthcare', 'anthropometry', 'biomarkers'],
            'keywords' => ['adult cohort', 'life course', 'chronic disease', 'health behaviours'],
            'population' => 'adults',
        ],
        'ageing' => [
            'name' => 'Ageing Study',
            'ages' => [50, 75], 'size' => [3000, 15000], 'interval' => [2, 4], 'start' => [1990, 2015],
            'familyBased' => false, 'ukWide' => true,
            'domains' => ['ageing', 'cognition', 'cardio', 'mental', 'healthcare', 'biomarkers', 'socioeconomic', 'anthropometry'],
            'keywords' => ['ageing', 'older adults', 'dementia', 'frailty', 'retirement'],
            'population' => 'adults aged 50 and over',
        ],
        'panel' => [
            'name' => 'Household Panel',
            'ages' => [0, 99], 'size' => [10000, 40000], 'interval' => [1, 1], 'start' => [1991, 2012],
            'familyBased' => true, 'ukWide' => true,
            'domains' => ['socioeconomic', 'education', 'mental', 'healthcare', 'lifestyle', 'environment', 'covid'],
            'keywords' => ['household panel', 'social survey', 'income', 'employment', 'wellbeing'],
            'population' => 'members of private households',
        ],
        'youth' => [
            'name' => 'Adolescent Cohort',
            'ages' => [11, 14], 'size' => [1500, 9000], 'interval' => [1, 3], 'start' => [1995, 2018],
            'familyBased' => false, 'ukWide' => false,
            'domains' => ['mental', 'education', 'lifestyle', 'child', 'environment', 'anthropometry'],
            'keywords' => ['adolescence', 'young people', 'schools', 'mental health'],
            'population' => 'secondary school pupils',
        ],
        'womens' => [
            'name' => 'Women\'s Health Panel',
            'ages' => [18, 45], 'size' => [3000, 12000], 'interval' => [1, 3], 'start' => [2000, 2019],
            'familyBased' => false, 'ukWide' => false,
            'domains' => ['maternal', 'mental', 'cardio', 'lifestyle', 'healthcare', 'biomarkers'],
            'keywords' => ['women\'s health', 'reproductive health', 'pregnancy', 'menopause'],
            'population' => 'women',
        ],
        'twins' => [
            'name' => 'Twins Register',
            'ages' => [18, 80], 'size' => [4000, 14000], 'interval' => [3, 6], 'start' => [1993, 2010],
            'familyBased' => true, 'ukWide' => true,
            'domains' => ['genetics', 'mental', 'cardio', 'anthropometry', 'cognition', 'biomarkers'],
            'keywords' => ['twins', 'heritability', 'genetic epidemiology', 'family-based cohort'],
            'population' => 'adult twin pairs',
        ],
        'workforce' => [
            'name' => 'Workforce Cohort',
            'ages' => [20, 60], 'size' => [4000, 16000], 'interval' => [2, 4], 'start' => [1988, 2016],
            'familyBased' => false, 'ukWide' => false,
            'domains' => ['education', 'mental', 'lifestyle', 'socioeconomic', 'cardio', 'healthcare'],
            'keywords' => ['occupational health', 'work', 'employment', 'stress'],
            'population' => 'employees',
        ],
    ];

    /** Sub-studies a cohort may run between waves. */
    public const SUB_STUDIES = [
        ['label' => 'Biomarker sub-study', 'domains' => ['biomarkers', 'cardio'], 'samples' => ['Whole blood', 'Serum', 'Plasma'], 'share' => [0.2, 0.5]],
        ['label' => 'Genotyping', 'domains' => ['genetics'], 'samples' => ['DNA'], 'share' => [0.3, 0.8]],
        ['label' => 'COVID-19 survey', 'domains' => ['covid', 'mental'], 'samples' => [], 'share' => [0.3, 0.6]],
        ['label' => 'Accelerometry sub-study', 'domains' => ['lifestyle', 'anthropometry'], 'samples' => [], 'share' => [0.1, 0.3]],
        ['label' => 'Linked education records', 'domains' => ['education'], 'samples' => [], 'share' => [0.6, 0.9]],
        ['label' => 'Linked health records', 'domains' => ['healthcare'], 'samples' => [], 'share' => [0.6, 0.95]],
        ['label' => 'Mental health deep-dive', 'domains' => ['mental'], 'samples' => ['Saliva'], 'share' => [0.1, 0.3]],
        ['label' => 'Cognitive assessment sub-study', 'domains' => ['cognition'], 'samples' => [], 'share' => [0.15, 0.4]],
    ];

    /** Sample types recorded for waves that collected biosamples. */
    public const SAMPLE_TYPES = ['Whole blood', 'Serum', 'Plasma', 'Saliva', 'Urine', 'DNA', 'Buccal swab'];

    public const STANDARDS = ['DDI', 'SNOMED CT', 'ICD-10', 'OMOP CDM', 'Local'];

    /** Access routes, each with the lead times that suit it. */
    public const ACCESS = [
        'TRE/SDE' => ['1-2 months', '2-6 months', 'More than 6 months'],
        'Direct access' => ['2-4 weeks', '1-2 months', '2-6 months'],
        'Open access' => ['Less than 1 week', '1-2 weeks'],
        'Varies based on project' => ['Variable', 'Other', 'Not applicable'],
    ];

    /**
     * Variable domains: the table they appear in, the GWDM data type and
     * sub-type they add, keywords, and variables as [name, data type,
     * description]. Variables marked `core` are always collected, so common
     * searches ("pregnancy outcomes", "depression measures") reliably match.
     */
    public const DOMAINS = [
        'demographics' => [
            'table' => 'Household and demographics', 'type' => 'Demographics', 'subType' => 'Demographics',
            'keywords' => ['demographics'],
            'core' => ['age_at_interview', 'sex_at_birth'],
            'variables' => [
                ['age_at_interview', 'integer', 'Age in completed years at the interview date.'],
                ['sex_at_birth', 'string', 'Sex recorded at birth (1 = male, 2 = female).'],
                ['gender_identity', 'string', 'Self-described gender identity, from a single question with a free-text option.'],
                ['ethnic_group_ons', 'string', 'Self-reported ethnic group using ONS 2011 census categories.'],
                ['marital_status', 'string', 'Legal marital or civil partnership status.'],
                ['household_size', 'integer', 'Number of people usually resident in the household.'],
                ['region_of_residence', 'string', 'Government office region or nation of current residence.'],
                ['country_of_birth', 'string', 'Country of birth, coded to ISO 3166.'],
                ['first_language', 'string', 'First language learned at home in childhood.'],
                ['religion', 'string', 'Religion or belief, from the census question.'],
            ],
        ],
        'socioeconomic' => [
            'table' => 'Socioeconomic circumstances', 'type' => 'Socioeconomic', 'subType' => 'Income and deprivation',
            'keywords' => ['socioeconomic position', 'income', 'deprivation'],
            'core' => ['household_income_band'],
            'variables' => [
                ['household_income_band', 'string', 'Net weekly household income in banded categories, equivalised.'],
                ['imd_quintile', 'integer', 'Index of Multiple Deprivation quintile of the home postcode (1 = most deprived).'],
                ['nssec_class', 'string', 'National Statistics Socio-economic Classification (NS-SEC) of the household reference person.'],
                ['housing_tenure', 'string', 'Tenure of the home: owned outright, mortgaged, social rent, private rent.'],
                ['receives_benefits', 'boolean', 'Whether anyone in the household receives means-tested benefits.'],
                ['food_insecurity', 'string', 'Household food insecurity in the last 12 months (USDA six-item short form).'],
                ['financial_strain', 'string', 'Self-rated ease of managing financially: living comfortably to finding it very difficult.'],
                ['car_access', 'integer', 'Number of cars or vans available to the household.'],
                ['overcrowding', 'boolean', 'Household below the bedroom standard for its size.'],
                ['fuel_poverty', 'boolean', 'Unable to keep the home adequately warm because of cost.'],
            ],
        ],
        'education' => [
            'table' => 'Education and employment', 'type' => 'Socioeconomic', 'subType' => 'Education',
            'keywords' => ['education', 'employment'],
            'core' => ['highest_qualification'],
            'variables' => [
                ['highest_qualification', 'string', 'Highest educational qualification, mapped to RQF levels.'],
                ['employment_status', 'string', 'Current economic activity: employed, self-employed, unemployed, student, retired, inactive.'],
                ['hours_worked_week', 'decimal', 'Usual hours worked per week in the main job, including paid overtime.'],
                ['occupation_soc2020', 'string', 'Occupation of the main job coded to SOC 2020 unit group.'],
                ['shift_work', 'boolean', 'Whether the main job involves regular night or rotating shifts.'],
                ['job_strain', 'decimal', 'Job strain score from the demand-control questionnaire (higher = more strain).'],
                ['ks2_maths_level', 'string', 'Key Stage 2 maths attainment level, from linked National Pupil Database records.'],
                ['ks4_attainment8', 'decimal', 'Attainment 8 score at Key Stage 4, from linked education records.'],
                ['school_absence_rate', 'decimal', 'Percentage of school sessions missed in the academic year.'],
                ['special_educational_needs', 'boolean', 'Recorded special educational needs or EHC plan.'],
                ['neet_status', 'boolean', 'Not in education, employment or training at age 16-24.'],
            ],
        ],
        'maternal' => [
            'table' => 'Pregnancy and birth', 'type' => 'Health and disease', 'subType' => 'Maternal health',
            'keywords' => ['pregnancy', 'birth outcomes', 'maternal health'],
            'core' => ['birthweight_g', 'gestational_age_weeks'],
            'variables' => [
                ['birthweight_g', 'integer', 'Birthweight of the baby in grams, from the birth record.'],
                ['gestational_age_weeks', 'decimal', 'Gestational age at delivery in completed weeks, from dating scan.'],
                ['preterm_birth', 'boolean', 'Delivery before 37 completed weeks of gestation.'],
                ['parity', 'integer', 'Number of previous pregnancies carried beyond 24 weeks.'],
                ['gravidity', 'integer', 'Total number of pregnancies, including the current one.'],
                ['mode_of_delivery', 'string', 'Mode of delivery: spontaneous vaginal, instrumental, planned or emergency caesarean.'],
                ['pre_eclampsia', 'boolean', 'Diagnosis of pre-eclampsia in this pregnancy.'],
                ['gestational_diabetes', 'boolean', 'Diagnosis of gestational diabetes in this pregnancy.'],
                ['epds_total', 'integer', 'Edinburgh Postnatal Depression Scale total score (0-30); 13 or more suggests probable depression.'],
                ['smoking_in_pregnancy', 'string', 'Maternal smoking during pregnancy: never, stopped in first trimester, continued.'],
                ['breastfeeding_duration_weeks', 'integer', 'Duration of any breastfeeding in weeks.'],
                ['maternal_age_at_delivery', 'integer', 'Mother\'s age in years at the birth.'],
                ['miscarriage_history', 'integer', 'Number of previous miscarriages reported.'],
            ],
        ],
        'child' => [
            'table' => 'Child development', 'type' => 'Health and disease', 'subType' => 'Child development',
            'keywords' => ['child development', 'early years'],
            'core' => ['sdq_total'],
            'variables' => [
                ['sdq_total', 'integer', 'Strengths and Difficulties Questionnaire total difficulties score (0-40), parent report.'],
                ['sdq_emotional', 'integer', 'SDQ emotional symptoms subscale (0-10).'],
                ['sdq_conduct', 'integer', 'SDQ conduct problems subscale (0-10).'],
                ['asq_communication', 'integer', 'Ages and Stages Questionnaire communication domain score.'],
                ['bayley_cognitive', 'integer', 'Bayley Scales of Infant Development cognitive composite score.'],
                ['reading_age_months', 'integer', 'Reading age in months from a standardised reading test.'],
                ['bmi_z_score', 'decimal', 'BMI z-score against UK90 growth reference.'],
                ['screen_time_hours', 'decimal', 'Average daily recreational screen time in hours.'],
                ['childcare_type', 'string', 'Main type of childcare used before school entry.'],
                ['adverse_childhood_experiences', 'integer', 'Count of adverse childhood experiences (0-10), retrospective report.'],
            ],
        ],
        'mental' => [
            'table' => 'Mental health and wellbeing', 'type' => 'Mental health', 'subType' => 'Mental health',
            'keywords' => ['mental health', 'depression', 'anxiety', 'wellbeing'],
            'core' => ['phq9_total', 'gad7_total'],
            'variables' => [
                ['phq9_total', 'integer', 'Patient Health Questionnaire-9 total depression score (0-27); 10 or more indicates moderate depression.'],
                ['gad7_total', 'integer', 'Generalised Anxiety Disorder-7 total score (0-21).'],
                ['ghq12_score', 'integer', 'General Health Questionnaire-12 caseness score (0-12) for common mental disorder.'],
                ['wemwbs_total', 'integer', 'Warwick-Edinburgh Mental Wellbeing Scale total score (14-70).'],
                ['kessler6_distress', 'integer', 'Kessler K6 psychological distress score (0-24).'],
                ['ever_diagnosed_depression', 'boolean', 'Ever told by a doctor that they had depression.'],
                ['antidepressant_use', 'boolean', 'Currently taking antidepressant medication.'],
                ['self_harm_ever', 'boolean', 'Lifetime history of self-harm, self-reported.'],
                ['loneliness_ucla3', 'integer', 'Three-item UCLA loneliness scale score (3-9).'],
                ['life_satisfaction', 'integer', 'Overall life satisfaction on a 0-10 scale (ONS4).'],
                ['mfq_short', 'integer', 'Short Mood and Feelings Questionnaire score (0-26), young people.'],
                ['perceived_stress_pss10', 'integer', 'Perceived Stress Scale-10 total score (0-40).'],
            ],
        ],
        'cognition' => [
            'table' => 'Cognitive assessment', 'type' => 'Health and disease', 'subType' => 'Cognition',
            'keywords' => ['cognition', 'cognitive decline', 'dementia'],
            'core' => ['word_recall_immediate'],
            'variables' => [
                ['word_recall_immediate', 'integer', 'Number of words recalled immediately from a 10-word list.'],
                ['word_recall_delayed', 'integer', 'Number of words recalled after a five-minute delay.'],
                ['verbal_fluency_animals', 'integer', 'Animals named in one minute (semantic verbal fluency).'],
                ['mmse_total', 'integer', 'Mini-Mental State Examination total score (0-30).'],
                ['moca_total', 'integer', 'Montreal Cognitive Assessment total score (0-30).'],
                ['reaction_time_ms', 'integer', 'Mean simple reaction time in milliseconds over 20 trials.'],
                ['trail_making_b_seconds', 'decimal', 'Time in seconds to complete Trail Making Test part B.'],
                ['dementia_diagnosis', 'boolean', 'Dementia diagnosis from linked primary care records.'],
                ['subjective_memory_decline', 'boolean', 'Self-reported worsening memory over the past two years.'],
            ],
        ],
        'anthropometry' => [
            'table' => 'Clinic measurements', 'type' => 'Health and disease', 'subType' => 'Anthropometry',
            'keywords' => ['anthropometry', 'obesity', 'body composition'],
            'core' => ['height_cm', 'weight_kg'],
            'variables' => [
                ['height_cm', 'decimal', 'Standing height in centimetres, measured by stadiometer.'],
                ['weight_kg', 'decimal', 'Body weight in kilograms, measured in light clothing.'],
                ['bmi', 'decimal', 'Body mass index (kg/m2) from measured height and weight.'],
                ['waist_circumference_cm', 'decimal', 'Waist circumference in centimetres at the midpoint between rib and iliac crest.'],
                ['hip_circumference_cm', 'decimal', 'Hip circumference in centimetres at the widest point.'],
                ['body_fat_percent', 'decimal', 'Body fat percentage by bioelectrical impedance.'],
                ['grip_strength_kg', 'decimal', 'Maximum hand grip strength in kilograms, dominant hand.'],
                ['head_circumference_cm', 'decimal', 'Head circumference in centimetres (infants and children).'],
            ],
        ],
        'cardio' => [
            'table' => 'Cardiometabolic health', 'type' => 'Health and disease', 'subType' => 'Cardiovascular',
            'keywords' => ['cardiovascular disease', 'blood pressure', 'diabetes'],
            'core' => ['systolic_bp_mmhg'],
            'variables' => [
                ['systolic_bp_mmhg', 'integer', 'Seated systolic blood pressure in mmHg, mean of second and third readings.'],
                ['diastolic_bp_mmhg', 'integer', 'Seated diastolic blood pressure in mmHg, mean of second and third readings.'],
                ['resting_heart_rate', 'integer', 'Resting heart rate in beats per minute.'],
                ['hypertension_diagnosis', 'boolean', 'Doctor-diagnosed high blood pressure.'],
                ['type2_diabetes', 'boolean', 'Doctor-diagnosed type 2 diabetes.'],
                ['heart_attack_ever', 'boolean', 'Ever had a myocardial infarction, self-report validated against records.'],
                ['stroke_ever', 'boolean', 'Ever had a stroke or transient ischaemic attack.'],
                ['statin_use', 'boolean', 'Currently taking a statin.'],
                ['qrisk3_score', 'decimal', 'QRISK3 ten-year cardiovascular risk (%).'],
            ],
        ],
        'lifestyle' => [
            'table' => 'Health behaviours', 'type' => 'Lifestyle', 'subType' => 'Physical activity',
            'keywords' => ['physical activity', 'smoking', 'alcohol', 'diet', 'sleep'],
            'core' => ['smoking_status'],
            'variables' => [
                ['smoking_status', 'string', 'Cigarette smoking status: never, former, current occasional, current daily.'],
                ['cigarettes_per_day', 'integer', 'Cigarettes smoked per day among current smokers.'],
                ['vaping_status', 'string', 'E-cigarette use: never, tried, former, current.'],
                ['alcohol_units_week', 'decimal', 'Alcohol units in the last seven days, from a drinks diary.'],
                ['audit_c_score', 'integer', 'AUDIT-C alcohol consumption score (0-12).'],
                ['mvpa_minutes_week', 'integer', 'Moderate-to-vigorous physical activity minutes per week (IPAQ short form).'],
                ['accelerometer_steps_day', 'integer', 'Mean daily step count from a wrist-worn accelerometer over seven days.'],
                ['sedentary_hours_day', 'decimal', 'Daily sedentary time in hours, accelerometer-derived.'],
                ['sleep_duration_hours', 'decimal', 'Usual sleep duration in hours on a weeknight.'],
                ['fruit_veg_portions', 'integer', 'Portions of fruit and vegetables eaten yesterday.'],
                ['sugary_drinks_week', 'integer', 'Sugar-sweetened drinks consumed per week.'],
            ],
        ],
        'ageing' => [
            'table' => 'Ageing and function', 'type' => 'Health and disease', 'subType' => 'Ageing',
            'keywords' => ['ageing', 'frailty', 'disability'],
            'core' => ['frailty_index'],
            'variables' => [
                ['frailty_index', 'decimal', 'Frailty index from 36 deficits (0-1); 0.25 or more indicates frailty.'],
                ['adl_difficulty_count', 'integer', 'Number of activities of daily living with difficulty (0-6).'],
                ['iadl_difficulty_count', 'integer', 'Number of instrumental activities of daily living with difficulty (0-7).'],
                ['falls_past_year', 'integer', 'Number of falls in the past 12 months.'],
                ['gait_speed_m_s', 'decimal', 'Usual gait speed over a 2.4 metre course in metres per second.'],
                ['hearing_difficulty', 'boolean', 'Self-reported difficulty hearing a conversation.'],
                ['informal_care_hours', 'integer', 'Hours of unpaid care received per week.'],
                ['retirement_age', 'integer', 'Age at retirement from paid work.'],
                ['polypharmacy', 'boolean', 'Taking five or more regular prescribed medicines.'],
            ],
        ],
        'biomarkers' => [
            'table' => 'Blood and saliva biomarkers', 'type' => 'Biological samples', 'subType' => 'Biomarkers',
            'keywords' => ['biomarkers', 'blood samples', 'inflammation'],
            'core' => ['hba1c_mmol_mol'],
            'variables' => [
                ['hba1c_mmol_mol', 'decimal', 'Glycated haemoglobin (HbA1c) in mmol/mol, whole blood.'],
                ['total_cholesterol_mmol', 'decimal', 'Total serum cholesterol in mmol/L, non-fasting.'],
                ['hdl_cholesterol_mmol', 'decimal', 'HDL cholesterol in mmol/L.'],
                ['crp_mg_l', 'decimal', 'High-sensitivity C-reactive protein in mg/L.'],
                ['vitamin_d_nmol_l', 'decimal', 'Serum 25-hydroxyvitamin D in nmol/L.'],
                ['ferritin_ug_l', 'decimal', 'Serum ferritin in micrograms per litre.'],
                ['cortisol_saliva_nmol', 'decimal', 'Salivary cortisol on waking in nmol/L.'],
                ['interleukin6_pg_ml', 'decimal', 'Plasma interleukin-6 in pg/mL.'],
                ['egfr', 'decimal', 'Estimated glomerular filtration rate (CKD-EPI) in mL/min/1.73m2.'],
            ],
        ],
        'healthcare' => [
            'table' => 'Health service use', 'type' => 'Health and disease', 'subType' => 'Health services',
            'keywords' => ['health services', 'linked records', 'long-term conditions'],
            'core' => ['self_rated_health'],
            'variables' => [
                ['self_rated_health', 'string', 'Self-rated general health: very good to very bad.'],
                ['long_term_condition', 'boolean', 'Has a long-standing illness or disability limiting daily activities.'],
                ['gp_consultations_12m', 'integer', 'GP consultations in the past 12 months, from linked primary care records.'],
                ['hospital_admissions_12m', 'integer', 'Emergency hospital admissions in the past 12 months (HES/SMR01).'],
                ['multimorbidity_count', 'integer', 'Count of 20 chronic conditions recorded in primary care.'],
                ['prescription_count', 'integer', 'Distinct medicines prescribed in the past 12 months.'],
                ['unmet_care_need', 'boolean', 'Needed medical care in the past year but did not receive it.'],
                ['cancer_diagnosis', 'boolean', 'Cancer registration from linked cancer registry data.'],
            ],
        ],
        'covid' => [
            'table' => 'COVID-19 survey', 'type' => 'Health and disease', 'subType' => 'Infectious disease',
            'keywords' => ['COVID-19', 'pandemic'],
            'core' => ['covid_test_positive'],
            'variables' => [
                ['covid_test_positive', 'boolean', 'Ever had a positive SARS-CoV-2 PCR or lateral flow test.'],
                ['covid_vaccine_doses', 'integer', 'Number of COVID-19 vaccine doses received.'],
                ['long_covid_symptoms', 'boolean', 'Symptoms lasting 12 weeks or more after COVID-19 infection.'],
                ['shielding', 'boolean', 'Advised to shield as clinically extremely vulnerable.'],
                ['furloughed', 'boolean', 'Placed on furlough under the Coronavirus Job Retention Scheme.'],
                ['pandemic_loneliness', 'integer', 'Loneliness during lockdown, three-item UCLA scale (3-9).'],
                ['home_schooling_hours', 'decimal', 'Daily hours of home schooling during school closures.'],
            ],
        ],
        'genetics' => [
            'table' => 'Genetic data', 'type' => 'Biological samples', 'subType' => 'Genomics',
            'keywords' => ['genetics', 'genotyping', 'polygenic scores'],
            'core' => ['genotyped'],
            'variables' => [
                ['genotyped', 'boolean', 'Genome-wide genotype data available that passed quality control.'],
                ['genotyping_array', 'string', 'Genotyping array used (e.g. Illumina Global Screening Array).'],
                ['genetic_ancestry_pc1', 'decimal', 'First genetic principal component, for ancestry adjustment.'],
                ['pgs_bmi', 'decimal', 'Standardised polygenic score for body mass index.'],
                ['pgs_depression', 'decimal', 'Standardised polygenic score for major depression.'],
                ['zygosity', 'string', 'Twin zygosity: monozygotic or dizygotic, from genotype concordance.'],
                ['apoe_e4_carrier', 'boolean', 'Carries at least one APOE e4 allele.'],
            ],
        ],
        'environment' => [
            'table' => 'Neighbourhood and environment', 'type' => 'Socioeconomic', 'subType' => 'Environment',
            'keywords' => ['neighbourhood', 'air pollution', 'green space'],
            'core' => ['no2_annual_mean'],
            'variables' => [
                ['no2_annual_mean', 'decimal', 'Modelled annual mean NO2 at the home address in micrograms per cubic metre.'],
                ['pm25_annual_mean', 'decimal', 'Modelled annual mean PM2.5 at the home address.'],
                ['green_space_300m', 'decimal', 'Percentage of land within 300 metres of home that is green space.'],
                ['rural_urban_class', 'string', 'Rural-urban classification of the home output area.'],
                ['neighbourhood_safety', 'string', 'Feels safe walking alone in the area after dark.'],
                ['distance_to_gp_km', 'decimal', 'Road distance from home to the nearest GP practice in kilometres.'],
                ['housing_damp', 'boolean', 'Problems with damp, mould or condensation in the home.'],
            ],
        ],
    ];

    /** The sub-type a dataset gets for lifestyle variables it holds. */
    public const LIFESTYLE_SUBTYPES = ['Physical activity', 'Smoking', 'Alcohol', 'Diet', 'Sleep'];
}
