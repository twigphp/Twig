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

    public function getFilters(): array
    {
        return [
            new TwigFilter('data_uri', [$this, 'dataUri']),
            new TwigFilter(
                'html_attributes',
                [$this, 'htmlAttributes'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('html_classes', 'twig_html_classes'),
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

    /**
     * @param array{string, string|bool|int|float|null} $attributes
     */
    public function htmlAttributes(Environment $environment, array $attributes): string
    {
        /** @var string[] $htmlAttributes */
        $htmlAttributes = [];
        foreach ($attributes as $key => $value) {
            if (\is_bool($value)) {
                // false should never be outputted e.g. disabled, readonly
                // this matches also the behaviour here: https://github.com/symfony/symfony/blob/v5.4.14/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig#L465-L476
                if ($value) {
                    $htmlAttributes[] = $key;
                }

                continue;
            }

            // null represent no value and should not be outputted for a better DX
            if (null === $value) {
                continue;
            }

            $htmlAttributes[] = $key . '="' . twig_escape_filter($environment, $value) . '"';
        }

        return implode(' ', $htmlAttributes);
    }
}
}

namespace {
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
}
