<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostPublisher;
use App\Support\SchedulerHealth;
use Illuminate\Console\Command;
use Throwable;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Publica las noticias programadas cuya fecha ya llegó';

    public function handle(PostPublisher $publisher, SchedulerHealth $health): int
    {
        $count = 0;
        $health->started();

        try {
            Post::query()
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_for')
                ->where('scheduled_for', '<=', now())
                ->chunkById(100, function ($posts) use (&$count, $publisher): void {
                    foreach ($posts as $post) {
                        $publisher->publish(
                            post: $post,
                            userId: $post->reviewed_by,
                            automatic: true,
                        );
                        $count++;
                    }
                });
        } catch (Throwable $exception) {
            $health->failed($exception);

            throw $exception;
        }

        $health->completed($count);
        $this->info("Noticias publicadas: {$count}");

        return self::SUCCESS;
    }
}
