<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation string constants
    |--------------------------------------------------------------------------
    |
    | Owing to the validation strings being mostly identical we put them here
    | and reference to avoid duplications.
    |
    */

    'required' => 'the parameter ":attribute" is required',
    'max' => 'the parameter ":attribute" must not exceed :max characters',
    'string' => 'the parameter ":attribute" must be a string',
    'numeric' => 'the parameter ":attribute" must be an integer',
    'boolean' => 'the parameter ":attribute" must be a boolean',
    'email' => 'the parameter ":attribute" must be an email address',
    'exists' => 'the linked id of ":attribute" must first exist before being assigned',

];
