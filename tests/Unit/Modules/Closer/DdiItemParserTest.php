<?php

namespace Tests\Unit\Modules\Closer;

use App\Modules\Closer\Harvest\DdiItemParser;
use RuntimeException;
use Tests\TestCase;

/**
 * Fixtures are real CLOSER items (ALSPAC) with VersionResponsibility replaced
 * by a placeholder and the dataset's statistics list trimmed.
 */
class DdiItemParserTest extends TestCase
{
    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/closer/{$name}.xml"));
    }

    public function test_a_study_unit_gives_its_title_and_datasets(): void
    {
        $this->assertSame([
            'title' => 'Example Cohort Pregnancy to 12 months',
            'physical_instances' => [
                ['AgencyId' => 'uk.example', 'Identifier' => 'pi-1', 'Version' => 4],
                ['AgencyId' => 'uk.example', 'Identifier' => 'pi-2', 'Version' => 1],
                ['AgencyId' => 'uk.example', 'Identifier' => 'pi-3', 'Version' => 1],
            ],
        ], (new DdiItemParser())->studyUnit($this->fixture('study_unit')));
    }

    public function test_a_physical_instance_becomes_a_dataset_with_its_statistics_references(): void
    {
        $this->assertSame([
            'identifier' => '13857dab-fbaf-41c4-8be6-041f0d1f37a6',
            'version' => 4,
            'title' => 'ALSPAC About Yourself Questionnaire Dataset',
            'file_name' => 'alspac_12weeks',
            'case_quantity' => 12448,
            'statistics' => [
                ['AgencyId' => 'uk.alspac', 'Identifier' => 'f776cde3-88cb-4d23-9e9c-76c53c3e33c3', 'Version' => 1],
                ['AgencyId' => 'uk.alspac', 'Identifier' => '4ece4c32-9ab3-4cb6-8e10-c02ba619ac71', 'Version' => 1],
            ],
        ], (new DdiItemParser())->physicalInstance($this->fixture('physical_instance')));
    }

    public function test_a_variable_keeps_its_name_label_question_and_type(): void
    {
        $this->assertSame([
            'identifier' => '77656078-ed5f-4a25-8425-0781ff290af9',
            'version' => 1,
            'name' => 'f027',
            'label' => 'Had piles since CH born',
            'question' => ['AgencyId' => 'uk.alspac', 'Identifier' => 'd46bd6f3-9149-4d69-990b-2033404074e3', 'Version' => 1],
            'data_type' => 'Numeric',
        ], (new DdiItemParser())->variable($this->fixture('variable')));
    }

    public function test_a_question_item_keeps_only_its_label_and_text(): void
    {
        $this->assertSame([
            'identifier' => 'd46bd6f3-9149-4d69-990b-2033404074e3',
            'version' => 1,
            'type' => 'item',
            'label' => 'A3 h',
            'text' => 'Have you had any of the following since the baby was born? haemorrhoids/piles',
        ], (new DdiItemParser())->question($this->fixture('question_item')));
    }

    public function test_a_question_grid_keeps_only_its_label_and_text_not_its_layout(): void
    {
        $this->assertSame([
            'identifier' => '67975c69-ee51-41b1-976f-079409da20e5',
            'version' => 1,
            'type' => 'grid',
            'label' => 'E2 ii(a-g)',
            'text' => 'did you want this to happen with this person?',
        ], (new DdiItemParser())->question($this->fixture('question_grid')));
    }

    public function test_variable_statistics_keep_raw_summary_values_and_frequencies(): void
    {
        $this->assertSame([
            'identifier' => 'f82bd7d4-cc49-4cf6-823c-00088dfbe535',
            'version' => 2,
            'variable' => ['AgencyId' => 'uk.alspac', 'Identifier' => '347ba94f-4b83-4803-a179-96c0b5434f38', 'Version' => 2],
            'summary' => [
                'Maximum' => 2.0,
                'Minimum' => 1.0,
                'Mean' => 1.993,
                'Mode' => 2.0,
                'ValidCases' => 12448.0,
                'InvalidCases' => 0.0,
            ],
            'frequencies' => [
                ['value' => '-9999', 'count' => 0],
                ['value' => '1', 'count' => 82],
                ['value' => '2', 'count' => 12366],
            ],
        ], (new DdiItemParser())->variableStatistics($this->fixture('variable_statistics')));
    }

    public function test_nothing_parsed_carries_personal_data(): void
    {
        $parser = new DdiItemParser();
        $parsed = json_encode([
            $parser->physicalInstance($this->fixture('physical_instance')),
            $parser->variable($this->fixture('variable')),
            $parser->variableStatistics($this->fixture('variable_statistics')),
            $parser->question($this->fixture('question_item')),
            $parser->question($this->fixture('question_grid')),
        ]);

        $this->assertStringNotContainsString('@', $parsed);
    }

    public function test_invalid_xml_is_an_error(): void
    {
        $this->expectException(RuntimeException::class);

        (new DdiItemParser())->variable('<Fragment><Variable>');
    }
}
