<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationHasNotification;
use App\Models\ApplicationHasPermission;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasLog;
use App\Models\CohortRequestHasPermission;
use App\Models\CohortRequestLog;
use App\Models\Collection;
use App\Models\CollectionHasDur;
use App\Models\CollectionHasPublication;
use App\Models\CollectionHasTool;
use App\Models\CollectionHasUser;
use App\Models\Dataset;
use App\Models\DataAccessApplicationComment;
use App\Models\DataAccessTemplate;
use App\Models\Dur;
use App\Models\DurHasDatasetVersion;
use App\Models\DurHasPublication;
use App\Models\DurHasTool;
use App\Models\EnquiryMessage;
use App\Models\EnquiryThread;
use App\Models\Notification;
use App\Models\ProjectGrant;
use App\Models\PublicationHasTool;
use App\Models\Review;
use App\Models\SavedSearch;
use App\Models\Team;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasNotification;
use App\Models\TeamUserHasRole;
use App\Models\Tool;
use App\Models\ToolHasProgrammingLanguage;
use App\Models\ToolHasProgrammingPackage;
use App\Models\ToolHasTag;
use App\Models\ToolHasTypeCategory;
use App\Models\User;
use App\Models\UserHasNotification;
use App\Models\UserHasRole;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    /**
     * Entity types that require an explicit admin decision (reassign or
     * delete) before the owning user can be removed.
     */
    public const RESOLVABLE_ENTITY_TYPES = [
        'dataset',
        'tool',
        'application',
        'review',
        'cohort_request',
        'enquiry_thread',
        'collection',
    ];

    /**
     * Gather every entity linked to a user that needs an explicit
     * reassign-or-delete decision before that user can be safely removed.
     *
     * @param integer $userId
     * @return array
     */
    public function getLinkedEntities(int $userId): array
    {
        return [
            'datasets' => Dataset::where('user_id', $userId)
                ->with(['latestMetadata' => fn ($q) => $q->selectRaw('dataset_versions.id, dataset_versions.dataset_id, dataset_versions.title')])
                ->get(['id'])
                ->map(fn ($dataset) => [
                    'id' => $dataset->id,
                    'title' => $dataset->latestMetadata?->title,
                ])
                ->toArray(),
            'tools' => Tool::where('user_id', $userId)
                ->get(['id', 'name'])
                ->toArray(),
            'applications' => Application::where('user_id', $userId)
                ->get(['id', 'name'])
                ->toArray(),
            'reviews' => Review::where('user_id', $userId)
                ->get(['id', 'review_text'])
                ->toArray(),
            'cohort_requests' => CohortRequest::where('user_id', $userId)
                ->get(['id'])
                ->toArray(),
            'enquiry_threads' => EnquiryThread::where('user_id', $userId)
                ->get(['id', 'project_title'])
                ->toArray(),
            'collections' => Collection::whereIn(
                'id',
                CollectionHasUser::where('user_id', $userId)->pluck('collection_id')
            )->get(['id', 'name'])->toArray(),
        ];
    }

    /**
     * Remove a super-user from any number of teams in one pass, reusing the
     * same pivot cleanup as AdminTeamUserController::destroy() /
     * TeamUserController::destroy() (team_user_has_notifications,
     * team_user_has_roles, then team_has_users), skipping the "last
     * custodian.team.admin" guard since it doesn't apply to super-users.
     *
     * @param integer $userId
     * @param array $teamIds
     * @return array<int, string> keyed by team id, one of 'removed' or 'not_member'
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function removeUserFromTeams(int $userId, array $teamIds): array
    {
        $user = User::find($userId);

        if (!$user || !$user->is_admin) {
            throw ValidationException::withMessages([
                'userId' => ['This action is for removing super-users from teams only.'],
            ]);
        }

        $results = [];

        foreach ($teamIds as $teamId) {
            $teamHasUser = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            if (!$teamHasUser) {
                $results[$teamId] = 'not_member';
                continue;
            }

            TeamUserHasNotification::where(['team_has_user_id' => $teamHasUser->id])->delete();
            TeamUserHasRole::where(['team_has_user_id' => $teamHasUser->id])->delete();
            TeamHasUser::where(['team_id' => $teamId, 'user_id' => $userId])->delete();

            $results[$teamId] = 'removed';
        }

        return $results;
    }

    /**
     * List every user as a reassignment-picker option: id, name, and team
     * membership - no email address.
     *
     * @return array
     */
    public function getPickerOptions(): array
    {
        return User::with(['teams:id,name'])
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get(['id', 'firstname', 'lastname', 'name'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'name' => $user->name,
                'teams' => $user->teams->map(fn (Team $team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                ])->values(),
            ])
            ->toArray();
    }

    /**
     * Count how many entities each of the given users owns (across the same
     * entity types tracked by getLinkedEntities), in a small fixed number of
     * aggregate queries rather than one per user.
     *
     * @param array $userIds
     * @return array<int, int> keyed by user id
     */
    public function getOwnedEntityCounts(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $counts = array_fill_keys($userIds, 0);

        // enquiry_threads has no deleted_at column - every other direct
        // table here is soft-deletable and must exclude trashed rows.
        $directTables = [
            'datasets' => true,
            'tools' => true,
            'applications' => true,
            'reviews' => true,
            'cohort_requests' => true,
            'enquiry_threads' => false,
        ];

        foreach ($directTables as $table => $isSoftDeletable) {
            $query = DB::table($table)
                ->select('user_id', DB::raw('count(*) as total'))
                ->whereIn('user_id', $userIds);

            if ($isSoftDeletable) {
                $query->whereNull('deleted_at');
            }

            $query->groupBy('user_id')
                ->get()
                ->each(function ($row) use (&$counts) {
                    $counts[$row->user_id] = ($counts[$row->user_id] ?? 0) + $row->total;
                });
        }

        DB::table('collection_has_users')
            ->select('user_id', DB::raw('count(*) as total'))
            ->whereIn('user_id', $userIds)
            ->whereNull('deleted_at')
            ->groupBy('user_id')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[$row->user_id] = ($counts[$row->user_id] ?? 0) + $row->total;
            });

        return $counts;
    }

    /**
     * Map from the plural response key (used by getLinkedEntities) to the
     * `entity_type` value used in a reassignment payload entry.
     */
    private const RESPONSE_KEY_TO_ENTITY_TYPE = [
        'datasets' => 'dataset',
        'tools' => 'tool',
        'applications' => 'application',
        'reviews' => 'review',
        'cohort_requests' => 'cohort_request',
        'enquiry_threads' => 'enquiry_thread',
        'collections' => 'collection',
    ];

    /**
     * Apply the given reassignment/delete decisions for every entity linked
     * to the user, null out non-blocking ownership columns, strip the user
     * from any teams, and hard-delete the user record.
     *
     * @param integer $userId
     * @param array $reassignments
     * @return void
     */
    public function transferAndDeleteUser(int $userId, array $reassignments): void
    {
        $this->assertFullCoverage($userId, $reassignments);

        DB::transaction(function () use ($userId, $reassignments) {
            foreach ($reassignments as $reassignment) {
                $this->applyReassignment($userId, $reassignment);
            }

            // Non-blocking ownership - safe to null out automatically. Every
            // one of these references users.id with no cascade at the DB
            // level (confirmed against the actual migrations, not just the
            // live DB - production had cascade behaviour on some of these
            // that the migrations don't define), so each independently
            // blocks the final hard-delete regardless of how the user's
            // owned entities above were resolved.
            Dur::where('user_id', $userId)->update(['user_id' => null]);
            ProjectGrant::where('user_id', $userId)->update(['user_id' => null]);
            DataAccessTemplate::where('user_id', $userId)->update(['user_id' => null]);
            DurHasDatasetVersion::where('user_id', $userId)->update(['user_id' => null]);
            DurHasPublication::where('user_id', $userId)->update(['user_id' => null]);
            DataAccessApplicationComment::where('user_id', $userId)->update(['user_id' => null]);
            CollectionHasTool::where('user_id', $userId)->update(['user_id' => null]);
            CollectionHasDur::where('user_id', $userId)->update(['user_id' => null]);
            CollectionHasPublication::where('user_id', $userId)->update(['user_id' => null]);
            PublicationHasTool::where('user_id', $userId)->update(['user_id' => null]);

            // Personal records with no meaning once the user is gone -
            // cohort_request_logs.user_id and saved_searches.user_id are
            // both NOT nullable/have no cascade, so these must be removed
            // outright rather than detached (discovered in production: a
            // leftover cohort_request_logs row blocked the final user
            // delete below with a 1451 foreign key violation).
            CohortRequestLog::where('user_id', $userId)->delete();
            SavedSearch::withTrashed()->where('user_id', $userId)->get()->each(fn ($savedSearch) => $savedSearch->forceDelete());

            // Remove the user from every team.
            $teamHasUserIds = TeamHasUser::where('user_id', $userId)->pluck('id');
            TeamUserHasNotification::whereIn('team_has_user_id', $teamHasUserIds)->delete();
            TeamUserHasRole::whereIn('team_has_user_id', $teamHasUserIds)->delete();
            TeamHasUser::where('user_id', $userId)->delete();

            // Hard-delete the user itself.
            UserHasNotification::where('user_id', $userId)->delete();
            UserHasRole::where('user_id', $userId)->delete();

            $user = User::withTrashed()->where('id', $userId)->first();
            if ($user) {
                $user->forceDelete();
            }
        });
    }

    /**
     * Apply a single reassignment/delete decision.
     *
     * @param integer $userId
     * @param array $reassignment
     * @return void
     */
    private function applyReassignment(int $userId, array $reassignment): void
    {
        $entityType = $reassignment['entity_type'];
        $entityId = (int) $reassignment['entity_id'];
        $delete = (bool) ($reassignment['delete'] ?? false);
        $newUserId = $reassignment['new_user_id'] ?? null;

        if ($entityType === 'collection') {
            if ($delete) {
                // Force-delete (not soft-delete): user_id is NOT NULL on
                // collection_has_users, and this pivot row has no meaning
                // once its user is gone, so it must be fully removed - a
                // soft-deleted row would keep a dangling FK and block the
                // user's hard-delete below.
                CollectionHasUser::withTrashed()
                    ->where('collection_id', $entityId)
                    ->where('user_id', $userId)
                    ->forceDelete();
            } else {
                CollectionHasUser::where('collection_id', $entityId)
                    ->where('user_id', $userId)
                    ->update(['user_id' => $newUserId]);
            }

            return;
        }

        $model = $this->modelForEntityType($entityType);

        if ($delete) {
            $this->deleteEntityChildren($entityType, $entityId);

            // user_id is NOT NULL on these entities' tables, so a
            // soft-delete (the model's default ::destroy() behaviour where
            // applicable) would leave a dangling FK that blocks the user's
            // hard-delete below - force-delete instead.
            $query = $model::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $query = $model::withTrashed();
            }

            $query->where('id', $entityId)->get()->each(fn ($entity) => $entity->forceDelete());

            return;
        }

        $model::where('id', $entityId)->update(['user_id' => $newUserId]);
    }

    /**
     * Clean up child rows that would otherwise block a hard delete of the
     * given entity with a foreign key constraint violation (1451) -
     * discovered in production when deleting a CohortRequest with rows in
     * cohort_request_has_logs. None of these child tables cascade on
     * delete, and reusing each entity's own controller::destroy() isn't
     * enough since those only soft-delete and, in a couple of cases
     * (CohortRequest's logs, Tool's reviews, Application's DUR links,
     * EnquiryThread's messages), don't clean up every non-cascading child
     * table anyway.
     *
     * @param string $entityType
     * @param integer $entityId
     * @return void
     */
    private function deleteEntityChildren(string $entityType, int $entityId): void
    {
        switch ($entityType) {
            case 'cohort_request':
                CohortRequestHasLog::where('cohort_request_id', $entityId)->delete();
                CohortRequestHasPermission::where('cohort_request_id', $entityId)->delete();
                break;

            case 'tool':
                // None of these cascade at the DB level (verified against
                // the actual migrations, not the live DB - see note on
                // transferAndDeleteUser). Reviews of this tool (possibly by
                // other users) have no meaning once the tool is gone, so
                // they're deleted; every pivot table is pure tool-scoped
                // linkage data, so it's deleted too rather than detached.
                //
                // Deleted via a chained Builder::forceDelete() (a bulk
                // DELETE on the underlying query), NOT by fetching model
                // instances and calling ->forceDelete() on each: several of
                // these pivot tables (e.g. dur_has_tools) have no real `id`
                // primary key, so a per-instance forceDelete() silently
                // matches and deletes nothing.
                Review::withTrashed()->where('tool_id', $entityId)->forceDelete();
                DurHasTool::withTrashed()->where('tool_id', $entityId)->forceDelete();
                ToolHasTag::withTrashed()->where('tool_id', $entityId)->forceDelete();
                ToolHasProgrammingLanguage::withTrashed()->where('tool_id', $entityId)->forceDelete();
                ToolHasProgrammingPackage::withTrashed()->where('tool_id', $entityId)->forceDelete();
                ToolHasTypeCategory::withTrashed()->where('tool_id', $entityId)->forceDelete();
                CollectionHasTool::withTrashed()->where('tool_id', $entityId)->forceDelete();
                PublicationHasTool::withTrashed()->where('tool_id', $entityId)->forceDelete();
                break;

            case 'application':
                ApplicationHasPermission::where('application_id', $entityId)->delete();

                $notificationIds = ApplicationHasNotification::where('application_id', $entityId)
                    ->pluck('notification_id');
                Notification::whereIn('id', $notificationIds)->delete();
                ApplicationHasNotification::where('application_id', $entityId)->delete();

                // Dur/DUR-link/collection-link rows can reference this
                // application but are independent business records (a Dur,
                // or a collection<->tool/dur/publication association) -
                // detach rather than delete.
                Dur::where('application_id', $entityId)->update(['application_id' => null]);
                DurHasDatasetVersion::where('application_id', $entityId)->update(['application_id' => null]);
                DurHasPublication::where('application_id', $entityId)->update(['application_id' => null]);
                CollectionHasTool::where('application_id', $entityId)->update(['application_id' => null]);
                CollectionHasDur::where('application_id', $entityId)->update(['application_id' => null]);
                CollectionHasPublication::where('application_id', $entityId)->update(['application_id' => null]);
                PublicationHasTool::where('application_id', $entityId)->update(['application_id' => null]);
                break;

            case 'enquiry_thread':
                EnquiryMessage::where('thread_id', $entityId)->delete();
                break;

            default:
                break;
        }
    }

    /**
     * @param string $entityType
     * @return string
     */
    private function modelForEntityType(string $entityType): string
    {
        return match ($entityType) {
            'dataset' => Dataset::class,
            'tool' => Tool::class,
            'application' => Application::class,
            'review' => Review::class,
            'cohort_request' => CohortRequest::class,
            'enquiry_thread' => EnquiryThread::class,
        };
    }

    /**
     * Verify that every entity currently linked to the user is covered by
     * an entry in the supplied reassignments, so nothing is left orphaned.
     *
     * @param integer $userId
     * @param array $reassignments
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function assertFullCoverage(int $userId, array $reassignments): void
    {
        $linkedEntities = $this->getLinkedEntities($userId);

        $covered = [];
        foreach ($reassignments as $reassignment) {
            $covered[$reassignment['entity_type'] . ':' . $reassignment['entity_id']] = true;
        }

        $missing = [];
        foreach ($linkedEntities as $responseKey => $entities) {
            $entityType = self::RESPONSE_KEY_TO_ENTITY_TYPE[$responseKey];

            foreach ($entities as $entity) {
                $key = $entityType . ':' . $entity['id'];

                if (!isset($covered[$key])) {
                    $missing[] = $key;
                }
            }
        }

        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'reassignments' => [
                    'The following linked entities are missing a reassign/delete decision: ' . implode(', ', $missing),
                ],
            ]);
        }
    }
}
