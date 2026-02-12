<?php

declare(strict_types=1);

namespace App\Data\Content;

use App\Models\Content\Post;
use Spatie\LaravelData\Data;

final class PostDetailData extends Data
{
    /**
     * @param array<PostTargetData> $targets
     * @param array<PostMediaData> $media
     */
    public function __construct(
        public PostData $post,
        public array $targets,
        public array $media,
    ) {}

    /**
     * Create PostDetailData from a Post model.
     */
    public static function fromModel(Post $post): self
    {
        $post->loadMissing(['author', 'targets.socialAccount', 'media']);

        return new self(
            post: PostData::fromModel($post),
            targets: $post->targets
                ->map(fn ($target) => PostTargetData::fromModel($target))
                ->all(),
            media: $post->media
                ->map(fn ($media) => PostMediaData::fromModel($media))
                ->all(),
        );
    }
}
