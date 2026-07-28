<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Publica las noticias programadas cuya fecha ya llegó';

    public function handle(): int
    {
        $count = 0;

        Post::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->chunkById(100, function ($posts) use (&$count): void {
                foreach ($posts as $post) {
                    $post->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'scheduled_for' => null,
                    ]);

                    ActivityLog::query()->create([
                        'user_id' => $post->reviewed_by,
                        'action' => 'post.published_scheduled',
                        'subject_type' => $post->getMorphClass(),
                        'subject_id' => $post->id,
                        'properties' => ['automatic' => true],
                    ]);
                    $count++;
                }
            });

        $this->info("Noticias publicadas: {$count}");

        return self::SUCCESS;
    }
}
