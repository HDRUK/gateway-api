<?php

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\PrukDemoCatalogue as C;
use Ramsey\Uuid\Uuid;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Generates fictional longitudinal population study (LPS) datasets as GWDM
 * 2.3 envelopes: cohorts, each a parent study followed by its contact waves
 * and the odd sub-study, linked by `waveOf`. Every dataset carries variables
 * (structuralMetadata) for variable search.
 *
 * Deterministic and prefix-stable: the same seed always gives the same data,
 * and generate(n) is the first n of any larger run, so the seeder can upsert
 * on pid at any size. Touches no database.
 */
final class PrukDemoGenerator
{
    public const DEFAULT_SEED = 20261127;

    // Fixed so reruns produce identical metadata.
    private const MODIFIED = '2026-11-01T09:00:00.000Z';

    private const LAST_YEAR = 2026;

    private const UUID_NAMESPACE = '6f6f3f9e-5b3c-4f61-9b0c-7d4c1e2a9a10';

    private Randomizer $random;

    private int $index = 0;

    public function __construct(private readonly int $seed = self::DEFAULT_SEED)
    {
    }

    /**
     * @return array<int, array{custodian: string, envelope: array}>
     */
    public function generate(int $count): array
    {
        $this->random = new Randomizer(new Mt19937($this->seed));
        $this->index = 0;
        $items = [];

        // Every place/archetype pairing once, in a seeded order; repeated
        // (as a "second series") only for very large runs.
        $pairings = $this->random->shuffleArray($this->pairings());

        for ($series = 1; count($items) < $count; $series++) {
            foreach ($pairings as [$nation, $place, $archetype]) {
                foreach ($this->cohort($nation, $place, $archetype, $series) as $item) {
                    $items[] = $item;
                    if (count($items) === $count) {
                        return $items;
                    }
                }
            }
        }

        return $items;
    }

    /** @return array<int, array{0: string, 1: string, 2: string}> */
    private function pairings(): array
    {
        $pairings = [];
        foreach (C::PLACES as $nation => $places) {
            foreach ($places as $place) {
                foreach (array_keys(C::ARCHETYPES) as $archetype) {
                    $pairings[] = [$nation, $place, $archetype];
                }
            }
        }

        return $pairings;
    }

