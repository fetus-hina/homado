<?php
namespace jp3cki\homado;

use Normalizer;
use RuntimeError;
use Abraham\TwitterOAuth\TwitterOAuth;
use jp3cki\homado\config\Config;
use jp3cki\homado\config\TargetWord as TargetWordConfig;

class Application
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function run()
    {
        $twitter = $this->getTwitterClient();
        foreach ($this->makeTweets() as $message) {
            $twitter->post('statuses/update', ['status' => $message]);
        }
    }

    private function makeTweets()
    {
        $targetConfig = $this->config->getTargetConfig();

        $tweetList = $this->getTweetList($targetConfig->getTargetId());
        if (empty($tweetList)) {
            return [];
        }

        $ret = [];
        foreach ($targetConfig->getTargetWords() as $targetWord) {
            $text = $this->makeTweet($tweetList, $targetWord);
            if ($text !== false && $text != '') {
                $ret[] = $text;
            }
        }
        return $ret;
    }

    private function makeTweet(array $tweets, TargetWordConfig $word)
    {
        $matchedTweets = array_filter(
            $tweets,
            function ($tweet) use ($word) {
                return $word->isMatch($tweet['text']);
            }
        );
        if (!empty($matchedTweets)) {
            $firstMatchedAt = min(
                array_map(
                    function ($tweet) {
                        return $tweet['at'];
                    },
                    $matchedTweets
                )
            );
            $lastMatchedAt = max(
                array_map(
                    function ($tweet) {
                        return $tweet['at'];
                    },
                    $matchedTweets
                )
            );
        } else {
            $firstMatchedAt = null;
            $lastMatchedAt = null;
        }

        $matchedCount = count($matchedTweets);
        $replace = [
            'totalCount' => count($tweets),
            'matchCount' => $matchedCount,
            'avgMin' => $matchedCount >= 2
                ? number_format(
                    ($lastMatchedAt - $firstMatchedAt) / $matchedCount / 60,
                    1,
                    '.',
                    ''
                )
                : null,
            'ratio' => empty($tweets)
                ? null
                : number_format($matchedCount * 100 / count($tweets), 1, '.', ''),
            'lastMatchAgo' => time() - $lastMatchedAt >= 3600
                ? (floor((time() - $lastMatchedAt) / 3600) . '時間')
                : (floor((time() - $lastMatchedAt) / 60) . '分間'),
        ];
        return preg_replace_callback(
            '/\{([[:alnum:]]+)\}/',
            function ($match) use ($replace) {
                return isset($replace[$match[1]])
                    ? $replace[$match[1]]
                    : $match[0];
            },
            $word->getFormat($matchedCount)
        );
    }

    // returns array<['text' => ..., 'at' => int]>
    private function getTweetList($twitterUserId)
    {
        $list = $this->getTwitterClient()->get(
            'statuses/user_timeline',
            [
                'user_id' => $twitterUserId,
                'count' => 200,
                'exclude_replies' => 'true',
                'include_rts' => 'false',
                'trim_user' => 'true',
            ]
        );
        if (!is_array($list) || empty($list)) {
            return [];
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
        $tw = $this->config->getTwitterConfig();
        return new TwitterOAuth(
            $tw->getConsumerKey(),
            $tw->getConsumerSecret(),
            $tw->getUserToken(),
            $tw->getUserSecret()
        );
    }
}
