<?php
namespace jp3cki\homado;

use BadMethodCallException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class Config
{
    private $config;

    public function __construct($path)
    {
        $this->load($path);
    }

    public function getTwitterConsumerKey()
    {
        return $this->config['twitter']['consumer']['key'];
    }

    public function getTwitterConsumerSecret()
    {
        return $this->config['twitter']['consumer']['secret'];
    }

    public function getTwitterUserToken()
    {
        return $this->config['twitter']['user']['token'];
    }

    public function getTwitterUserSecret()
    {
        return $this->config['twitter']['user']['secret'];
    }

    public function getTargetId()
    {
        return $this->config['target']['id'];
    }

    public function getTargetWords()
    {
        return $this->config['target']['words'];
    }

    private function load($path)
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException("Could not read {$path}");
        }

        if (!is_file($path)) {
            throw new RuntimeException("{$path} is not a file");
        }

        $yaml = Yaml::parse(file_get_contents($path));
        if (!isset($yaml['twitter']['consumer']['key']) ||
                !isset($yaml['twitter']['consumer']['secret']) ||
                !isset($yaml['twitter']['user']['token']) ||
                !isset($yaml['twitter']['user']['secret']) ||
                !isset($yaml['target']['id']) ||
                !isset($yaml['target']['words'])) {
            throw new RuntimeException("Broken config file");
        }

        $this->config = $yaml;

        // target / words は MonitoredWord クラスのインスタンスに変換する
        $this->config['target']['words'] = array_map(
            function (array $monitor) {
                return new MonitoredWord($monitor);
            },
            $this->config['target']['words']
        );
    }
}
