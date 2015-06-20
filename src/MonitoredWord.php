<?php
namespace jp3cki\homado;

use RuntimeException;

class MonitoredWord
{
    private $match;
    private $format;

    public function __construct(array $conf)
    {
        if (!isset($conf['match'])) {
            throw new RuntimeException('Broken config: words-match not found');
        }
        if (!isset($conf['format'])) {
            throw new RuntimeException('Broken config: words-format not found');
        }
        if (@preg_match($conf['match'], '') === false) {
            throw new RuntimeException('Broken config: words-match regex broken, ' . $conf['match']);
        }
        $this->match = $conf['match'];
        $this->format = $conf['format'];
    }

    public function isMatch($text)
    {
        return !!preg_match($this->match, $text);
    }

    public function getFormat()
    {
        return $this->format;
    }
}
