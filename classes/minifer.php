<?php defined('SYSPATH') or die('No direct script access.');

class Minifer extends Minify_Html
{
    public static function minify_html($html)
    {
        if ( Kohana::$config->load('minifer')->run )
            return Minify_HTML::minify($html);

        return $html;
    }
}
