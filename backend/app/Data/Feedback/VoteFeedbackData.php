<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Enums\Feedback\VoteType;
use Spatie\LaravelData\Data;

final class VoteFeedbackData extends Data
{
    public function __construct(
        public VoteType $vote_type = VoteType::UPVOTE,
    ) {}
}
