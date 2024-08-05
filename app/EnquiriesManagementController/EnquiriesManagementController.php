<?php

namespace App\EnquiriesManagementController;

use Auditor;
use Exception;

use App\Jobs\SendEmailJob;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\DatasetVersion;
use App\Models\TeamHasUser;
use App\Models\EnquiryThread;
use App\Models\EmailTemplate;
use App\Models\EnquiryMessage;
use App\Models\TeamUserHasRole;
use App\Models\EnquiryThreadHasDatasetVersion;

class EnquiriesManagementController
{
    public function determineDARManagersFromTeamId(int $teamId, int $enquiryThreadId): ?array
    {
        $team = Team::with('users')->where('id', $teamId)->first();
        $teamHasUserIds = TeamHasUser::where('team_id', $team->id)->get();
        $roleIdeal = null;
        $roleSecondary = null;

        $users = null;

        foreach ($teamHasUserIds as $thu) {
            $teamUserHasRoles = TeamUserHasRole::where('team_has_user_id', $thu->id)->get();

            foreach ($teamUserHasRoles as $tuhr) {
                $roleIdeal = Role::where([
                    'id' => $tuhr->role_id,
                    'name' => 'custodian.dar.manager',
                ])->first();

                $roleSecondary = Role::where([
                    'id' => $tuhr->role_id,
                    'name' => 'dar.manager',
                ])->first();

                if (!$roleIdeal && !$roleSecondary) {
                    continue;
                } // If neither roles are set, ignore

                // we don't care about this as we've found our dar.manager users.
                unset($team['users']);

                $enquiryThread = EnquiryThread::where([
                    'id' => $enquiryThreadId,
                ])->first();

                $users[] = [
                    'user' => User::where('id', $thu['user_id'])->first()->toArray(),
                    'role' => (($roleIdeal ? $roleIdeal->toArray() : ($roleSecondary ?
                        $roleSecondary->toArray() : []))),
                    'team' => $team->toArray(),
                ];
            }
        }

        return $users;
    }

    public function createEnquiryThread(array $input): int
    {
        $enquiryThread = EnquiryThread::create([
            'user_id' => $input['user_id'],
            'team_id' => $input['team_id'],
            'project_title' => $input['project_title'],
            'unique_key' => $input['unique_key'],
            'is_dar_dialogue' => $input['is_dar_dialogue'],
            'is_dar_review' => $input['is_dar_review'],
            'enabled' => $input['enabled'],
        ]);

        if ($enquiryThread) {
            foreach ($input['datasets'] as $dataset) {
                $datasetVersion = DatasetVersion::where("dataset_id", $dataset["id"])
                    ->latest('created_at')->first();
                $enquiryThreadHasDataset = EnquiryThreadHasDatasetVersion::create([
                    'enquiry_thread_id' => $enquiryThread->id,
                    'dataset_version_id' =>  $datasetVersion->id,
                    'interest_type' => $dataset['interest_type'],
                ]);
            }
        }

        return $enquiryThread->id;
    }

    public function createEnquiryMessage(int $threadId, array $input): int
    {
        $enquiryMessage = EnquiryMessage::create([
            'thread_id' => $threadId,
            'from' => $input['from'],
            'message_body' => json_encode($input['message_body']),
        ]);

        return $enquiryMessage->id;
    }

    public function sendEmail(string $ident, array $threadDetail, array $usersToNotify, array $jwtUser): void
    {
        $something = null;

        try {
            $template = EmailTemplate::where('identifier', $ident)->first();
            $team = Team::where('id', $threadDetail['thread']['team_id'])->first();
            $user = User::where('id', $jwtUser['id'])->first();

            $threadDetail['message']['message_body']['[[DATASETS]]'] = $threadDetail['thread']['datasets'];

            $replacements = [
                '[[CURRENT_YEAR]]' => $threadDetail['message']['message_body']['[[CURRENT_YEAR]]'],
                '[[TEAM_NAME]]' => $threadDetail['message']['message_body']['[[TEAM_NAME]]'],
                '[[USER_FIRST_NAME]]' => $threadDetail['message']['message_body']['[[USER_FIRST_NAME]]'],
                '[[MESSAGE_BODY]]' => $this->convertThreadToBody($threadDetail),
            ];

            // TODO Add unique key to URL button. Future scope.
            foreach ($usersToNotify as $u) {
                if ($u === null) {
                    Auditor::log([
                        'user_id' => (int)$jwtUser['id'],
                        'action_type' => 'SEND EMAIL',
                        'action_service' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => 'EnquiriesManagementController failed to send email on behalf of ' .
                            $jwtUser['id'] . '. Detail: ' . json_encode($threadDetail),
                    ]);
                    continue;
                }

                // In case for multiple users to notify, loop again for actual details.
                foreach ($u as $arr) {
                    $aliasEmail = str_replace('@', '+' . $threadDetail['thread']['unique_key']. '@', $arr['user']['email']);
                    $to = [
                        'to' => [
                            'email' => str_replace('@', '+' . $threadDetail['thread']['unique_key']. '@', $arr['user']['email']),
                            'name' => $arr['user']['firstname'] . ' ' . $arr['user']['lastname'],
                        ],
                    ];

                    $from = 'devreply+' . $threadDetail['thread']['unique_key'] . '@healthdatagateway.org';
                    $something = SendEmailJob::dispatch($to, $template, $replacements, $from);
                }
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'SEND EMAIL',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function convertThreadToBody(array $in): string
    {
        $str = '';
        $datasetsStr = '<br/><br/>';

        foreach ($in['thread']['datasets'] as $d) {
            $datasetsStr .= $d['title'] . ' (<a href="' . $d['url'] . '">Direct link</a>)<br/>';
        }

        $str .= 'Name: ' . $in['message']['message_body']['[[USER_FIRST_NAME]]'] . ' ' . $in['message']['message_body']['[[USER_LAST_NAME]]'] . '<br/>';
        $str .= 'Organisation: ' . $in['message']['message_body']['[[USER_ORGANISATION]]'] . '<br/>';
        $str .= 'Project title: ' . $in['thread']['project_title'] . '<br/>';
        $str .= 'Research aim: ' . $in['message']['message_body']['[[RESEARCH_AIM]]'] . '<br/>';
        $str .= 'Datasets of interest: ' . $datasetsStr . '<br/>';
        $str .= 'Are there other datasets you would like to link with the ones listed above: ' . $in['message']['message_body']['[[OTHER_DATASETS_YES_NO]]'] . '<br/>';
        $str .= 'Do you know which parts of the datasets you are interested in? ' . $in['message']['message_body']['[[DATASETS_PARTS_YES_NO]]'] . '<br/>';
        $str .= 'Funding: ' . $in['message']['message_body']['[[FUNDING]]'] . '<br/>';
        $str .= 'Potential research benefits: ' . $in['message']['message_body']['[[PUBLIC_BENEFIT]]'] . '<br/>';

        return $str;
    }
}
