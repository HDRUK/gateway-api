<?php

namespace App\Modules\Closer\Harvest;

use DOMDocument;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Reads the fields we keep from CLOSER's DDI 3.3 item fragments. Only named
 * fields are copied, so personal data such as VersionResponsibility (a staff
 * email) never leaves this class.
 *
 * Counts and statistics are returned raw. They must be rounded and small
 * counts suppressed before anything shows them.
 */
class DdiItemParser
{
    /**
     * The wave's title and the datasets it lists.
     *
     * @return array{title: ?string, physical_instances: list<array{AgencyId: string, Identifier: string, Version: int}>}
     */
    public function studyUnit(string $xml): array
    {
        $x = $this->load($xml);

        return [
            'title' => $this->text($x, "/*/*/*[local-name()='Citation']/*[local-name()='Title']/*[local-name()='String']"),
            'physical_instances' => $this->references($x, "/*/*/*[local-name()='PhysicalInstanceReference']"),
        ];
    }

    /**
     * @return array{identifier: string, version: ?int, title: ?string, file_name: ?string, case_quantity: ?int, statistics: list<array{AgencyId: string, Identifier: string, Version: int}>}
     */
    public function physicalInstance(string $xml): array
    {
        $x = $this->load($xml);
        $cases = $this->text($x, "//*[local-name()='GrossFileStructure']/*[local-name()='CaseQuantity']");

        return [
            ...$this->identity($x),
            'title' => $this->text($x, "/*/*/*[local-name()='Citation']/*[local-name()='Title']/*[local-name()='String']"),
            'file_name' => $this->text($x, "/*/*/*[local-name()='UserID'][@typeOfUserID='closer:sourceFileName']"),
            'case_quantity' => is_numeric($cases) ? (int) $cases : null,
            'statistics' => $this->references($x, "//*[local-name()='StatisticalSummary']/*[local-name()='VariableStatisticsReference']"),
        ];
    }

    /**
     * @return array{identifier: string, version: ?int, name: ?string, label: ?string, question: ?array{AgencyId: string, Identifier: string, Version: int}, data_type: ?string}
     */
    public function variable(string $xml): array
    {
        $x = $this->load($xml);

        return [
            ...$this->identity($x),
            'name' => $this->text($x, "/*/*/*[local-name()='VariableName']/*[local-name()='String']"),
            'label' => $this->text($x, "/*/*/*[local-name()='Label']/*[local-name()='Content']"),
            'question' => $this->references($x, "/*/*/*[local-name()='QuestionReference']")[0] ?? null,
            'data_type' => $this->text($x, "//*[local-name()='VariableRepresentation']//*[local-name()='RecommendedDataType']"),
        ];
    }

    /**
     * A QuestionItem or QuestionGrid, reduced to what the portal needs: its
     * short label (e.g. "A3 h") and text. Grid layout, response domains and
     * routing are left in CLOSER; link there to dig deeper.
     *
     * @return array{identifier: string, version: ?int, type: ?string, label: ?string, text: ?string}
     */
    public function question(string $xml): array
    {
        $x = $this->load($xml);

        return [
            ...$this->identity($x),
            'type' => match ($x->document->documentElement?->firstElementChild?->localName) {
                'QuestionItem' => 'item',
                'QuestionGrid' => 'grid',
                default => null,
            },
            'label' => $this->text($x, "/*/*/*[local-name()='Label']/*[local-name()='Content']"),
            'text' => $this->text($x, "/*/*/*[local-name()='QuestionText']/*[local-name()='LiteralText']/*[local-name()='Text']"),
        ];
    }

    /**
     * @return array{identifier: string, version: ?int, variable: ?array{AgencyId: string, Identifier: string, Version: int}, summary: array<string, float>, frequencies: list<array{value: string, count: int|float}>}
     */
    public function variableStatistics(string $xml): array
    {
        $x = $this->load($xml);

        $summary = [];
        foreach ($x->query("/*/*/*[local-name()='SummaryStatistic']") as $statistic) {
            $type = $this->text($x, "*[local-name()='TypeOfSummaryStatistic']", $statistic);
            $value = $this->text($x, "*[local-name()='StatisticDouble']", $statistic);
            if ($type !== null && is_numeric($value)) {
                $summary[$type] = (float) $value;
            }
        }

        $frequencies = [];
        foreach ($x->query("//*[local-name()='UnfilteredCategoryStatistics']/*[local-name()='VariableCategory']") as $category) {
            $value = $this->text($x, "*[local-name()='CategoryValue']/*[local-name()='Value']", $category);
            $count = $this->text($x, "*[local-name()='CategoryStatistic'][*[local-name()='TypeOfCategoryStatistic']='Frequency']/*[local-name()='StatisticDouble']", $category);
            if ($value !== null && is_numeric($count)) {
                $frequencies[] = ['value' => $value, 'count' => $this->number($count)];
            }
        }

        return [
            ...$this->identity($x),
            'variable' => $this->references($x, "/*/*/*[local-name()='VariableReference']")[0] ?? null,
            'summary' => $summary,
            'frequencies' => $frequencies,
        ];
    }

    private function load(string $xml): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $xml !== '' && $document->loadXML($xml, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded) {
            throw new RuntimeException('CLOSER returned an item that is not valid XML.');
        }

        return new DOMXPath($document);
    }

    /** @return array{identifier: string, version: ?int} */
    private function identity(DOMXPath $x): array
    {
        $version = $this->text($x, "/*/*/*[local-name()='Version']");

        return [
            'identifier' => (string) $this->text($x, "/*/*/*[local-name()='ID']"),
            'version' => is_numeric($version) ? (int) $version : null,
        ];
    }

    /**
     * Item references as the identifier triples _getList takes.
     *
     * @return list<array{AgencyId: string, Identifier: string, Version: int}>
     */
    private function references(DOMXPath $x, string $query): array
    {
        $references = [];
        foreach ($x->query($query) as $reference) {
            $agency = $this->text($x, "*[local-name()='Agency']", $reference);
            $id = $this->text($x, "*[local-name()='ID']", $reference);
            $version = $this->text($x, "*[local-name()='Version']", $reference);

            if ($agency !== null && $id !== null && is_numeric($version)) {
                $references[] = ['AgencyId' => $agency, 'Identifier' => $id, 'Version' => (int) $version];
            }
        }

        return $references;
    }

    private function text(DOMXPath $x, string $query, ?DOMNode $context = null): ?string
    {
        $node = $x->query($query, $context)->item(0);
        $text = $node !== null ? trim($node->textContent) : '';

        return $text === '' ? null : $text;
    }

    /** Whole numbers as int (counts); fractional values stay float (weighted). */
    private function number(string $value): int|float
    {
        $float = (float) $value;

        return floor($float) === $float ? (int) $float : $float;
    }
}
