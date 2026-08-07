<?php

namespace App\Services;

use App\Exceptions\MailSendException;
use Illuminate\Support\Facades\Http;

class EmailPreviewRenderer
{
    /**
     * Substitutes any remaining [[PLACEHOLDER]] tokens with fictional preview data,
     * then renders the MJML source to HTML via the MJML render service.
     */
    public function renderHtml(string $body): string
    {
        $response = Http::post(config('services.mjml.render_url'), [
            'mjml' => $this->applyDummyData($body),
        ]);

        if (!$response->successful()) {
            throw new MailSendException('Unable to contact MJML render service');
        }

        return $response->json()['html'];
    }

    public function applyDummyData(string $body): string
    {
        $successList = implode('', [
            '<li>PID: dataset-001 - Synced OK (1 record)</li>',
            '<li>PID: dataset-002 - Synced OK (3 records)</li>',
            '<li>PID: dataset-003 - Synced OK (2 records)</li>',
        ]);

        $errorList = implode('', [
            '<li>PID - dataset-004:<br><ul>' .
                '<li>my-failing-dataset / 1.0 — Schema validation failed: \'description\' is required</li>' .
                '<li>my-failing-dataset / 1.0 — Schema validation failed: \'keywords\' must be an array</li>' .
            '</ul></li>',
        ]);

        $adminList = '<ul><li>Jane Doe (jane.doe@example.com)</li><li>John Smith (john.smith@example.com)</li></ul>';

        $map = [
            // Generic
            '[[USER_FIRSTNAME]]'      => 'Jane',
            '[[USER_LASTNAME]]'       => 'Doe',
            '[[USER_FIRST_NAME]]'     => 'Jane',
            '[[USER_EMAIL]]'          => 'jane.doe@example.com',
            '[[RECIPIENT_NAME]]'      => 'Jane Doe',
            '[[SENDER_NAME]]'         => 'John Smith',
            '[[TEAM_NAME]]'           => 'Health Data Research UK',
            '[[TEAM_ID]]'             => '10',
            '[[RUN_DATE]]'            => now()->toDateTimeString(),
            '[[CURRENT_YEAR]]'        => now()->format('Y'),
            '[[PROJECT_TITLE]]'       => 'Example Research Project',
            '[[APPLICATION_ID]]'      => '123',
            '[[STATUS]]'              => 'Approved',
            '[[CUSTODIANS]]'          => 'Health Data Research UK',
            '[[APP_NAME]]'            => 'Example Application',
            '[[UUID]]'                => 'example-uuid-0000-0000',
            // Integration job
            '[[INTEGRATION_ID]]'      => '42',
            '[[INTEGRATION_LIST_URL]]' => config('gateway.gateway_url') . '/en/account/team/10/integrations/integration/list',
            '[[DATASET_COUNT]]'       => '3',
            '[[INTEGRATION_SUCCESS]]' => "<ul>{$successList}</ul>",
            '[[INTEGRATION_ERRORS]]'  => "<ul>{$errorList}</ul>",
            '[[JOB_ERROR]]'           => 'Connection timed out after 30 seconds (HTTP 504 from upstream)',
            '[[USER_LIST]]'           => $adminList,
        ];

        return str_replace(array_keys($map), array_values($map), $body);
    }
}
