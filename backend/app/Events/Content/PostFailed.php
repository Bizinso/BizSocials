<?php

declare(strict_types=1);

namespace App\Events\Content;

use App\Models\Content\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PostFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Post $post,
        public readonly string $reason,
    ) {}
}
