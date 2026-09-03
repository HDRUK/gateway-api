<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\Validator;

class TransferAndDeleteUser extends BaseFormRequest
{
    public const ENTITY_TYPES = [
        'dataset',
        'tool',
        'application',
        'review',
        'cohort_request',
        'enquiry_thread',
        'collection',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'userId' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'reassignments' => [
                'array',
            ],
            'reassignments.*.entity_type' => [
                'required',
                'string',
                'in:' . implode(',', self::ENTITY_TYPES),
            ],
            'reassignments.*.entity_id' => [
                'required',
                'int',
            ],
            'reassignments.*.new_user_id' => [
                'nullable',
                'int',
                'exists:users,id',
            ],
            'reassignments.*.delete' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([ 'userId' => $this->route('userId') ]);
    }

    /**
     * Ensure each reassignment entry specifies exactly one of `new_user_id`
     * or `delete`, never both and never neither.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $reassignments = $this->input('reassignments', []);

            foreach ($reassignments as $index => $reassignment) {
                $hasNewUserId = array_key_exists('new_user_id', $reassignment) && !is_null($reassignment['new_user_id']);
                $hasDelete = array_key_exists('delete', $reassignment) && $reassignment['delete'];

                if ($hasNewUserId && $hasDelete) {
                    $validator->errors()->add(
                        "reassignments.{$index}",
                        'Only one of new_user_id or delete may be provided.'
                    );
                }

                if (!$hasNewUserId && !$hasDelete) {
                    $validator->errors()->add(
                        "reassignments.{$index}",
                        'Either new_user_id or delete must be provided.'
                    );
                }

                // A Dataset is referenced by dataset_versions, DAR
                // applications, collections, DURs, tools etc. - too heavily
                // linked to safely hard-delete here. Datasets must always
                // be reassigned to a new owner, never deleted, via this
                // endpoint.
                if (($reassignment['entity_type'] ?? null) === 'dataset' && $hasDelete) {
                    $validator->errors()->add(
                        "reassignments.{$index}",
                        'Datasets cannot be deleted here - they must be reassigned to another user.'
                    );
                }

                // A Cohort Discovery request is inherently tied to the
                // specific user who submitted it - reassigning it to
                // someone else doesn't make sense. It must always be
                // deleted, never reassigned, via this endpoint.
                if (($reassignment['entity_type'] ?? null) === 'cohort_request' && $hasNewUserId) {
                    $validator->errors()->add(
                        "reassignments.{$index}",
                        'Cohort Discovery requests cannot be reassigned here - they must be deleted.'
                    );
                }
            }
        });
    }
}
