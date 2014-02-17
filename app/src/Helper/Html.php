<?php

namespace Helper;

use dflydev\markdown\MarkdownExtraParser;
use Model\Article;
use Voodoo\Paginator;

class Html
{
    public static function fromMarkdown($text)
    {
        static $markdown;

        if ($markdown === null) {
            $markdown = new INewsMarkdownParser();
        }

        $text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $text);

        return $markdown->transformMarkdown($text);
    }

    public static function makePage($path, $pattern, $page, $total, $limit = 20)
    {
        $paginator = new Paginator($path, $pattern);
        return $paginator->setTotalItems($total)
            ->setItemsPerPage($limit)
            ->setNavigationSize(9)
            ->setCurrentPage($page)
            ->render();
    }

    public static function gravatar($email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array())
    {
        $url = 'http://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";
        if ($img) {
            $url = '<img src="' . $url . '"';
            foreach ($atts as $key => $val)
                $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }

    public static function makeShareText(Article $article)
    {
        $data = array(
            '{site_title}' => config('site.title'),
            '{title}'      => $article->title
        );

        return str_replace(array("\n", "'"), array("", "\\'"), strtr(config('site.share_text'), $data));
    }

    public static function makeMetaText($meta)
    {
        return strip_tags(preg_replace('/[\s`#>]+/', ' ', $meta));
    }
}