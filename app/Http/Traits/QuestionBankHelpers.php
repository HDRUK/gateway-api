<?php

namespace App\Http\Traits;

use App\Models\QuestionBank;

trait QuestionBankHelpers
{
    private function getVersion($question)
    {
        // If $question is an element of an array, then `latestVersion` will have been
        // converted to `latest_version`, and similarly for `childVersions`.
        $questionVersion = $question['latestVersion'] ?? $question['latest_version'];

        $keys = [
            'section_id',
            'user_id',
            'locked',
            'archived',
            'archived_date',
            'force_required',
            'allow_guidance_override',
            'is_child',
            'question_type',
        ];

        foreach ($keys as $key) {
            $questionVersion[$key] = $question[$key];
        }

        $questionVersion['team_ids'] = array_column(
            is_array($question['teams'])
                ? $question['teams']
                : $question['teams']->toArray(),
            'id'
        );

        $teams = [];
        foreach ($question['teams'] as $team) {
            $teams[] = [
                'id' => $team['id'],
                'name' => $team['name'],
            ];
        }

        $questionVersion['teams'] = $teams;
        $questionVersion['all_custodians'] = $question['question_type'] === QuestionBank::STANDARD_TYPE;
        // decode json for the FE to easily digest
        foreach ($questionVersion['question_json'] as $key => $value) {
            $questionVersion[$key] = $value;
        }
        unset($questionVersion['question_json']);
        \Log::info("", array($questionVersion));

        $options = [];

        $childVersions = $questionVersion['childVersions'] ?? $questionVersion['child_versions'];
        foreach ($childVersions as $child) {
            // get its option value
            $option = $child['pivot']['condition'];
            // append its value to the correct option (handling creation of the key if not already extant)
            if (!array_key_exists($option, $options)) {
                $options[$option] = [];
            }
            array_push($options[$option], $child);
        }
        unset($questionVersion['childVersions']);
        unset($questionVersion['child_versions']);

        // now rejig so it's a non-associative array with labels in the values
        $newOptions = [];
        foreach ($options as $optionKey => $option) {
            $childVersionArray = [];
            foreach ($option as $childQuestionVersion) {
                // move all items from `field` field to one level up
                $toAdd = $childQuestionVersion['question_json'];
                $toAdd['component'] = $toAdd['field']['component'];
                if (isset($toAdd['field'])) {

                    if (isset($toAdd['field']['options'])) {
                        $toAdd['options'] = array_map(
                            fn ($elem) => ['label' => $elem],
                            $toAdd['field']['options']
                        );
                    }
                    if (isset($toAdd['field']['validations'])) {
                        $toAdd['validations'] = $toAdd['field']['validations'] ?? null;
                    }
                    unset($toAdd['field']);
                }

                $qbFields = QuestionBank::where('id', $childQuestionVersion['question_id'])
                    ->select('id', 'force_required', 'allow_guidance_override')
                    ->first();
                $toAdd['force_required'] = $qbFields->force_required;
                $toAdd['allow_guidance_override'] = $qbFields->allow_guidance_override;

                array_push(
                    $childVersionArray,
                    [
                        'label' => $optionKey,
                        'version_id' => $childQuestionVersion['id'],
                        'question_id' => $childQuestionVersion['question_id'],
                        ...$toAdd,
                    ]
                );
            }

            array_push(
                $newOptions,
                [
                    'label' => $optionKey,
                    'children' => $childVersionArray
                ]
            );

        }

        // add in any options that don't have any children
        if (isset($questionVersion['field']['options'])) {
            foreach ($questionVersion['field']['options'] as $option) {
                if (!in_array($option, array_column($newOptions, 'label'))) {
                    array_push(
                        $newOptions,
                        [
                            'label' => $option,
                            'children' => []
                        ]
                    );
                }
            }
        }

        $questionVersion['options'] = $newOptions;

        // Move 2 entries up to the root
        $questionVersion['component'] = $questionVersion['field']['component'];
        $questionVersion['validations'] = $questionVersion['field']['validations'] ?? null;
        $questionVersion['document'] = $questionVersion['field']['document'] ?? null;
        unset($questionVersion['field']);

        // And, because we're really returning a modified form of the QuestionVersion in response
        // to a query about a Question, we need to make it clear that the id is the version id
        $questionVersion['version_id'] = $questionVersion['id'];
        unset($questionVersion['id']);

        return $questionVersion;
    }
}
