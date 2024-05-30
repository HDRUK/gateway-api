<?php

namespace Tests\Feature;

use Tests\TestCase;

use Config;

class FormHydrationTest extends TestCase
{
    public function test_form_hydration_schema(): void
    {
        $response = $this->get('api/v1/form_hydration/schema');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
                'data' =>[
                'identifier' => [
                    'required',
                    'title',
                    'description',
                    'examples',
                    'is_list',
                    'is_optional',
                    'types'
                ]
            ]
        ]);
    }

    public function test_form_hydration_schema_with_parameters(): void
    {
        $response = $this->get('api/v1/form_hydration/schema?model=HDRUK');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
                'data' =>[
                'identifier' => [
                    'required',
                    'title',
                    'description',
                    'examples',
                    'is_list',
                    'is_optional',
                    'types'
                ]
            ]
        ]);

        $responseOldVersion = $this->get('api/v1/form_hydration/schema?model=HDRUK&version=2.1.2');
        $responseOldVersion->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
                'data' =>[
                'identifier' => [
                    'required',
                    'title',
                    'description',
                    'examples',
                    'is_list',
                    'is_optional',
                    'types'
                ]
            ]
        ]);

        $this->assertNotEquals($response, $responseOldVersion);


    }
    
     public function test_form_hydration_schema_will_fail(): void
    {
        $response = $this->get('api/v1/form_hydration/schema?model=blah');
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));

        $response = $this->get('api/v1/form_hydration/schema?version=9.9.9');
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }
    
}
