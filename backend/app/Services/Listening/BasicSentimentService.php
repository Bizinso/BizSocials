<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\Enums\Listening\SentimentType;
use App\Services\BaseService;

final class BasicSentimentService extends BaseService
{
    /**
     * Positive keywords for sentiment analysis.
     *
     * @var array<int, string>
     */
    private const POSITIVE_WORDS = [
        'great', 'love', 'amazing', 'excellent', 'wonderful',
        'fantastic', 'happy', 'good', 'best', 'awesome',
        'helpful', 'recommend',
    ];

    /**
     * Negative keywords for sentiment analysis.
     *
     * @var array<int, string>
     */
    private const NEGATIVE_WORDS = [
        'bad', 'terrible', 'awful', 'poor', 'worst',
        'hate', 'horrible', 'disappointed', 'frustrating', 'annoying',
        'useless', 'broken',
    ];

    /**
     * Analyze text and return a sentiment type.
     */
    public function analyze(string $text): SentimentType
    {
        $lowerText = strtolower($text);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach (self::POSITIVE_WORDS as $word) {
            $positiveCount += substr_count($lowerText, $word);
        }

        foreach (self::NEGATIVE_WORDS as $word) {
            $negativeCount += substr_count($lowerText, $word);
        }

        if ($positiveCount > $negativeCount) {
            return SentimentType::POSITIVE;
        }

        if ($negativeCount > $positiveCount) {
            return SentimentType::NEGATIVE;
        }

        if ($positiveCount > 0 && $negativeCount > 0) {
            return SentimentType::NEUTRAL;
        }

        if ($positiveCount === 0 && $negativeCount === 0) {
            return SentimentType::UNKNOWN;
        }

        return SentimentType::NEUTRAL;
    }

    /**
     * Get a sentiment score between -1.0 and 1.0.
     */
    public function getScore(string $text): float
    {
        $lowerText = strtolower($text);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach (self::POSITIVE_WORDS as $word) {
            $positiveCount += substr_count($lowerText, $word);
        }

        foreach (self::NEGATIVE_WORDS as $word) {
            $negativeCount += substr_count($lowerText, $word);
        }

        $total = $positiveCount + $negativeCount;

        if ($total === 0) {
            return 0.0;
        }

        return round(($positiveCount - $negativeCount) / $total, 2);
    }
}
