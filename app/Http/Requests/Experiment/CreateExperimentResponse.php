<?php

namespace App\Http\Requests\Experiment;

use App\Http\Requests\BaseFormRequest;

/**
 * @OA\Schema(
 *   schema="ExperimentResponseInput",
 *   required={"answers","path"},
 *   @OA\Property(property="answers", type="object", description="Answers keyed by question block id; unknown ids are dropped",
 *     example={"score": 4, "areas": {"filters"}}),
 *   @OA\Property(property="path", type="string", maxLength=255, example="/en/search"),
 *   @OA\Property(property="variant", type="string", nullable=true),
 *   @OA\Property(property="contact", type="boolean", example=false),
 *   @OA\Property(property="email", type="string", format="email", nullable=true, description="Required when contact is true and not signed in"),
 * )
 */
class CreateExperimentResponse extends BaseFormRequest
{
    private const MAX_ANSWERS_BYTES = 20000;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|int|exists:experiments,id',
            'answers' => [
                'present',
                'array',
                'max:50',
                function ($attribute, $value, $fail) {
                    if (strlen(json_encode($value)) > self::MAX_ANSWERS_BYTES) {
                        $fail('The answers are too long.');
                    }
                },
            ],
            'path' => 'required|string|max:255',
            'variant' => 'nullable|string|max:255',
            'contact' => 'sometimes|boolean',
            'email' => $this->jwtUser() ? 'nullable' : 'exclude_unless:contact,true|required|email|max:255',
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