    /**
     * A parent study, then its waves and sub-studies in time order.
     *
     * @return iterable<array{custodian: string, envelope: array}>
     */
    private function cohort(string $nation, string $place, string $key, int $series): iterable
    {
        $type = C::ARCHETYPES[$key];
        $name = "{$place} {$type['name']}".($series > 1 ? " {$series}" : '');
        $acronym = $this->acronym($name);
        $ukWide = $type['ukWide'] && $this->chance(30);
        $spatial = $ukWide ? 'United Kingdom' : $this->spatialFor($nation);
        $custodian = $this->pick(C::CUSTODIANS[$ukWide ? 'United Kingdom' : $nation]);

        $start = $this->int(...$type['start']);
        $interval = $this->int(...$type['interval']);
        $waves = max(2, min($this->int(3, 10), intdiv(self::LAST_YEAR - $start, $interval) + 1));
        $size = $this->int(...$type['size']);
        [$ageMin, $ageMax] = $type['ages'];
        $ongoing = $start + $waves * $interval >= self::LAST_YEAR - 1 || $this->chance(35);

        // The cohort's own domains: always demographics, plus the archetype's
        // first few and one or two more.
        $domains = array_values(array_unique(array_merge(
            ['demographics'],
            array_slice($type['domains'], 0, 3),
            $this->sample(array_slice($type['domains'], 3), $this->int(1, 2)),
        )));

        $cohort = [
            'key' => $key,
            'type' => $type,
            'name' => $name,
            'title' => "{$name} ({$acronym})",
            'slug' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $acronym.'-'.$place)),
            'custodian' => $custodian,
            'spatial' => $spatial,
            'start' => $start,
            'interval' => $interval,
            'waves' => $waves,
            'size' => $size,
            'ages' => [$ageMin, $ageMax],
            'domains' => $domains,
            'ongoing' => $ongoing,
            'familyBased' => $type['familyBased'],
            'access' => $this->pick(array_keys(C::ACCESS)),
            'recruitment' => $this->recruitmentStatus($start, $ongoing, $key),
            'followUp' => $this->followUp($interval),
        ];

        $parent = $this->dataset($cohort, [
            'title' => $cohort['title'],
            'abstract' => $this->cohortAbstract($cohort),
            'year' => $start,
            'endYear' => $ongoing ? null : min(self::LAST_YEAR, $start + ($waves - 1) * $interval + 1),
            'ages' => $key === 'panel' ? [$ageMin, $ageMax] : [$ageMin, min(120, $ageMax + ($waves - 1) * $interval)],
            'size' => $size,
            'domains' => $domains,
            'wave' => null,
            'samples' => [],
        ]);
        yield $parent;

        $parentLink = [
            'pid' => $parent['envelope']['metadata']['required']['gatewayId'],
            'title' => $cohort['title'],
            'url' => "https://{$cohort['slug']}.example.org",
        ];
        $remaining = $size;

        for ($wave = 1; $wave <= $waves; $wave++) {
            $year = $start + ($wave - 1) * $interval;
            $offset = $year - $start;
            $remaining = $wave === 1 ? $size : (int) round($remaining * $this->float(0.82, 0.96));
            $waveDomains = $this->waveDomains($cohort, $offset);
            $collectsSamples = in_array('biomarkers', $waveDomains, true) || $this->chance(15);

            yield $this->dataset($cohort, [
                'title' => "{$cohort['title']} — Wave {$wave}",
                'abstract' => $this->waveAbstract($cohort, $wave, $year, $remaining, $waveDomains),
                'year' => $year,
                'endYear' => min(self::LAST_YEAR, $year + 1),
                'ages' => $this->agesAt($cohort, $offset),
                'size' => $remaining,
                'domains' => $waveDomains,
                'wave' => ['number' => $wave, 'type' => 'Contact wave', 'label' => "Wave {$wave}"],
                'samples' => $collectsSamples ? $this->sample(C::SAMPLE_TYPES, $this->int(1, 3)) : [],
                'link' => $parentLink,
            ]);

            // A sub-study after some waves.
            if ($wave < $waves && $this->chance(30)) {
                $sub = $this->pick(C::SUB_STUDIES);
                $subYear = min(self::LAST_YEAR, $year + max(1, intdiv($interval, 2)));
                $subSize = (int) round($remaining * $this->float(...$sub['share']));
                $subDomains = array_values(array_unique(array_merge(['demographics'], $sub['domains'])));

                yield $this->dataset($cohort, [
                    'title' => "{$cohort['title']} — {$sub['label']} {$subYear}",
                    'abstract' => $this->subStudyAbstract($cohort, $sub['label'], $subYear, $subSize),
                    'year' => $subYear,
                    'endYear' => min(self::LAST_YEAR, $subYear + 1),
                    'ages' => $this->agesAt($cohort, $subYear - $start),
                    'size' => $subSize,
                    'domains' => $subDomains,
                    'wave' => ['number' => $wave, 'type' => 'Sub-study', 'label' => $sub['label']],
                    'samples' => $sub['samples'],
                    'link' => $parentLink,
                ]);
            }
        }
    }

    /**
     * Participants' ages some years into the study. A household panel takes
     * in new members (newborns included) every wave, so its range holds.
     */
    private function agesAt(array $cohort, int $years): array
    {
        [$min, $max] = $cohort['ages'];

        return $cohort['key'] === 'panel'
            ? [$min, $max]
            : [min(120, $min + $years), min(120, $max + $years)];
    }

    /**
     * Domains for a wave: demographics, plus the cohort's domains that suit
     * participants' ages by then (pregnancy at birth or in adulthood, child
     * development while under 18).
     */
    private function waveDomains(array $cohort, int $offset): array
    {
        $youngest = $cohort['ages'][0] + $offset;
        $oldest = $cohort['ages'][1] + $offset;

        $suitable = array_values(array_filter($cohort['domains'], function (string $domain) use ($cohort, $offset, $youngest, $oldest) {
            return match ($domain) {
                'maternal' => ($cohort['key'] === 'birth' && $offset === 0) || ($oldest >= 18 && $youngest <= 45),
                'child' => $youngest < 18,
                'ageing', 'cognition' => $oldest >= 45 || $cohort['key'] !== 'birth',
                'covid' => $cohort['start'] + $offset >= 2020,
                default => true,
            };
        }));

        $optional = array_values(array_diff($suitable, ['demographics']));

        return array_values(array_unique(array_merge(
            ['demographics'],
            $this->sample($optional, min(count($optional), $this->int(2, 4))),
        )));
    }

    /** One dataset's GWDM 2.3 envelope. */
    private function dataset(array $cohort, array $d): array
    {
        $index = $this->nextIndex();
        $pid = Uuid::uuid5(self::UUID_NAMESPACE, "pruk-demo/{$this->seed}/{$index}")->toString();
        [$ageMin, $ageMax] = $d['ages'];
        $isWave = $d['wave'] !== null;
        $access = $cohort['access'];
        $tables = $this->structuralMetadata($d['domains'], $d['wave']);

        $types = $subTypes = $keywords = [];
        foreach ($d['domains'] as $domain) {
            $types[] = C::DOMAINS[$domain]['type'];
            $subTypes[] = $domain === 'lifestyle'
                ? $this->pick(C::LIFESTYLE_SUBTYPES)
                : C::DOMAINS[$domain]['subType'];
            $keywords = array_merge($keywords, C::DOMAINS[$domain]['keywords']);
        }
        $keywords = array_merge($this->sample($cohort['type']['keywords'], 2), $this->sample(array_unique($keywords), 4));

        $metadata = [
            'required' => [
                'gatewayId' => $pid,
                'gatewayPid' => $pid,
                'issued' => min($d['year'] + 1, self::LAST_YEAR).'-03-01T00:00:00.000Z',
                'modified' => self::MODIFIED,
                'version' => '1.0.0',
                'revisions' => [],
                'recordType' => 'dataset',
            ],
            'summary' => [
                'title' => $d['title'],
                'shortTitle' => $d['title'],
                'abstract' => $d['abstract'],
                'description' => $this->description($cohort, $tables),
                'keywords' => implode(C::DELIMITER, array_values(array_unique($keywords))),
                'datasetType' => implode(C::DELIMITER, array_values(array_unique($types))),
                'datasetSubType' => implode(C::DELIMITER, array_values(array_unique($subTypes))),
                'populationSize' => $d['size'],
                'contactPoint' => 'enquiries@example.org',
                'publisher' => [
                    'name' => $cohort['custodian'],
                    'gatewayId' => 'demo.'.strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cohort['custodian'])),
                ],
            ],
            'coverage' => [
                'spatial' => $cohort['spatial'],
                'typicalAgeRange' => "{$ageMin}-{$ageMax}",
                'followUp' => $cohort['followUp'],
            ],
            'provenance' => [
                'temporal' => [
                    'startDate' => sprintf('%d-%02d-01', $d['year'], $this->int(1, 9)),
                    'endDate' => $d['endYear'] === null ? null : sprintf('%d-%02d-28', $d['endYear'], $this->int(1, 12)),
                    'timeLag' => $this->pick(['1-6 months', '6-12 months', 'Not applicable']),
                    'accrualPeriodicity' => $isWave ? 'Static' : ($cohort['ongoing'] ? 'Irregular' : 'Static'),
                ],
            ],
            'accessibility' => [
                'access' => [
                    'accessServiceCategory' => $access,
                    'deliveryLeadTime' => $this->pick(C::ACCESS[$access]),
                    'accessRights' => "https://{$cohort['slug']}.example.org/access",
                    'accessRequestCost' => $this->pick(['Free', 'Low cost', 'Medium cost', 'Cost varies by project']),
                ],
                'formatAndStandards' => [
                    'conformsTo' => implode(C::DELIMITER, $this->sample(C::STANDARDS, $this->int(1, 2))),
                ],
            ],
            'linkage' => [
                'datasetLinkage' => [
                    'waveOf' => $isWave ? [$d['link']] : [],
                ],
            ],
            'tissuesSampleCollection' => array_map(fn ($type) => ['materialType' => $type], $d['samples']),
            'population' => array_filter([
                'studyDesign' => [
                    'isLongitudinal' => true,
                    'isFamilyBased' => $cohort['familyBased'],
                    'isClinicalBased' => false,
                ],
                'wave' => $isWave ? [
                    'waveId' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $d['wave']['label']))."-{$d['year']}",
                    'waveType' => $d['wave']['type'],
                    'label' => $d['wave']['label'],
                ] : null,
                'recruitment' => [
                    'status' => $cohort['recruitment'],
                    'startDate' => "{$cohort['start']}-01-01",
                    'samplingMethod' => $this->samplingMethod($cohort),
                ],
                'followUpSchedule' => [
                    'frequency' => $cohort['followUp'],
                ],
            ]),
            'structuralMetadata' => $tables,
        ];

        return [
            'custodian' => $cohort['custodian'],
            'envelope' => [
                'identifier' => $pid,
                'gwdmVersion' => '2.3',
                'metadata' => $metadata,
            ],
        ];
    }

    private function nextIndex(): int
    {
        return $this->index++;
    }

    /**
     * Tables of variables, one per domain. Core variables are always
     * present; the rest are a sample. Wave variables carry a wave prefix,
     * as LPS data dictionaries usually do.
     */
    private function structuralMetadata(array $domains, ?array $wave): array
    {
        $prefix = $wave && $wave['type'] === 'Contact wave' ? "w{$wave['number']}_" : '';
        $when = $wave ? " Collected at {$wave['label']}." : ' Harmonised across waves.';

        return array_map(function (string $domain) use ($prefix, $when) {
            $spec = C::DOMAINS[$domain];
            $core = array_values(array_filter($spec['variables'], fn ($v) => in_array($v[0], $spec['core'], true)));
            $rest = array_values(array_filter($spec['variables'], fn ($v) => ! in_array($v[0], $spec['core'], true)));
            $chosen = array_merge($core, $this->sample($rest, min(count($rest), $this->int(4, 8))));

            return [
                'name' => $spec['table'],
                'description' => "{$spec['table']} variables.",
                'columns' => array_map(fn (array $v) => [
                    'name' => $prefix.$v[0],
                    'dataType' => $v[1],
                    'description' => $v[2].$when.' '.$this->int(72, 99).'% complete.',
                    'sensitive' => in_array($v[0], ['ethnic_group_ons', 'religion', 'gender_identity', 'self_harm_ever'], true),
                ], $chosen),
            ];
        }, $domains);
    }

    private function cohortAbstract(array $c): string
    {
        $topics = $this->domainList($c['domains']);
        $span = $c['ongoing'] ? 'and continues today' : 'until '.min(self::LAST_YEAR, $c['start'] + ($c['waves'] - 1) * $c['interval'] + 1);
        $where = $c['spatial'] === 'United Kingdom' ? 'across the UK' : "in {$c['spatial']}";

        return "{$c['name']} is a longitudinal study of ".number_format($c['size'])." {$c['type']['population']} {$where}. "
            ."Data collection began in {$c['start']} {$span}, with {$c['waves']} waves so far covering {$topics}. "
            .'Questionnaire data are linked to clinic measurements and, with consent, routine records.';
    }

    private function waveAbstract(array $c, int $wave, int $year, int $size, array $domains): string
    {
        return "Wave {$wave} of {$c['name']}, collected in {$year} from ".number_format($size).' participants. '
            .'This wave covers '.$this->domainList($domains).'.';
    }

    private function subStudyAbstract(array $c, string $label, int $year, int $size): string
    {
        return "A {$label} of {$c['name']} run in {$year}, with ".number_format($size)
            .' participants drawn from the main cohort.';
    }

    private function description(array $c, array $tables): string
    {
        $columns = array_sum(array_map(fn ($t) => count($t['columns']), $tables));

        return "Data dictionary covers {$columns} variables across ".count($tables).' tables ('
            .implode(', ', array_column($tables, 'name'))."). Held by {$c['custodian']}.";
    }

    private function domainList(array $domains): string
    {
        $names = array_map(fn ($d) => strtolower(C::DOMAINS[$d]['table']), $domains);
        $last = array_pop($names);

        return $names ? implode(', ', $names)." and {$last}" : $last;
    }

    private function samplingMethod(array $c): string
    {
        return match ($c['key']) {
            'birth' => 'All births in participating maternity units over the recruitment period.',
            'panel' => 'Stratified, clustered probability sample of private households.',
            'youth' => 'Whole year groups in a random sample of secondary schools.',
            'twins' => 'Volunteer twin pairs recruited through national media appeals and GP registers.',
            'workforce' => 'All staff of participating employers invited at baseline.',
            default => 'Random sample from GP practice registers in the study area.',
        };
    }

    private function recruitmentStatus(int $start, bool $ongoing, string $key): string
    {
        if ($this->chance(10)) {
            return 'Unknown';
        }
        // An ongoing study planning a boost sample it has yet to recruit.
        if ($ongoing && $this->chance(15)) {
            return 'Not yet recruiting';
        }

        return $ongoing && ($key === 'panel' || $start >= 2010 || $this->chance(25)) ? 'Recruiting' : 'Closed';
    }

    private function followUp(int $interval): string
    {
        if ($this->chance(10)) {
            return $this->pick(['Irregular', 'Continuous', 'Other']);
        }

        return match (true) {
            $interval <= 1 => 'Annual',
            $interval === 2 => 'Biennial',
            $interval <= 5 => 'Every 3-5 years',
            default => 'Every 5+ years',
        };
    }

    /** Mostly the nation; sometimes England and Wales together. */
    private function spatialFor(string $nation): string
    {
        return $nation === 'England' && $this->chance(15) ? 'England and Wales' : $nation;
    }

    private function acronym(string $name): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', str_replace('\'s', '', $name), -1, PREG_SPLIT_NO_EMPTY);

        return implode('', array_map(fn ($w) => ctype_digit($w) ? $w : strtoupper($w[0]), $words));
    }

    private function int(int $min, int $max): int
    {
        return $this->random->getInt($min, $max);
    }

    private function float(float $min, float $max): float
    {
        return $min + ($max - $min) * $this->random->getInt(0, 1000) / 1000;
    }

    private function chance(int $percent): bool
    {
        return $this->random->getInt(1, 100) <= $percent;
    }

    private function pick(array $values): mixed
    {
        $values = array_values($values);

        return $values[$this->random->getInt(0, count($values) - 1)];
    }

    private function sample(array $values, int $count): array
    {
        return array_slice($this->random->shuffleArray(array_values($values)), 0, max(0, $count));
    }
}
