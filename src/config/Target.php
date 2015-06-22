<?php
namespace jp3cki\homado\config;

class Target
{
    private $targetId;
    private $targetWords;

    public function __construct(array $config)
    {
        $this->load($config);
    }

    public function getTargetId()
    {
        return $this->targetId;
    }

    public function getTargetWords()
    {
        return $this->targetWords;
    }

    private function load(array $config)
    {
        foreach (['id', 'words'] as $key) {
            if (!isset($config[$key])) {
                throw new Exception("Broken config file: target::{$key} does not exist.");
            }
        }

        if (!is_string($config['id'])) {
            throw new Exception("Broken config file: target::id exists, but it's not a string.");
        }

        if (!preg_match('/^\d+$/', $config['id'])) {
            throw new Exception("Broken config file: target::id exists, but it's not a numeric-string.");
        }

        if (!is_array($config['words'])) {
            throw new Exception("Broken config file: target::words exists, but it's not an array.");
        }

        $this->targetId = $config['id'];
        $this->targetWords = array_map(
            function ($row) {
                return new TargetWord($row);
            },
            $config['words']
        );
    }
}
