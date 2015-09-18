<?php defined('SYSPATH') or die('No direct script access.');

class Minify_Html
{
    protected $placeholders = [];

    public static function minify($html)
    {
        $min = new Minify_Html($html);
        return $min->process();
    }

    protected function __construct($html)
    {
        $this->html = str_replace("\r\n", " ", trim($html));
    }

    protected function process()
    {
        $this->html = preg_replace_callback(
            '/(\\s*)(<script\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/i',
            [$this, 'remove_script'],
            $this->html
        );

        $this->html = preg_replace_callback(
            '/\\s*(<style\\b[^>]*?>)([\\s\\S]*?)<\\/style>\\s*/i',
            [$this, 'remove_style'],
            $this->html
        );

        $this->html = preg_replace_callback(
            '/<!--([\\s\\S]*?)-->/',
            [$this, 'comment'],
            $this->html
        );

        $this->html = preg_replace_callback(
            '/\\s*(<pre\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i',
            [$this, 'remove_pre'],
            $this->html
        );

        $this->html = preg_replace_callback(
            '/\\s*(<textarea\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i',
            [$this, 'remove_textarea'],
            $this->html
        );

        $this->html = preg_replace(
            '/^\\s+|\\s+$/m',
            '',
            $this->html
        );

        $this->html = preg_replace_callback(
            '/>([^<]+)</',
            [$this, 'outside_tag'],
            $this->html
        );

        $this->html = preg_replace(
            '/(<[a-z\\-]+)\\s+([^>]+>)/i',
            "$1\n$2",
            $this->html
        );

        $this->html = str_replace(
            array_keys($this->placeholders),
            array_values($this->placeholders),
            $this->html
        );

        $this->html = preg_replace(
            '/\s+/',
            ' ',
            $this->html
        );

        return $this->html;
    }

    protected function comment($m)
    {
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<!['))
            ? $m[0]
            : '';
    }

    protected function reserve_place($content)
    {
        $placeholder = '%' . count($this->placeholders) . '%';
        $this->placeholders[$placeholder] = $content;
        return $placeholder;
    }

    protected function outside_tag($m)
    {
        return '>' . preg_replace('/^\\s+|\\s+$/', ' ', $m[1]) . '<';
    }

    protected function remove_pre($m)
    {
        return $this->reserve_place($m[1]);
    }

    protected function remove_textarea($m)
    {
        return $this->reserve_place($m[1]);
    }

    protected function remove_style($m)
    {
        $openStyle = $m[1];
        $css = $m[2];
        $css = preg_replace('/(?:^\\s*<!--|-->\\s*$)/', '', $css);
        $css = $this->remove_cdata($css);

        return $this->reserve_place($this->needs_cdata($css)
            ? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>"
            : "{$openStyle}{$css}</style>"
        );
    }

    protected function remove_script($m)
    {
        $openScript = $m[2];
        $js = $m[3];
        $ws1 = ($m[1] === '') ? '' : ' ';
        $ws2 = ($m[4] === '') ? '' : ' ';
        $js = preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $js);
        $js = $this->remove_cdata($js);

        return $this->reserve_place($this->needs_cdata($js)
            ? "{$ws1}{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>{$ws2}"
            : "{$ws1}{$openScript}{$js}</script>{$ws2}"
        );
    }

    protected function remove_cdata($str)
    {
        return (false !== strpos($str, '<![CDATA['))
            ? str_replace(array('<![CDATA[', ']]>'), '', $str)
            : $str;
    }

    protected function needs_cdata($str)
    {
        return (preg_match('/(?:[<&]|\\-\\-|\\]\\]>)/', $str));
    }
}
