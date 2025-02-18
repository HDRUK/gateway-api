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
    public function getUsersByTeamIds(array $teamIds, int $currUserId = 0)
    {
        $users = [];

        $selectRoles = ['custodian.dar.manager'];
        $roles = Role::whereIn('name', $selectRoles)->select(['id'])->get()->toArray();
        $roleIds = convertArrayToArrayWithKeyName($roles, 'id');
        foreach ($teamIds as $teamId) {
            $team = Team::where('id', $teamId)->first();
            if (is_null($team)) {
                continue;
            }

            $teamHasUsers = TeamHasUser::where('team_id', $teamId)->select(['id', 'user_id'])->get()->toArray();
            foreach ($teamHasUsers as $teamHasUser) {
                $teamUserHasRoles = TeamUserHasRole::where('team_has_user_id', $teamHasUser['id'])->whereIn('role_id', $roleIds)->first();

                if (!is_null($teamUserHasRoles)) {
                    $user = User::where('id', $teamHasUser['user_id'])
                                ->select(['id', 'name', 'firstname', 'lastname', 'email', 'secondary_email', 'preferred_email'])
                                ->first();

                    if (is_null($user)) {
                        continue;
                    }

                    $users[] = [
                        'user' => $user->toArray(),
                        'team' => $team->toArray(),
                    ];
                }
            }
        }

        $user = User::where('id', $currUserId)
                    ->select(['id', 'name', 'firstname', 'lastname', 'email', 'secondary_email', 'preferred_email'])
                    ->first();

        if ($currUserId && !is_null($user)) {
            foreach ($teamIds as $teamId) {
                $team = Team::where('id', $teamId)->first();
                if (is_null($team)) {
                    continue;
                }

                $teamHasUsers = TeamHasUser::where([
                    'team_id' => $teamId,
                    'user_id' => $currUserId,
                ])->first();

                if (!is_null($teamHasUsers)) {
                    $users[] = [
                        'user' => $user->toArray(),
                        'team' => $team->toArray(),
                    ];

                }
            }
        }

        return $users;
    }

    public function createEnquiryThread(array $input): int
    {
        $enquiryThread = EnquiryThread::create([
            'user_id' => $input['user_id'],
            'team_ids' => $input['team_ids'],
            'project_title' => isset($input['project_title']) ? $input['project_title'] : "",
            'unique_key' => $input['unique_key'],
            'is_dar_dialogue' => $input['is_dar_dialogue'],
            'is_dar_status' => $input['is_dar_status'],
            'is_feasibility_enquiry' => $input['is_feasibility_enquiry'],
            'is_general_enquiry' => $input['is_general_enquiry'],
            'enabled' => $input['enabled'],
        ]);

        if ($enquiryThread) {
            foreach ($input['datasets'] as $dataset) {
                // handle case where enquiry is to Data Custodian without a dataset selected
                if ($dataset['dataset_id'] === null) {
                    continue;
                }
                $datasetVersion = DatasetVersion::where('dataset_id', $dataset['dataset_id'])
                    ->latest('created_at')->first();
                EnquiryThreadHasDatasetVersion::create([
                    'enquiry_thread_id' => $enquiryThread->id,
                    'dataset_version_id' =>  $datasetVersion->id,
                    'interest_type' => $dataset['interest_type'],
                ]);

                unset($datasetVersion);
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
        $imapUsername = env('ARS_IMAP_USERNAME', 'devreply@healthdatagateway.org');
        list($username, $domain) = explode('@', $imapUsername);

        try {
            $template = EmailTemplate::where('identifier', $ident)->first();

            if (array_key_exists('datasets', $threadDetail['thread'])) {
                $threadDetail['message']['message_body']['[[DATASETS]]'] = $threadDetail['thread']['datasets'];
            }

            $replacements = [
                '[[CURRENT_YEAR]]' => $threadDetail['message']['message_body']['[[CURRENT_YEAR]]'],
                '[[TEAM_NAME]]' => '',
                '[[SENDER_NAME]]' => $threadDetail['message']['message_body']['[[SENDER_NAME]]'] ?? '',
                '[[USER_FIRST_NAME]]' => $threadDetail['message']['message_body']['[[USER_FIRST_NAME]]'],
                '[[USER_LAST_NAME]]' => $threadDetail['message']['message_body']['[[USER_LAST_NAME]]'],
                '[[USER_ORGANISATION]]' => $threadDetail['message']['message_body']['[[USER_ORGANISATION]]'],
                '[[PROJECT_TITLE]]' => $threadDetail['message']['message_body']['[[PROJECT_TITLE]]'],
                '[[MESSAGE_BODY]]' => $this->convertThreadToBody($threadDetail),
            ];

            // TODO Add unique key to URL button. Future scope.
            if (count($usersToNotify) === 0) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'SEND EMAIL',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'EnquiriesManagementController failed to send email on behalf of ' .
                        $jwtUser['id'] . '. Detail: ' . json_encode($threadDetail),
                ]);

                return;
            }

            foreach ($usersToNotify as $user) {
                $replacements['[[RECIPIENT_NAME]]'] = $user['user']['name'];
                $replacements['[[TEAM_NAME]]'] = $user['team']['name'];
                $to = [
                    'to' => [
                        'email' => $user['user']['email'],
                        'name' => $user['user']['firstname'] . ' ' . $user['user']['lastname'],
                    ],
                ];

                $from = $username . '+' . $threadDetail['thread']['unique_key'] . '@' . $domain;
                $something = SendEmailJob::dispatch($to, $template, $replacements, $from);
            }

            unset(
                $template,
                $replacements,
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'SEND EMAIL',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    private function convertThreadToBody(array $in): string
    {
        $str = '';
        $datasetsStr = '<br/><br/>';
        $dataCustodiansStr = '';

        foreach ($in['thread']['datasets'] as $d) {
            $datasetsStr .= $d['title'] . ' (<a href="' . $d['url'] . '">Direct link</a>)<br/>';
        }

        foreach ($in['thread']['dataCustodians'] as $d) {
            $dataCustodiansStr .= '<p><b>' . $d . '</b></p>';
        }

        $str .= 'Name: ' . $in['message']['message_body']['[[USER_FIRST_NAME]]'] . ' ' . $in['message']['message_body']['[[USER_LAST_NAME]]'] . '<br/>';
        $str .= 'Applicant organisation: ' . $in['message']['message_body']['[[USER_ORGANISATION]]'] . '<br/>';
        $str .= 'Contact number: ' . $in['message']['message_body']['[[CONTACT_NUMBER]]'] . '<br/>';
        if ($in['thread']['is_feasibility_enquiry']) {
            $str .= 'Project title: ' . $in['thread']['project_title'] . '<br/>';
            $str .= 'Research aim: ' . $in['message']['message_body']['[[RESEARCH_AIM]]'] . '<br/>';
            $str .= 'Datasets of interest: ' . $datasetsStr . '<br/>';
            $str .= 'Are there other datasets you would like to link with the ones listed above? ' . $in['message']['message_body']['[[OTHER_DATASETS_YES_NO]]'] . '<br/>';
            $str .= 'Do you know which parts of the datasets you are interested in? ' . $in['message']['message_body']['[[DATASETS_PARTS_YES_NO]]'] . '<br/>';
            $str .= 'Funding: ' . $in['message']['message_body']['[[FUNDING]]'] . '<br/>';
            $str .= 'Potential research benefits: ' . $in['message']['message_body']['[[PUBLIC_BENEFIT]]'] . '<br/>';
            $str .= 'This enquiry has also been sent to the following Data Custodians: ' . $dataCustodiansStr . '<br/>';
        } elseif ($in['thread']['is_general_enquiry']) {
            $str .= 'Enquiry: ' . $in['message']['message_body']['[[QUERY]]'] . '<br/>';
            $str .= 'This enquiry has also been sent to the following Data Custodians: ' . $dataCustodiansStr . '<br/>';
        } elseif ($in['thread']['is_dar_dialogue']) {
            $str .= $in['message']['message_body']['[[MESSAGE]]'] . '<br/>';
        }

        return $str;
    }
}
