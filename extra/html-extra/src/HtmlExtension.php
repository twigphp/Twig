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

    public function getFilters()
    {
        return [
            new TwigFilter('data_uri', [$this, 'dataUri']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('html_classes', 'twig_html_classes'),
            new TwigFunction('html_attr', 'twig_html_attributes', ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * Creates a data URI (RFC 2397).
     *
     * Length validation is not perfomed on purpose, validation should
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

function twig_html_attributes(Environment $env, array $attributes): string
{
    $output = '';

    foreach ($attributes as $attribute => $value) {
        $attribute = \htmlspecialchars($attribute, ENT_COMPAT | ENT_HTML5, $env->getCharset(), false);

        if ($value === true) {
            $output .= ' ' . $attribute;
        } else if (\is_string($value) || \is_numeric($value)) {
            $value = \htmlspecialchars($value, ENT_COMPAT | ENT_HTML5, $env->getCharset(), false);
            $output .= sprintf(' %s="%s"', $attribute, $value);
        } else if ($value !== false) {
            throw new RuntimeError(sprintf('The html_attr function argument value of key %d should be either a boolean, string or number, got "%s".', $attribute, \gettype($value)));
        }
    }

    return $output;
}
}
