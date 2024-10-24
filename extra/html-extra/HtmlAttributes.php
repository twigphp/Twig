<?php

namespace Twig\Extra\Html;

use Twig\Error\RuntimeError;

final class HtmlAttributes
{
    /**
     * Merges multiple attribute group arrays into a single array.
     *
     * `HtmlAttributes::merge(['id' => 'a', 'disabled' => true], ['hidden' => true])` becomes
     * `['id' => 'a', 'disabled' => true, 'hidden' => true]`
     *
     * attributes override each other in the order they are provided.
     *
     * `HtmlAttributes::merge(['id' => 'a'], ['id' => 'b'])` becomes `['id' => 'b']`.
     *
     * However, `class` and `style` attributes are merged into an array so they can be concatenated in later processing.
     *
     * `HtmlAttributes::merge(['class' => 'a'], ['class' => 'b'], ['class' => 'c'])` becomes
     * `['class' => ['a' => true, 'b' => true, 'c' => true]]`.
     *
     * style attributes are also merged into an array so they can be concatenated in later processing.
     *
     * `HtmlAttributes::merge(['style' => 'color: red'], ['style' => ['background-color' => 'blue']])` becomes
     * `['style' => ['color: red;' => true, 'background-color: blue;' => true]]`.
     *
     * style attributes which are arrays with false and null values are also processed
     *
     * `HtmlAttributes::merge(['style' => ['color: red' => true]], ['style' => ['display: block' => false]]) becomes
     * `['style' => ['color: red;' => true, 'display: block;' => false]]`.
     *
     * attributes can be provided as an array of key, value where the value can be true, false or null.
     *
     * Example:
     * `HtmlAttributes::merge(['class' => ['a' => true, 'b' => false], ['class' => ['c' => null']])` becomes
     * `['class' => ['a' => true, 'b' => false, 'c' => null]]`.
     *
     * `aria` and `data` arrays are expanded into `aria-*` and `data-*` attributes before further processing.
     *
     * Example:
     *
     * `HtmlAttributes::merge([data' => ['count' => '1']])` becomes `['data-count' => '1']`.
     * `HtmlAttributes::merge(['aria' => ['hidden' => true]])` becomes `['aria-hidden' => true]`.
     *
     * @param ...$attributeGroup
     * @return array
     * @throws RuntimeError
     * @see ./Tests/HtmlAttributesTest.php for usage examples
     *
     */
    public static function merge(...$attributeGroup): array
    {
        $result = [];

        $attributeGroupCount = 0;

        foreach ($attributeGroup as $attributes) {

            $attributeGroupCount++;

            // Skip empty attributes
            // Return early if no attributes are provided
            // This could be false or null when using the twig ternary operator
            if (!$attributes) {
                continue;
            }

            if (!is_iterable($attributes)) {
                throw new RuntimeError(sprintf('"%s" only works with mappings or "Traversable", got "%s" for argument %d.', self::class, \gettype($attributes), $attributeGroupCount));
            }

            // Alternative to is_iterable check above, cast the attributes to an array
            // This would produce weird results but would not throw an error
//            $attributes = (array)$attributes;

            // data and aria arrays are expanded into data-* and aria-* attributes
            $expanded = [];
            foreach ($attributes as $key => $value) {
                if (in_array($key, ['data', 'aria'])) {
                    $value = (array)$value;
                    foreach ($value as $k => $v) {
                        $k = $key . '-' . $k;
                        $expanded[$k] = $v;
                    }
                    continue;
                }
                $expanded[$key] = $value;
            }

            // Reset the attributes array to the flattened version
            $attributes = $expanded;

            foreach ($attributes as $key => $value) {

                // Treat class and data-controller attributes as arrays
                if (in_array($key, [
                    'class',
                    'data-controller',
                    'data-action',
                    'data-targets',
                ])) {
                    if (!array_key_exists($key, $result)) {
                        $result[$key] = [];
                    }
                    $value = (array)$value;
                    foreach ($value as $k => $v) {
                        if (is_int($k)) {
                            $classes = explode(' ', $v);
                            foreach ($classes as $class) {
                                $result[$key][$class] = true;
                            }
                        } else {
                            $classes = explode(' ', $k);
                            foreach ($classes as $class) {
                                $result[$key][$class] = $v;
                            }
                        }
                    }
                    continue;
                }

                if ($key === 'style') {
                    if (!array_key_exists('style', $result)) {
                        $result['style'] = [];
                    }
                    $value = (array)$value;
                    foreach ($value as $k => $v) {
                        if (is_int($k)) {
                            $styles = array_filter(explode(';', $v));
                            foreach ($styles as $style) {
                                $style = explode(':', $style);
                                $sKey = trim($style[0]);
                                $sValue = trim($style[1]);
                                $result['style']["$sKey: $sValue;"] = true;
                            }
                        } elseif (is_bool($v) || is_null($v)) {
                            $styles = array_filter(explode(';', $k));
                            foreach ($styles as $style) {
                                $style = explode(':', $style);
                                $sKey = trim($style[0]);
                                $sValue = trim($style[1]);
                                $result['style']["$sKey: $sValue;"] = $v;
                            }
                        } else {
                            $sKey = trim($k);
                            $sValue = trim($v);
                            $result['style']["$sKey: $sValue;"] = true;
                        }
                    }
                    continue;
                }

                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function renderAttributes($attributes): string
    {
        $return = [];

        foreach ($attributes as $key => $value) {

            // Skip null values regardless of attribute key
            if ($value === null) {
                continue;
            }

            // Handle class, style, data-controller value coercion
            // array[] -> concatenate string
            if (in_array($key, ['class', 'style', 'data-controller'])) {
                $value = array_filter($value);
                $value = array_keys($value);
                $value = implode(' ', $value);
            }

            // Handle aria-* value coercion
            // true -> 'true'
            // false -> 'false,
            // array[] ->  concatenate string
            if (str_starts_with($key, 'aria-')) {
                if ($value === true) {
                    $value = 'true';
                } elseif ($value === false) {
                    $value = 'false';
                } elseif(is_array($value)) {
                    $value = join(" ", array_filter($value));
                }

            }

            // Handle data-* value coercion
            // array[] -> json
            if (str_starts_with($key, 'data-')) {
                if(is_array($value)) {
                    $value = json_encode($value);
                }
            }

            // Skip false values
            if ($value === false) {
                continue;
            }

            // Boolean attribute doesn't have a value
             if ($value === true) {
                $return[] = $key;
                continue;
            }

            // Everything else gets added as an encoded value
            $return[] = $key . '="' . htmlspecialchars($value) . '"';
        }

        return implode(' ', $return);
    }
}