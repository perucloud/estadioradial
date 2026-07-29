<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class PostHtmlSanitizer
{
    public function sanitize(string $html): string
    {
        $html = $this->sanitizeRichAttributes($html);

        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('blockquote')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('br')
            ->allowElement('hr')
            ->allowElement('a', ['href', 'title', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
            ->allowElement('figure', ['class'])
            ->allowElement('figcaption')
            ->allowElement('span')
            ->allowElement('mark')
            ->allowElement('pre')
            ->allowElement('code')
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th', ['colspan', 'rowspan', 'colwidth'])
            ->allowElement('td', ['colspan', 'rowspan', 'colwidth'])
            ->allowElement('div', ['data-youtube-video', 'data-oembed-url'])
            ->allowElement('iframe', ['src', 'width', 'height', 'allow', 'allowfullscreen', 'frameborder', 'loading', 'referrerpolicy', 'title'])
            ->allowAttribute('style', ['p', 'h2', 'h3', 'h4', 'span', 'mark', 'figure'])
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowMediaSchemes(['https'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->forceAttribute('iframe', 'loading', 'lazy')
            ->forceAttribute('iframe', 'referrerpolicy', 'strict-origin-when-cross-origin')
            ->withMaxInputLength(250_000);

        return (new HtmlSanitizer($config))->sanitize($html);
    }

    private function sanitizeRichAttributes(string $html): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach (iterator_to_array($document->getElementsByTagName('iframe')) as $iframe) {
            $host = mb_strtolower((string) parse_url($iframe->getAttribute('src'), PHP_URL_HOST));
            $allowed = in_array($host, [
                'www.youtube.com',
                'youtube.com',
                'www.youtube-nocookie.com',
                'youtube-nocookie.com',
                'player.vimeo.com',
                'www.dailymotion.com',
                'dailymotion.com',
                'open.spotify.com',
            ], true);

            if (! $allowed) {
                $container = $iframe->parentNode;
                $target = $container instanceof \DOMElement
                    && ($container->hasAttribute('data-youtube-video') || $container->hasAttribute('data-oembed-url'))
                    ? $container
                    : $iframe;
                $target->parentNode?->removeChild($target);

                continue;
            }

            $iframe->setAttribute('width', (string) min(1200, max(320, (int) $iframe->getAttribute('width'))));
            $iframe->setAttribute('height', (string) min(675, max(180, (int) $iframe->getAttribute('height'))));
            $iframe->setAttribute('title', 'Contenido multimedia');
        }

        foreach (iterator_to_array($document->getElementsByTagName('*')) as $element) {
            if ($element->tagName === 'figure' && $element->hasAttribute('class')) {
                $allowedClasses = [
                    'image',
                    'image_resized',
                    'image-style-block-align-left',
                    'image-style-block-align-right',
                    'image-style-align-left',
                    'image-style-align-center',
                    'image-style-align-right',
                    'image-style-side',
                    'table',
                    'media',
                ];
                $classes = array_values(array_intersect(
                    preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [],
                    $allowedClasses,
                ));

                if ($classes === []) {
                    $element->removeAttribute('class');
                } else {
                    $element->setAttribute('class', implode(' ', $classes));
                }
            }

            if (! $element->hasAttribute('style')) {
                continue;
            }

            $safeDeclarations = collect(explode(';', $element->getAttribute('style')))
                ->map(fn (string $declaration) => array_map('trim', explode(':', $declaration, 2)))
                ->filter(fn (array $parts) => count($parts) === 2)
                ->filter(function (array $parts) use ($element): bool {
                    [$property, $value] = $parts;
                    $property = mb_strtolower($property);

                    if ($property === 'text-align') {
                        return in_array($value, ['left', 'center', 'right', 'justify'], true);
                    }

                    if (in_array($property, ['color', 'background-color'], true)) {
                        return (bool) preg_match('/^(#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|hsla?\([\d\s.,%deg]+\))$/i', $value);
                    }

                    if (in_array($property, ['margin-left', 'margin-right'], true)) {
                        return (bool) preg_match('/^(?:0|(?:[1-9]\d?|1\d{2})(?:px|em|rem|%))$/i', $value);
                    }

                    return $element->tagName === 'figure'
                        && $property === 'width'
                        && (bool) preg_match('/^(?:[1-9]\d?|100)(?:\.\d+)?%$/', $value);
                })
                ->map(fn (array $parts) => implode(': ', $parts))
                ->implode('; ');

            if ($safeDeclarations === '') {
                $element->removeAttribute('style');
            } else {
                $element->setAttribute('style', $safeDeclarations);
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        $sanitized = '';

        foreach ($body?->childNodes ?? [] as $node) {
            $sanitized .= $document->saveHTML($node);
        }

        return $sanitized;
    }
}
