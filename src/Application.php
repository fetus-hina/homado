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
        $homoInfo = $this->countHomo();
        if ($homoInfo['homoCount'] < 1) {
            // つまらん
            return;
        }

        $message = sprintf(
            'ちょまどさんは直近%dツイート中%dツイート、平均%.1f分ごとにホモとつぶやいています（ホモ率%.1f%%）',
            $homoInfo['tweets'],
            $homoInfo['homoCount'],
            $homoInfo['homoInterval'] / 60,
            $homoInfo['homoCount'] / $homoInfo['tweets'] * 100
        );

        $this->getTwitterClient()->post('statuses/update', ['status' => $message]);
    }

    private function countHomo()
    {
        $client = $this->getTwitterClient();
        $now = time();
        $list = $client->get(
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
        $homoCount = 0;
        $firstTweetAt = false;
        $homoInterval = null;
        foreach ($list as $tweet) {
            $text = mb_convert_kana(
                Normalizer::normalize($tweet->text, Normalizer::FORM_C),
                'asKV',
                'UTF-8'
            );
            if (preg_match('/homo|[ホほ][モも]/ui', $text)) {
                ++$homoCount;
            }
            $t = strtotime($tweet->created_at);
            if ($firstTweetAt === false || $firstTweetAt > $t) {
                $firstTweetAt = $t;
            }
        }
        
        if ($homoCount > 0 && $firstTweetAt !== false) {
            $homoInterval = ($now - $firstTweetAt) / $homoCount;
        }

        return [
            'tweets' => count($list),
            'homoCount' => $homoCount,
            'homoInterval' => $homoInterval,
        ];
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
