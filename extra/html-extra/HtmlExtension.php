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
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
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

        if (0 === strpos($mime, 'text/')) {
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
            if (\is_string($arg)) {
                $classes[] = $arg;
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
     * @param string|list<string|null> $base
     * @param array<string, array<string, string|array<string>> $variants
     * @param array<array<string, string|array<string>>> $compoundVariants
     * @param array<string, string>                      $defaultVariant
     *
     * @internal
     */
    public static function htmlCva(array|string $base = [], array $variants = [], array $compoundVariants = [], array $defaultVariant = []): Cva
    {
        return new Cva($base, $variants, $compoundVariants, $defaultVariant);
    }

    public static function htmlAttrMerge(...$arrays): array
    {
        $result = [];

        foreach ($arrays as $argNumber => $array) {
            if (!$array) {
                continue;
            }

            if (!is_iterable($array)) {
                throw new RuntimeError(sprintf('The "attr_merge" filter only works with arrays or "Traversable", got "%s" for argument %d.', \gettype($array), $argNumber + 1));
            }

            $array = CoreExtension::toArray($array);

            foreach (['class', 'style', 'data', 'aria'] as $deepMergeKey) {
                if (isset($array[$deepMergeKey])) {
                    $value = $array[$deepMergeKey];
                    unset($array[$deepMergeKey]);

                    if (!is_iterable($value)) {
                        $value = (array) $value;
                    }

                    $value = CoreExtension::toArray($value);

                    $result[$deepMergeKey] = array_merge($result[$deepMergeKey] ?? [], $value);
                }
            }

            $result = array_merge($result, $array);
        }

        return $result;
    }

    public static function htmlAttr(Environment $env, ...$args): string
    {
        $attr = self::htmlAttrMerge(...$args);

        if (isset($attr['class'])) {
            $attr['class'] = trim(implode(' ', $attr['class']));
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
        $runtime = $env->getRuntime(EscaperRuntime::class);

        foreach ($attr as $name => $value) {
            $result .= $runtime->escape($name, 'html_attr').'="'.$runtime->escape($value).'" ';
        }

        return trim($result);
    }
}
