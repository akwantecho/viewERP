<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\Log;

class HtmlSanitizer
{
    protected ?HTMLPurifier $purifier = null;

    public function __construct()
    {
        if (class_exists(HTMLPurifier::class)) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.SafeIframe', true);
            $config->set('Attr.EnableID', true);
            $config->set('AutoFormat.AutoParagraph', false);
            $config->set('CSS.AllowImportant', true);
            $config->set('HTML.Allowed', null); // allow default safe set
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('HTML.SafeEmbed', true);
            $config->set('HTML.SafeObject', true);
            $config->set('HTML.Trusted', true);
            $config->set('Cache.DefinitionImpl', null);
            $this->purifier = new HTMLPurifier($config);
        } else {
            Log::warning('HTMLPurifier package not installed; HtmlSanitizer will fall back to strip_tags.');
        }
    }

    public function sanitize(string $html): string
    {
        if ($this->purifier instanceof HTMLPurifier) {
            return $this->purifier->purify($html);
        }

        return strip_tags($html, '<p><br><strong><em><u><ol><ul><li><a><blockquote><code><pre><span><img><table><thead><tbody><tr><th><td><h1><h2><h3><h4><h5><h6><hr>');
    }
}

