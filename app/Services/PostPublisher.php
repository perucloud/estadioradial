<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Post;
use Carbon\CarbonInterface;

class PostPublisher
{
    public function publish(
        Post $post,
        ?int $userId = null,
        bool $automatic = false,
        ?CarbonInterface $publishedAt = null,
    ): Post {
        $scheduledFor = $post->scheduled_for;
        $publishedAt ??= now();

        $post->update([
            'status' => 'published',
            'published_at' => $publishedAt,
            'scheduled_for' => null,
            'reviewed_by' => $userId ?? $post->reviewed_by,
        ]);

        ActivityLog::query()->create([
            'user_id' => $userId ?? $post->reviewed_by,
            'action' => $automatic ? 'post.published_scheduled' : 'post.published',
            'subject_type' => $post->getMorphClass(),
            'subject_id' => $post->id,
            'properties' => [
                'automatic' => $automatic,
                'scheduled_for' => $scheduledFor?->toIso8601String(),
                'published_at' => $publishedAt->toIso8601String(),
                'timezone' => config('app.timezone'),
            ],
        ]);

        return $post;
    }
}
