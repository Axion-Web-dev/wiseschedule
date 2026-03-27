<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Abraham\TwitterOAuth\TwitterOAuth;
use Exception;
use Illuminate\Support\Facades\Log;

class SocialMediaService
{
    private AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function publishPostToX(Post $post, User $user): array
    {
        try {

            $rawThread = $this->aiService->generateTweetDraft($post);
            $tweets = $this->aiService->parseThread($rawThread);

            if (empty($tweets)) {
                throw new Exception('No tweets generated from AI response');
            }

            $connection = new TwitterOAuth(
                config('services.twitter.client_id'),
                config('services.twitter.client_secret'),
                $user->twitter_token,
                $user->twitter_token_secret
            );

            $mediaId = null;
            if ($post->featured_image_url) {
                try {
                    $media = $connection->upload('media/upload', ['media' => $post->featured_image_url]);
                    $mediaId = $media->media_id_string;
                } catch (Exception $e) {
                    Log::warning('Failed to upload media to Twitter', ['error' => $e->getMessage()]);

                }
            }

            $connection->setApiVersion('2');
            $lastTweetId = null;
            $postedTweets = [];

            foreach ($tweets as $index => $text) {

                $text = $this->truncateTweet($text);

                $payload = ['text' => $text];

                if ($index === 0 && $mediaId) {
                    $payload['media'] = ['media_ids' => [$mediaId]];
                }

                if ($lastTweetId) {
                    $payload['reply'] = ['in_reply_to_tweet_id' => $lastTweetId];
                }

                $result = $connection->post('tweets', $payload, true);

                if ($connection->getLastHttpCode() === 201) {
                    $lastTweetId = $result->data->id;
                    $postedTweets[] = [
                        'id' => $lastTweetId,
                        'text' => $text,
                        'media_id' => $index === 0 ? $mediaId : null
                    ];
                } else {
                    Log::error("Failed to post tweet #$index", [
                        'status_code' => $connection->getLastHttpCode(),
                        'response' => (array)$result
                    ]);
                    throw new Exception("Failed to post tweet #" . ($index + 1));
                }
            }

            return [
                'success' => true,
                'thread_id' => $postedTweets[0]['id'] ?? null,
                'tweets_posted' => count($postedTweets),
                'tweets' => $postedTweets
            ];

        } catch (Exception $e) {
            Log::error('Twitter thread publishing failed', [
                'post_id' => $post->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function truncateTweet(string $text): string
    {
        if (mb_strlen($text) <= 280) {
            return $text;
        }

        return mb_substr($text, 0, 277) . '...';
    }

    public function testTwitterConnection(User $user): bool
    {
        try {
            $connection = new TwitterOAuth(
                config('services.twitter.client_id'),
                config('services.twitter.client_secret'),
                $user->twitter_token,
                $user->twitter_token_secret
            );

            $connection->setApiVersion('2');
            $result = $connection->get('users/me', [], true);

            return $connection->getLastHttpCode() === 200;
        } catch (Exception $e) {
            Log::error('Twitter connection test failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}