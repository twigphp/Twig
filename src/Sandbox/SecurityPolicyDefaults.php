<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Sandbox;

/**
 * A set of defaults for the security policy. These can be automatically added to the policy by including the special value SecurityPolicyDefaults::INCLUDE_DEFAULTS in the array of allowed tags, filters, functions, methods, or properties.
 * The static functions in this class simply check if that special value is present, and if so, replace it with the actual defaults. 
 * 
 * @author Yaakov Saxon <ysaxon@gmail.com>
 */

 class SecurityPolicyDefaults {
    public const INCLUDE_DEFAULTS = '(include_defaults)';

    // TODO: Note that below defaults have not been exhaustively analyzed for correctness or completeness but rather represent an example of what defaults could look like for the initial code review of this pull request.
    const TAGS = [
        'autoescape',
        'filter',
        'do',
        'flush',
        'for',
        'set',
        'verbatium',
        'if',
        'spaceless',
        'sandbox'
    ];

    const FILTERS =  [
        'abs',
        'batch',
        'capitalize',
        'convert_encoding',
        'date',
        'date_modify',
        'default',
        'escape',
        'first',
        'format',
        'join',
        'json_encode',
        'keys',
        'last',
        'length',
        'lower',
        'merge',
        'nl2br',
        'number_format',
        'raw',
        'replace',
        'reverse',
        'slice',
        'sort',
        'split',
        'striptags',
        'title',
        'trim',
        'upper',
        'url_encode',
    ];
    
    const FUNCTIONS = [
        'attribute',
        'block',
        'constant',
        'cycle',
        'date',
        'html_classes',
        'max',
        'min',
        'parent',
        'random',
        'range',
        'source',
    ];

    const METHODS = [
        // ...
    ];
    
    const PROPERTIES = [
        // ...
    ];

    private static function processDefaultsTokenForMethods(array $input){
        return self::processDefaultsTokenForAssociativeArray($input, self::METHODS);
    }

    private static function processDefaultsTokenForProperties(array $input){
        return self::processDefaultsTokenForAssociativeArray($input, self::PROPERTIES);
    }

    private static function processDefaultsTokenForTags(array $input){
        return self::processDefaultsTokenForIndexedArray($input, self::TAGS);
    }

    private static function processDefaultsTokenForFilters(array $input){
        return self::processDefaultsTokenForIndexedArray($input, self::FILTERS);
    }

    private static function processDefaultsTokenForFunctions(array $input){
        return self::processDefaultsTokenForIndexedArray($input, self::FUNCTIONS);
    }


    private static function processDefaultsTokenForIndexedArray(array $array, array $defaults)
    {
        if (in_array(self::INCLUDE_DEFAULTS, $array)) {
            //remove DEFAULTS marker
            $array = array_diff($array, [self::INCLUDE_DEFAULTS]);
            //add defaults
            $array = array_merge($array, $defaults);
            //uniquify
            $array = array_unique($array);
        }
        return $array;
    }

    private static function processDefaultsTokenForAssociativeArray(array $array, array $defaults)
    {
        $key = array_search(SecurityPolicyDefaults::INCLUDE_DEFAULTS, $array);
        if ($key !== false) {
            //remove DEFAULTS marker
            unset($array[$key]);
            //add defaults
            $array = self::AssociativeArrayMerge($array, $defaults);
        }
        return $array;
    }

    private static function AssociativeArrayMerge($array1, $array2)
    {
        $new_array = [];
        // Merge keys from both arrays
        $all_keys = array_merge(array_keys($array1), array_keys($array2));
        $all_keys = array_unique($all_keys);

        // Iterate through all unique keys
        foreach ($all_keys as $key) {
            $val1 = isset($array1[$key]) ? $array1[$key] : [];
            $val2 = isset($array2[$key]) ? $array2[$key] : [];

            // Convert to array if not already an array
            if (!is_array($val1)) {
                $val1 = [$val1];
            }
            if (!is_array($val2)) {
                $val2 = [$val2];
            }

            // Merge the values and eliminate duplicates
            $combined_vals = array_unique(array_merge($val1, $val2));
            $new_array[$key] = $combined_vals;
        }
        return $new_array;
    }

 }