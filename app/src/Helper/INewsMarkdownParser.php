<?php

namespace Helper;

use Model\User;
use dflydev\markdown\MarkdownExtraParser;
use Pagon\Url;

class INewsMarkdownParser extends MarkdownExtraParser
{
    var $no_markup = true;

    public function __construct(array $configure = null)
    {
        $this->block_gamut += array(
            "parserUser" => 100,
            'parserUrl'  => 10000,
        );

        $this->span_gamut += array(
            'parserNewLine' => 0,
        );

        parent::__construct($configure);
    }

    public function parserNewLine($text)
    {
        return preg_replace_callback('/\n/',
            array(&$this, '_doHardBreaks_callback'), $text);
    }

    public function parserUser($text)
    {
        return preg_replace_callback('{
                (?<!(?:\[|`))\s?
                    @([\w]{1,20})
                \s(?!(?:\]|`))
            }xs', function ($match) {
            if ($user = User::dispense()->where('name', $match[1])->find_one()) {
                return '[' . trim($match[0]) . '](' . Url::to('/u/' . $user->id) . ')';
            } else {
                return $match[0];
            }
        }, $text);
    }

    public function parserUrl($text)
    {
        return preg_replace_callback("{
                (?<!\(|\"|'|\[)
                    ((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)
                (?!\)|\"|'|\])
            }xs",
            function ($match) {
                return '[' . $match[1] . '](' . $match[1] . ')';
            }, $text);
    }
}