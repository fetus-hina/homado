<?php
namespace jp3cki\homado;

use Normalizer;
use RuntimeError;
use Abraham\TwitterOAuth\TwitterOAuth;

class Application
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function run()
    {
        $now = time();
        $tweetList = $this->getTweetList();
        if (empty($tweetList)) {
            return;
        }
        $oldestTweetAt = min(array_map(
            function ($tweet) {
                return $tweet['at'];
            },
            $tweetList
        ));

        foreach ($this->config->getTargetWords() as $targetWord) {
            $matchCount = 0;
            foreach ($tweetList as $tweet) {
                if ($targetWord->isMatch($tweet['text'])) {
                    ++$matchCount;
                }
            }
            if ($matchCount > 0) {
                $replace = [
                    'totalCount' => count($tweetList),
                    'matchCount' => $matchCount,
                    'avgMin'     => number_format(($now - $oldestTweetAt) / $matchCount / 60, 1, '.', ''),
                    'ratio'      => number_format($matchCount * 100 / count($tweetList), 1, '.', ''),
                ];
                $message = preg_replace_callback(
                    '/\{([[:alnum:]]+)\}/',
                    function ($match) use ($replace) {
                        return isset($replace[$match[1]])
                            ? $replace[$match[1]]
                            : $match[0];
                    },
                    $targetWord->getFormat()
                );

                $this->getTwitterClient()->post('statuses/update', ['status' => $message]);
            }
        }
    }

    // returns array<['text' => ..., 'at' => int]>
    private function getTweetList()
    {
        $list = $this->getTwitterClient()->get(
            'statuses/user_timeline',
            [
                'user_id' => $this->config->getTargetId(),
                'count' => 200,
                'exclude_replies' => 'true',
                'include_rts' => 'false',
                'trim_user' => 'true',
            ]
        );
        if (!is_array($list) || empty($list)) {
            throw new RuntimeError();
        }
        return array_map(
            function (\stdClass $tweet) {
                $text = html_entity_decode($tweet->text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = Normalizer::normalize($text, Normalizer::FORM_C);
                $text = mb_convert_kana($text, 'asKV', 'UTF-8');
                $text = preg_replace('/[[:space:]]+/s', ' ', $text);
                return [
                    'text' => $text,
                    'at' => strtotime($tweet->created_at)
                ];
            },
            $list
        );
    }

    private function getTwitterClient()
    {
        return new TwitterOAuth(
            $this->config->getTwitterConsumerKey(),
            $this->config->getTwitterConsumerSecret(),
            $this->config->getTwitterUserToken(),
            $this->config->getTwitterUserSecret()
        );
    }
}
