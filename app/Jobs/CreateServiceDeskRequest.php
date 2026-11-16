<?php

namespace App\Jobs;

use App\Http\Traits\LoggingContext;
use App\Models\Experiment;
use App\Services\Experiments\ExperimentSubmission;
use App\Services\JiraServiceDesk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Exception;
use Throwable;

class CreateServiceDeskRequest implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use LoggingContext;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    public $timeout = 60;

    private array $loggingContext;

    public function __construct(public readonly ExperimentSubmission $submission)
    {
        $this->loggingContext = $this->getLoggingContext(\request());
        $this->loggingContext['method_name'] = class_basename($this);
    }

    public function handle(JiraServiceDesk $serviceDesk): void
    {
        $experiment = Experiment::find($this->submission->experimentId);

        if (!$experiment) {
            \Log::warning('CreateServiceDeskRequest :: experiment no longer exists, request not raised.', array_merge(
                $this->loggingContext,
                ['experiment_id' => $this->submission->experimentId]
            ));

            return;
        }

        $created = $serviceDesk->createFeedbackRequest(
            Str::limit('Feedback: ' . $experiment->name, 250),
            $this->description($experiment),
            $this->submission->userType
        );

        if ($created->clientError()) {
            $this->fail(new Exception('JSM rejected the request: HTTP ' . $created->status()));

            return;
        }

        if ($created->failed()) {
            throw new Exception('JSM request failed: HTTP ' . $created->status());
        }
    }

    public function failed(Throwable $exception): void
    {
        \Log::error('CreateServiceDeskRequest :: request not raised.', array_merge($this->loggingContext, [
            'experiment_id' => $this->submission->experimentId,
            'error' => $exception->getMessage(),
        ]));
    }

    public function tags(): array
    {
        return ['experiments', 'experiment:' . $this->submission->experimentId];
    }

    private function description(Experiment $experiment): string
    {
        $blocks = collect($experiment->blocks())->keyBy('id');

        $lines = collect($this->submission->answers)
            ->map(fn ($answer, $blockId) => ($blocks[$blockId]['label'] ?? $blockId) . ': ' .
                $this->formatAnswer($blocks[$blockId] ?? [], $answer))
            ->values()
            ->all();

        return implode("\n", array_merge(
            [
                'Page: ' . $this->submission->path,
                'Contact: ' . ($this->submission->contactEmail ?: 'not requested'),
                'Signed in: ' . $this->signedIn(),
                '',
            ],
            $lines
        ));
    }

    private function signedIn(): string
    {
        if (!$this->submission->signedIn) {
            return 'no';
        }

        return $this->submission->userId ? 'yes (user ' . $this->submission->userId . ')' : 'yes';
    }

    private function formatAnswer(array $block, mixed $answer): string
    {
        $text = fn ($value) => is_scalar($value) ? (string)$value : json_encode($value);
        $optionLabel = fn ($id) => is_scalar($id)
            ? (collect($block['options'] ?? [])->firstWhere('id', $id)['label'] ?? (string)$id)
            : json_encode($id);

        switch ($block['type'] ?? null) {
            case 'rating':
                return $text($answer) . ' of ' . ($block['max'] ?? 5);
            case 'choice':
                return collect((array)$answer)->map($optionLabel)->implode(', ');
            case 'abChoice':
                if (!is_array($answer)) {
                    return $text($answer);
                }

                $chosen = is_scalar($answer['optionId'] ?? null)
                    ? collect($block['options'] ?? [])->firstWhere('id', $answer['optionId'])
                    : null;

                return $optionLabel($answer['optionId'] ?? '') .
                    (filled($answer['reason'] ?? null) ? ' (' . $text($answer['reason']) . ')' : '') .
                    (filled($chosen['imageSrc'] ?? null) ? ' | Image: ' . $chosen['imageSrc'] : '') .
                    (filled($chosen['url'] ?? null) ? ' | Prototype: ' . $chosen['url'] : '');
            default:
                return $text($answer);
        }
    }
}
