<?php
namespace jp3cki\homado\config;

class Twitter
{
    private $consumer;
    private $user;

    public function __construct(array $config)
    {
        $this->load($config);
    }

    public function getConsumerKey()
    {
        return $this->consumer->key;
    }

    public function getConsumerSecret()
    {
        return $this->consumer->secret;
    }

    public function getUserToken()
    {
        return $this->user->token;
    }

    public function getUserSecret()
    {
        return $this->user->secret;
    }

    private function load(array $config)
    {
        foreach (['consumer', 'user'] as $key) {
            if (!isset($config[$key])) {
                throw new Exception("Broken config file: twitter::{$key} does not exist.");
            }

            if (!is_array($config[$key])) {
                throw new Exception("Broken config file: twitter::{$key} exists, but it's not a hash.");
            }
        }

        foreach (['key', 'secret'] as $key) {
            if (!isset($config['consumer'][$key])) {
                throw new Exception("Broken config file: twitter::consumer::{$key} does not exist.");
            }
            if (!is_string($config['consumer'][$key])) {
                throw new Exception("Broken config file: twitter::consumer::{$key} exists, but it's not a string.");
            }
        }

        foreach (['token', 'secret'] as $key) {
            if (!isset($config['user'][$key])) {
                throw new Exception("Broken config file: twitter::user::{$key} does not exist.");
            }
            if (!is_string($config['user'][$key])) {
                throw new Exception("Broken config file: twitter::user::{$key} exists, but it's not a string.");
            }
        }

        $this->consumer = (object)$config['consumer'];
        $this->user = (object)$config['user'];
    }
}
