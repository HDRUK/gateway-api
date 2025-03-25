<?php

namespace Database\Seeders;

use App\Models\TypeCategory;
use Illuminate\Database\Seeder;

class TypeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'AI/ML pipeline' => 'Resources tailored for health researchers to develop and deploy artificial intelligence algorithms and machine learning models effectively. These might include powerful machine learning frameworks, automated machine learning tools for accelerated model development, or robust deployment and monitoring solutions ensuring the reliability and performance of AI/ML models in real-world healthcare applications.',
            'Analysis' => 'Resources for health data researchers to conduct in-depth analysis and modeling. These might encompass data analysis tools for exploring and understanding datasets, or natural language processing and text analytics tools for processing textual data.',
            'Conversion to a common data model' => 'Resources facilitating the conversion of disparate health data sources into a common data model. These might include tools for standardizing data formats, terminologies, and coding systems to enable interoperability and facilitate analysis, or data profiling tools for assessing data quality, structure, and content.',
            'Data collection/ Management' => 'Resources that streamline the collection and management of health-related data from various sources. These might include user-friendly questionnaire tools for electronic surveys, mobile applications for real-time data capture leveraging device sensors, or seamless integration with wearable devices and sensors, ensuring comprehensive data coverage.',
            'Data discovery' => 'Resources for health data researchers to efficiently identify patient cohorts based on specific criteria, such as demographics, diagnoses, medications, and procedures. Resources might also offer comprehensive metadata exploration capabilities, including data dictionaries, variable descriptions, and provenance tracking.',
            'Preparing data for analysis' => 'Resources for health researchers to prepare and analyze data with confidence. These might encompass ETL tools for seamless data integration, data cleaning and preprocessing tools for ensuring data quality, data standardization and curation tools for interoperability, data pipelines for workflow automation, or data profiling and quality assessment resources for identifying patterns, outliers, and biases.',
            'Reference documents/ Catalogue' => 'Resources housing essential frameworks, datasets, dashboards, and guidance documents for health researchers. These might include access to standardized frameworks for data analysis, curated datasets for research purposes, interactive dashboards for data exploration, or guidance documents for best practices in health data research.',
            'Reporting/ Visualisations/ Dashboards' => 'Resources for generating insightful reports and visualizations from health research data. These might include data visualization tools for creating interactive and informative visual representations of data, or disclosure checking tools to ensure compliance with privacy regulations and data sharing policies.',
            'TRE/SDE operations' => 'Resources for managing operations within trusted research environments and secure data environments. These might encompass functionalities such as data request management, data pipeline orchestration for efficient data processing, data deidentification techniques to protect privacy, or disclosure control mechanisms to prevent unauthorized data access and disclosure.',
            'TRE/SDE setup' => 'Resources for setting up and configuring trusted research environments and secure data environments for health research. These resources might include robust security measures to protect sensitive data, data privacy features to ensure compliance with regulations, configuration tools for establishing trusted research environments, user validation mechanisms, or airlocks for secure data access.',
            'Workspace management' => 'Resources designed to optimize the research workflow for health data researchers. These might encompass developer stack management tools for version control and collaboration, applications for project management and document sharing, and robust software for data visualization and analysis, or facilitating seamless collaboration and efficient research output.',
        ];

        foreach ($categories as $name => $description) {
            TypeCategory::create([
                'name' => $name,
                'description' => $description,
                'enabled' => true,
            ]);
        }
    }
}
