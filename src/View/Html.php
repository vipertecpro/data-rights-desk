<?php

namespace Vipertecpro\DataRightsDesk\View;

use Illuminate\Support\HtmlString;

/**
 * Puts a link into a translated sentence without trusting the sentence as HTML.
 */
final class Html
{
    /** @param  array<string, mixed>  $replace */
    public static function withLink(string $key, string $url, array $replace = [], ?string $label = null): HtmlString
    {
        $token = '%%DRD-LINK%%';
        $text = e((string) __($key, [...$replace, 'url' => $token]));
        // A bare web address is always read left to right, also inside Urdu, Kashmiri or Sindhi text.
        $link = $label === null
            ? '<a href="'.e($url).'" dir="ltr">'.e($url).'</a>'
            : '<a href="'.e($url).'">'.e($label).'</a>';

        return new HtmlString(str_replace($token, $link, $text));
    }
}
