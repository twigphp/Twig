<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html {
use Symfony\Component\Mime\MimeTypes;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class HtmlExtension extends AbstractExtension
{
    private $mimeTypes;

    public function __construct(MimeTypes $mimeTypes = null)
    {
        $this->mimeTypes = $mimeTypes;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('data_uri', [$this, 'dataUri']),
            new TwigFilter('attr_merge', 'twig_attr_merge'),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('html_classes', 'twig_html_classes'),
            new TwigFunction('attr', 'twig_attr', ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * Creates a data URI (RFC 2397).
     *
     * Length validation is not performed on purpose, validation should
     * be done before calling this filter.
     *
     * @return string The generated data URI
     */
    public function dataUri(string $data, string $mime = null, array $parameters = []): string
    {
        $repr = 'data:';

        if (null === $mime) {
            if (null === $this->mimeTypes) {
                $this->mimeTypes = new MimeTypes();
            }

            $tmp = tempnam(sys_get_temp_dir(), 'mime');
            file_put_contents($tmp, $data);
            try {
                if (null === $mime = $this->mimeTypes->guessMimeType($tmp)) {
                    $mime = 'text/plain';
                }
            } finally {
                @unlink($tmp);
            }
        }
        $repr .= $mime;

        foreach ($parameters as $key => $value) {
            $repr .= ';'.$key.'='.rawurlencode($value);
        }

        if (0 === strpos($mime, 'text/')) {
            $repr .= ','.rawurlencode($data);
        } else {
            $repr .= ';base64,'.base64_encode($data);
        }

        return $repr;
    }
}
}

namespace {
use Twig\Environment;
use Twig\Error\RuntimeError;

function twig_html_classes(...$args): string
{
    $classes = [];
    foreach ($args as $i => $arg) {
        if (\is_string($arg)) {
            $classes[] = $arg;
        } elseif (\is_array($arg)) {
            foreach ($arg as $class => $condition) {
                if (!\is_string($class)) {
                    throw new RuntimeError(sprintf('The html_classes function argument %d (key %d) should be a string, got "%s".', $i, $class, \gettype($class)));
                }
                if (!$condition) {
                    continue;
                }
                $classes[] = $class;
            }
        } else {
            throw new RuntimeError(sprintf('The html_classes function argument %d should be either a string or an array, got "%s".', $i, \gettype($arg)));
        }
    }

    return implode(' ', array_unique($classes));
}

function twig_attr_merge(...$arrays): array
{
    $result = [];

    foreach ($arrays as $argNumber => $array) {
        if (!$array) {
            continue;
        }

        if (!twig_test_iterable($array)) {
            throw new RuntimeError(sprintf('The attr_merge filter only works with arrays or "Traversable", got "%s" for argument %d.', \gettype($array), $argNumber + 1));
        }

        $array = twig_to_array($array);

        foreach (['class', 'style', 'data'] as $deepMergeKey) {
            if (isset($array[$deepMergeKey])) {
                $value = $array[$deepMergeKey];
                unset($array[$deepMergeKey]);

                if (!twig_test_iterable($value)) {
                    $value = (array) $value;
                }

                $value = twig_to_array($value);

                $result[$deepMergeKey] = array_merge($result[$deepMergeKey] ?? [], $value);
            }
        }

        $result = array_merge($result, $array);
    }

    return $result;
}

function twig_attr(Environment $env, ...$args): string
{
    $attr = twig_attr_merge(...$args);

    if (isset($attr['class'])) {
        $attr['class'] = trim(implode(' ', array_values($attr['class'])));
    }

    if (isset($attr['style'])) {
        $style = '';
        foreach ($attr['style'] as $name => $value) {
            if (is_numeric($name)) {
                $style .= $value.'; ';
            } else {
                $style .= $name.': '.$value.'; ';
            }
        }
        $attr['style'] = trim($style);
    }

    if (isset($attr['data'])) {
        foreach ($attr['data'] as $name => $value) {
            $attr['data-'.$name] = $value;
        }
        unset($attr['data']);
    }

    $result = '';
    foreach ($attr as $name => $value) {
        $result .= twig_escape_filter($env, $name, 'html_attr').'="'.htmlspecialchars($value).'" ';
    }

    return trim($result);
}
}
