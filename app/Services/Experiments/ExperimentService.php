<?php

namespace App\Services\Experiments;

use App\Exceptions\NotFoundException;
use App\Http\Traits\LoggingContext;
use App\Http\Traits\UserRolePermissions;
use App\Jobs\CreateServiceDeskRequest;
use App\Models\Experiment;
use App\Models\User;
use App\Services\JiraServiceDesk;
use App\Services\ReCaptcha;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Laravel\Pennant\Feature;

/**
 * @OA\Schema(
 *   schema="ActiveExperiment",
 *   description="An experiment as served to a visitor, without its targeting rules",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="Search feedback"),
 *   @OA\Property(property="paths", type="array", @OA\Items(type="string", example="/en/search")),
 *   @OA\Property(property="trigger", type="object", description="See Experiment.trigger"),
 *   @OA\Property(property="definition", type="object", description="See Experiment.definition"),
 *   @OA\Property(property="variant", type="string", nullable=true, example=null),
 *   @OA\Property(property="endsAt", type="string", format="date", nullable=true, example="2026-12-01"),
 * )
 */
class ExperimentService
{
    use LoggingContext;
    use UserRolePermissions;

    public const FEATURE_FLAG = 'POC_FeedbackExperiments';

    public const TICKETS_PER_HOUR = 50;

    private const TICKET_LIMIT_KEY = 'experiment-tickets:';

    private const TICKET_WINDOW_SECONDS = 3600;

    public function __construct(
        private readonly JiraServiceDesk $serviceDesk,
        private readonly ReCaptcha $recaptcha,
    ) {
    }

    public function verifyRecaptcha(string $token): void
    {
        if (!$this->recaptcha->verify($token, ReCaptcha::ACTION_EXPERIMENT_RESPONSE)) {
            throw new InvalidArgumentException('reCAPTCHA check failed');
        }
    }

    public function create(array $data, int $userId): Experiment
    {
        return Experiment::create(array_merge($data, ['created_by' => $userId]));
    }

    public function update(int $id, array $data): Experiment
    {
        $experiment = Experiment::findOrFail($id);
        $experiment->update($data);

        return $experiment->fresh();
    }

    public function activeFor(ExperimentVisitor $visitor): Collection
    {
        if (!Feature::active(self::FEATURE_FLAG)) {
            return collect();
        }

        return Experiment::running()->get()
            ->filter(fn (Experiment $experiment) => ExperimentTargeting::isShownTo($experiment, $visitor))
            ->map(fn (Experiment $experiment) => [
                'id' => $experiment->id,
                'name' => $experiment->name,
                'paths' => $experiment->targeting['paths'],
                'trigger' => $experiment->trigger,
                'definition' => $experiment->definition,
                'variant' => null,
                'endsAt' => $experiment->ends_at?->toDateString(),
            ])
            ->values();
    }

    public function findShownTo(int $id, ExperimentVisitor $visitor): Experiment
    {
        if (!Feature::active(self::FEATURE_FLAG)) {
            throw new NotFoundException();
        }

        $experiment = Experiment::running()->find($id);

        if (!$experiment || !ExperimentTargeting::isShownTo($experiment, $visitor)) {
            throw new NotFoundException();
        }

        return $experiment;
    }

    public function answersFor(Experiment $experiment, array $answers): array
    {
        $known = $experiment->knownAnswers($answers);

        if (!$known) {
            throw new InvalidArgumentException('No answers to known questions');
        }

        return $known;
    }

    public function submit(
        Experiment $experiment,
        ?int $userId,
        array $answers,
        string $path,
        bool $contact,
        ?string $email
    ): void {
        $user = $userId ? User::findOrFail($userId) : null;

        $experiment->increment('responses_count');

        if (!$this->serviceDesk->configured()) {
            \Log::warning('Experiment response not sent to the service desk: JSM is not configured', array_merge(
                $this->getLoggingContext(\request()),
                ['experiment_id' => $experiment->id]
            ));

            return;
        }

        if (RateLimiter::hit(self::TICKET_LIMIT_KEY . $experiment->id, self::TICKET_WINDOW_SECONDS) > self::TICKETS_PER_HOUR) {
            \Log::warning('Experiment response not sent to the service desk: hourly ticket limit reached', array_merge(
                $this->getLoggingContext(\request()),
                ['experiment_id' => $experiment->id]
            ));

            return;
        }

        CreateServiceDeskRequest::dispatch(new ExperimentSubmission(
            experimentId: $experiment->id,
            answers: $answers,
            path: $path,
            signedIn: $user !== null,
            userId: $contact && $user ? $user->id : null,
            userType: $user ? $this->userType($this->rolesFor($user->id)) : null,
            contactEmail: $contact ? ($user ? $user->preferredEmailAddress() : $email) : null,
        ));
    }

    private function userType(array $roles): string
    {
        return collect($roles)->contains(fn ($role) => str_starts_with($role, 'custodian.'))
            ? 'data_custodian'
            : 'user';
    }

    private function rolesFor(int $userId): array
    {
        return array_values($this->getUserRolePerms($userId)['summary']['roles']);
    }
}
