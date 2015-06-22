<?php
namespace jp3cki\homado\config;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private $twitter;
    private $target;

    public function __construct($path)
    {
        $this->load($path);
    }

    public function getTwitterConfig()
    {
        return $this->twitter;
    }

    public function getTargetConfig()
    {
        return $this->target;
    }

    private function load($path)
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("Could not read {$path}");
        }

        if (!is_file($path)) {
            throw new Exception("{$path} is not a file");
        }

        $yaml = Yaml::parse(file_get_contents($path));
        foreach (['twitter', 'target'] as $key) {
            if (!isset($yaml[$key])) {
                throw new Exception("Broken config file. {$key} does not exist.");
            }
            if (!is_array($yaml[$key])) {
                throw new Exception("Broken config file. {$key} exists, but it's not a hash.");
            }
        }
        $this->twitter = new Twitter($yaml['twitter']);
        $this->target = new Target($yaml['target']);
    }
}
