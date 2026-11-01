<?php

namespace App\Infrastructure\Auditing;

use App\Domain\Enum\ActivityAction;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function log(
        ActivityAction $action,
        Model $subject,
        string $description,
        array $changes = [],
        ?int $userId = null,
    ): void {
        ActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => (int) $subject->getKey(),
            'description' => $description,
            'changes' => $changes === [] ? null : $changes,
        ]);
    }
}
