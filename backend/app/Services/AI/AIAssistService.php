<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\BaseService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class AIAssistService extends BaseService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate a caption for a social media post.
     */
    public function generateCaption(string $topic, string $platform, ?string $tone = null): string
    {
        $toneInstruction = $tone ? " The tone should be {$tone}." : '';

        $prompt = "Generate a social media caption for {$platform} about: {$topic}.{$toneInstruction} "
            . "Return ONLY the caption text, no quotes or extra formatting. "
            . "Keep it concise and engaging, appropriate for {$platform}'s character limits.";

        return $this->chat($prompt);
    }

    /**
     * Suggest hashtags for content.
     *
     * @return array<int, string>
     */
    public function suggestHashtags(string $content, string $platform, int $count = 10): array
    {
        $prompt = "Suggest {$count} relevant hashtags for this {$platform} post: \"{$content}\". "
            . "Return ONLY a JSON array of hashtag strings (include the # symbol). No explanation.";

        $result = $this->chat($prompt);

        $hashtags = json_decode($result, true);

        if (!is_array($hashtags)) {
            // Try to extract hashtags from text
            preg_match_all('/#[\w]+/', $result, $matches);
            $hashtags = $matches[0] ?? [];
        }

        return array_slice($hashtags, 0, $count);
    }

    /**
     * Improve existing content.
     */
    public function improveContent(string $content, string $instruction): string
    {
        $prompt = "Improve this social media post based on the following instruction.\n\n"
            . "Original post: \"{$content}\"\n\n"
            . "Instruction: {$instruction}\n\n"
            . "Return ONLY the improved post text, no quotes or extra formatting.";

        return $this->chat($prompt);
    }

    /**
     * Generate post ideas.
     *
     * @return array<int, string>
     */
    public function generatePostIdeas(string $topic, string $platform, int $count = 5): array
    {
        $prompt = "Generate {$count} unique social media post ideas for {$platform} about: {$topic}. "
            . "Return ONLY a JSON array of strings, each being a post idea. No explanation.";

        $result = $this->chat($prompt);

        $ideas = json_decode($result, true);

        if (!is_array($ideas)) {
            // Split by newlines as fallback
            $ideas = array_filter(array_map('trim', explode("\n", $result)));
            $ideas = array_values($ideas);
        }

        return array_slice($ideas, 0, $count);
    }

    /**
     * Suggest replies for an engagement message.
     *
     * @return array{suggestions: array<int, string>, tone: string}
     */
    public function suggestReply(string $content, string $platform): array
    {
        $prompt = "You are replying to this {$platform} comment/message: \"{$content}\". "
            . "Suggest 3 different reply options with varying tones (professional, friendly, casual). "
            . "Return ONLY a JSON object with keys: 'suggestions' (array of 3 reply strings) and 'tone' (the recommended tone). No explanation.";

        $result = $this->chat($prompt);

        $parsed = json_decode($result, true);

        if (!is_array($parsed) || !isset($parsed['suggestions'])) {
            return [
                'suggestions' => [$result],
                'tone' => 'professional',
            ];
        }

        return [
            'suggestions' => array_slice($parsed['suggestions'], 0, 3),
            'tone' => $parsed['tone'] ?? 'professional',
        ];
    }

    /**
     * Analyze engagement metrics and provide insights.
     *
     * @param  array<string, mixed>  $metrics
     * @return array{score: int, insights: array<int, string>, recommendations: array<int, string>}
     */
    public function analyzeEngagement(string $content, array $metrics): array
    {
        $metricsJson = json_encode($metrics);

        $prompt = "Analyze this social media post and its performance metrics.\n\n"
            . "Post content: \"{$content}\"\n"
            . "Metrics: {$metricsJson}\n\n"
            . "Return ONLY a JSON object with keys: "
            . "'score' (0-100 integer rating), "
            . "'insights' (array of 3 key observations about the performance), "
            . "'recommendations' (array of 3 actionable suggestions to improve). No explanation.";

        $result = $this->chat($prompt);

        $parsed = json_decode($result, true);

        if (!is_array($parsed) || !isset($parsed['score'])) {
            return [
                'score' => 50,
                'insights' => ['Unable to fully analyze engagement patterns.'],
                'recommendations' => ['Try posting at different times for better reach.'],
            ];
        }

        return [
            'score' => (int) ($parsed['score'] ?? 50),
            'insights' => array_slice($parsed['insights'] ?? [], 0, 5),
            'recommendations' => array_slice($parsed['recommendations'] ?? [], 0, 5),
        ];
    }

    /**
     * Suggest best posting time based on topic and platform.
     *
     * @return array{recommended_times: array<int, array{day: string, hour: string, reason: string}>, confidence: string}
     */
    public function suggestBestTime(string $topic, string $platform): array
    {
        $prompt = "Based on social media best practices for {$platform}, suggest the best times to post about: \"{$topic}\". "
            . "Return ONLY a JSON object with keys: "
            . "'recommended_times' (array of 3 objects with 'day' (weekday name), 'hour' (e.g. '09:00'), and 'reason' (brief explanation)), "
            . "'confidence' (one of: 'high', 'medium', 'low'). No explanation.";

        $result = $this->chat($prompt);

        $parsed = json_decode($result, true);

        if (!is_array($parsed) || !isset($parsed['recommended_times'])) {
            return [
                'recommended_times' => [
                    ['day' => 'Tuesday', 'hour' => '09:00', 'reason' => 'General peak engagement time'],
                    ['day' => 'Wednesday', 'hour' => '11:00', 'reason' => 'Mid-week high activity'],
                    ['day' => 'Thursday', 'hour' => '14:00', 'reason' => 'Afternoon engagement spike'],
                ],
                'confidence' => 'low',
            ];
        }

        return [
            'recommended_times' => array_slice($parsed['recommended_times'] ?? [], 0, 5),
            'confidence' => $parsed['confidence'] ?? 'medium',
        ];
    }

    /**
     * Make a chat completion request to OpenAI.
     *
     * @throws \RuntimeException
     */
    private function chat(string $prompt): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a social media marketing expert. Be concise and creative.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

            if (!$response->successful()) {
                $this->log('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ], 'error');
                throw new \RuntimeException('AI service request failed: ' . $response->status());
            }

            $data = $response->json();

            return trim($data['choices'][0]['message']['content'] ?? '');
        } catch (ConnectionException $e) {
            $this->log('OpenAI connection error', ['error' => $e->getMessage()], 'error');
            throw new \RuntimeException('Unable to connect to AI service.');
        }
    }
}
