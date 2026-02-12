<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\KB;

use App\Data\KnowledgeBase\SubmitFeedbackData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\KB\SubmitFeedbackRequest;
use App\Models\KnowledgeBase\KBArticle;
use App\Services\KnowledgeBase\KBFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KBFeedbackController extends Controller
{
    public function __construct(
        private readonly KBFeedbackService $feedbackService,
    ) {}

    /**
     * Submit feedback for an article.
     * POST /kb/articles/{article}/feedback
     */
    public function store(SubmitFeedbackRequest $request, KBArticle $article): JsonResponse
    {
        // Only allow feedback on published articles
        if (!$article->isPublished()) {
            return $this->notFound('Article not found');
        }

        $data = SubmitFeedbackData::from($request->validated());

        $feedback = $this->feedbackService->submitFeedback(
            $article,
            $data,
            $request->ip()
        );

        return $this->created(
            [
                'id' => $feedback->id,
                'message' => 'Thank you for your feedback!',
            ],
            'Feedback submitted successfully'
        );
    }
}
