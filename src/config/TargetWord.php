<?php
namespace jp3cki\homado\config;

class TargetWord
{
    private $regex;
    private $formats;

    public function __construct(array $config)
    {
        $this->load($config);
    }

    public function isMatch($text)
    {
        return !!preg_match($this->regex, $text);
    }

    public function getFormat($num)
    {
        $num = (int)$num;
        return (isset($this->formats[$num]))
            ? $this->formats[$num]
            : $this->formats["*"];
    }

    private function load(array $config)
    {
        foreach (['match', 'formats'] as $key) {
            if (!isset($config[$key])) {
                throw new Exception("Broken config file: target::words::{$key} does not exist.");
            }
        }

        if (!is_string($config['match'])) {
            throw new Exception("Broken config file: target::words::match exists, but it's not a string.");
        }

        if (!@preg_match($config['match'], '') === false) {
            throw new Exception("Broken config file: target::words::match exists, but regex broken.");
        }

        if (!is_array($config['formats'])) {
            throw new Exception("Broken config file: target::words:formats exists, but it's not a hash.");
        }

        if (!isset($config['formats']['*'])) {
            throw new Exception("Broken config file: target::words::formats exists, but it hasn't '*'.");
        }

        $this->regex = $config['match'];
        $this->formats = $config['formats'];
    }
}
