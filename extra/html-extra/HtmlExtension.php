<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html;

use Symfony\Component\Mime\MimeTypes;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;
use Twig\Extra\Html\HtmlAttr\InlineStyle;
use Twig\Extra\Html\HtmlAttr\MergeableInterface;
use Twig\Extra\Html\HtmlAttr\SeparatedTokenList;
use Twig\Markup;
use Twig\Runtime\EscaperRuntime;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class HtmlExtension extends AbstractExtension
{
    private $mimeTypes;

    public function __construct(?MimeTypes $mimeTypes = null)
    {
        $this->mimeTypes = $mimeTypes;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('data_uri', [$this, 'dataUri']),
            new TwigFilter('html_attr_merge', [self::class, 'htmlAttrMerge']),
            new TwigFilter('html_attr_type', [self::class, 'htmlAttrType']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('html_classes', [self::class, 'htmlClasses']),
            new TwigFunction('html_cva', [self::class, 'htmlCva']),
            new TwigFunction('html_attr', [self::class, 'htmlAttr'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * Creates a data URI (RFC 2397).
     *
     * Length validation is not performed on purpose, validation should
     * be done before calling this filter.
     *
     * @return string The generated data URI
     *
     * @internal
     */
    public function dataUri(string $data, ?string $mime = null, array $parameters = []): string
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

        if (str_starts_with($mime, 'text/')) {
            $repr .= ','.rawurlencode($data);
        } else {
            $repr .= ';base64,'.base64_encode($data);
        }

        return $repr;
    }

    /**
     * @internal
     */
    public static function htmlClasses(...$args): string
    {
        $classes = [];
        foreach ($args as $i => $arg) {
            if (\is_string($arg) || $arg instanceof Markup) {
                $classes[] = (string) $arg;
            } elseif (\is_array($arg)) {
                foreach ($arg as $class => $condition) {
                    if (!\is_string($class)) {
                        throw new RuntimeError(\sprintf('The "html_classes" function argument %d (key %d) should be a string, got "%s".', $i, $class, get_debug_type($class)));
                    }
                    if (!$condition) {
                        continue;
                    }
                    $classes[] = $class;
                }
            } else {
                throw new RuntimeError(\sprintf('The "html_classes" function argument %d should be either a string or an array, got "%s".', $i, get_debug_type($arg)));
            }
        }

        return implode(' ', array_unique(array_filter($classes, static function ($v) { return '' !== $v; })));
    }

    /**
     * @param string|list<string|null>                           $base
     * @param array<string, array<string, string|array<string>>> $variants
     * @param array<array<string, string|array<string>>>         $compoundVariants
     * @param array<string, string>                              $defaultVariant
     *
     * @internal
     */
    public static function htmlCva(array|string $base = [], array $variants = [], array $compoundVariants = [], array $defaultVariant = []): Cva
    {
        return new Cva($base, $variants, $compoundVariants, $defaultVariant);
    }

    /** @internal */
    public static function htmlAttrType(mixed $value, string $type = 'sst'): AttributeValueInterface
    {
        return match ($type) {
            'sst' => new SeparatedTokenList($value, ' '),
            'cst' => new SeparatedTokenList($value, ', '),
            'style' => new InlineStyle($value),
            default => throw new RuntimeError(\sprintf('Unknown attribute type "%s" The only supported types are "sst", "cst" and "style".', $type)),
        };
    }

    /** @internal */
    public static function htmlAttrMerge(iterable|string|false|null ...$arrays): array
    {
        $result = [];

        foreach ($arrays as $array) {
            if (!$array) {
                continue;
            }

            if (\is_string($array)) {
                throw new RuntimeError('Only empty strings may be passed as string arguments to html_attr_merge. This is to support the implicit else clause for ternary operators.');
            }

            foreach ($array as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = $value;

                    continue;
                }

                $existing = $result[$key];

                switch (true) {
                    case $value instanceof MergeableInterface:
                        $result[$key] = $value->mergeInto($existing);
                        break;
                    case $existing instanceof MergeableInterface:
                        $result[$key] = $existing->appendFrom($value);
                        break;
                    case is_iterable($existing) && is_iterable($value):
                        $result[$key] = [...$existing, ...$value];
                        break;
                    case (\is_scalar($existing) || \is_object($existing)) && (\is_scalar($value) || \is_object($value)):
                        $result[$key] = $value;
                        break;
                    default:
                        throw new RuntimeError(\sprintf('Cannot merge incompatible values for key "%s".', $key));
                }
            }
        }

        return $result;
    }

    /** @internal */
    public static function htmlAttr(Environment $env, iterable|string|false|null ...$args): string
    {
        $attr = self::htmlAttrMerge(...$args);

        $result = '';
        $runtime = $env->getRuntime(EscaperRuntime::class);

        foreach ($attr as $name => $value) {
            if (str_starts_with($name, 'aria-')) {
                // For aria-*, convert booleans to "true" and "false" strings
                if (true === $value) {
                    $value = 'true';
                } elseif (false === $value) {
                    $value = 'false';
                }
            }

            if (str_starts_with($name, 'data-')) {
                if (!$value instanceof AttributeValueInterface && null !== $value && !\is_scalar($value)) {
                    // ... encode non-null non-scalars as JSON
                    try {
                        $value = json_encode($value, \JSON_THROW_ON_ERROR);
                    } catch (\JsonException $e) {
                        throw new RuntimeError(\sprintf('The "%s" attribute value cannot be JSON encoded.', $name), previous: $e);
                    }
                } elseif (true === $value) {
                    // ... and convert boolean true to a 'true'  string.
                    $value = 'true';
                }
            }

            // Convert iterable values to token lists
            if (!$value instanceof AttributeValueInterface && is_iterable($value)) {
                if ('style' === $name) {
                    $value = new InlineStyle($value);
                } else {
                    $value = new SeparatedTokenList($value);
                }
            }

            if ($value instanceof AttributeValueInterface) {
                $value = $value->getValue();
            }

            // In general, ...
            if (true === $value) {
                // ... use attribute="" for boolean true,
                // which is XHTML compliant and indicates the "empty value default", see
                // https://html.spec.whatwg.org/multipage/syntax.html#attributes-2 and
                // https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#boolean-attributes
                $value = '';
            }

            if (null === $value || false === $value) {
                // omit null-valued and false attributes completely (note aria-* has been processed before)
                continue;
            }

            if (\is_object($value) && !$value instanceof \Stringable) {
                throw new RuntimeError(\sprintf('The "%s" attribute value should be a scalar, an iterable, or an object implementing "%s", got "%s".', $name, \Stringable::class, get_debug_type($value)));
            }

            $result .= $runtime->escape($name, 'html_attr_relaxed').'="'.$runtime->escape((string) $value).'" ';
        }

        return trim($result);
    }
}
