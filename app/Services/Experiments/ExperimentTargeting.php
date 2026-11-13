<?php

namespace App\Services\Experiments;

use App\Models\Experiment;

final class ExperimentTargeting
{
    public static function isShownTo(Experiment $experiment, array $visitor): bool
    {
        return self::inAudience($experiment, $visitor['userId']) &&
            self::inPercentage($experiment, $visitor['subject']);
    }

    private static function inAudience(Experiment $experiment, ?int $userId): bool
    {
        $targeting = $experiment->targeting;

        switch ($targeting['audience']) {
            case Experiment::AUDIENCE_ALL:
                return true;
            case Experiment::AUDIENCE_LOGGED_IN:
                return $userId !== null;
            case Experiment::AUDIENCE_LOGGED_OUT:
                return $userId === null;
            case Experiment::AUDIENCE_USER_IDS:
                return $userId !== null && in_array($userId, $targeting['user_ids'] ?? [], true);
            default:
                return false;
        }
    }

    private static function inPercentage(Experiment $experiment, ?string $subject): bool
    {
        $percentage = (int)($experiment->targeting['percentage'] ?? 100);

        if ($percentage >= 100) {
            return true;
        }

        if (!$subject) {
            return false;
        }

        return crc32($experiment->id . ':' . $subject) % 100 < $percentage;
    }
}
