<?php

namespace App\Http\Requests\Experiment;

use App\Http\Requests\BaseFormRequest;
use App\Models\Experiment;

/**
 * @OA\Schema(
 *   schema="ExperimentInput",
 *   required={"name","status","definition","targeting","trigger"},
 *   @OA\Property(property="name", type="string", maxLength=255, example="Search feedback"),
 *   @OA\Property(property="status", type="string", enum={"draft","active","paused","archived"}, example="draft"),
 *   @OA\Property(property="definition", type="object", description="See Experiment.definition"),
 *   @OA\Property(property="targeting", type="object", description="See Experiment.targeting"),
 *   @OA\Property(property="trigger", type="object", description="See Experiment.trigger"),
 *   @OA\Property(property="starts_at", type="string", format="date", nullable=true, example="2026-11-01"),
 *   @OA\Property(property="ends_at", type="string", format="date", nullable=true, example="2026-12-01"),
 * )
 */
class CreateExperiment extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'status' => 'required|string|in:' . implode(',', Experiment::STATUSES),
            'definition' => 'required|array',
            'definition.steps' => 'required|array|min:1',
            'definition.steps.*.id' => 'required|string|distinct',
            'definition.steps.*.blocks' => 'present|array',
            'definition.steps.*.blocks.*.id' => 'required|string|max:100',
            'definition.steps.*.blocks.*.type' => 'required|string|in:' . implode(',', Experiment::BLOCK_TYPES),
            'definition.thankYou' => 'nullable|string',
            'targeting' => 'required|array',
            'targeting.paths' => 'required|array|min:1',
            'targeting.paths.*' => 'required|string|max:255',
            'targeting.audience' => 'required|string|in:' . implode(',', Experiment::AUDIENCES),
            'targeting.user_ids' => 'required_if:targeting.audience,' . Experiment::AUDIENCE_USER_IDS . '|array',
            'targeting.user_ids.*' => 'integer',
            'targeting.percentage' => 'required|integer|min:1|max:100',
            'trigger' => 'required|array',
            'trigger.on' => 'required|string|in:' . implode(',', Experiment::TRIGGERS),
            'trigger.delaySeconds' => 'required_if:trigger.on,' . Experiment::TRIGGER_DELAY . '|integer|min:0',
            'trigger.frequency' => 'required|string|in:' . implode(',', Experiment::FREQUENCIES),
            'starts_at' => 'nullable|date_format:Y-m-d',
            'ends_at' => 'nullable|date_format:Y-m-d|after_or_equal:starts_at',
        ];
    }

    public function experimentData(): array
    {
        return $this->safe()->merge(['definition' => $this->input('definition')])->except(['id']);
    }
}
