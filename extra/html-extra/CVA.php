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

final class CVA
{
    private $base;
    private $variants;
    private $compoundVariants;
    private $defaultVariants;

    /**
     * @var string|list<string|null>|null
     * @var array<string, array<string, string|list<string|null>>|null the array should have the following format [variantCategory => [variantName => classes]]
     *                                                ex: ['colors' => ['primary' => 'bleu-8000', 'danger' => 'red-800 text-bold'], 'size' => [...]]
     * @var array<array<string, string|array<string>>> the array should have the following format ['variantsCategory' => ['variantName', 'variantName'], 'class' => 'text-red-500']
     * @var array<string, string>|null
     */
    public function __construct(
        $base = null,
        $variants = null,
        $compoundVariants = null,
        $defaultVariants = null
    ) {
        $this->base = $base;
        $this->variants = $variants;
        $this->compoundVariants = $compoundVariants;
        $this->defaultVariants = $defaultVariants;
    }

    public function apply(array $recipes, string ...$classes): string
    {
        return trim($this->resolve($recipes).' '.implode(' ', $classes));
    }

    public function resolve(array $recipes): string
    {
        if (\is_array($this->base)) {
            $classes = implode(' ', $this->base);
        } else {
            $classes = $this->base ?? '';
        }

        foreach ($recipes as $recipeName => $recipeValue) {
            if (!isset($this->variants[$recipeName][$recipeValue])) {
                continue;
            }

            if (\is_string($this->variants[$recipeName][$recipeValue])) {
                $classes .= ' '.$this->variants[$recipeName][$recipeValue];
            } else {
                $classes .= ' '.implode(' ', $this->variants[$recipeName][$recipeValue]);
            }
        }

        if (null !== $this->compoundVariants) {
            foreach ($this->compoundVariants as $compound) {
                $isCompound = true;
                foreach ($compound as $compoundName => $compoundValues) {
                    if ('class' === $compoundName) {
                        continue;
                    }

                    if (!isset($recipes[$compoundName])) {
                        $isCompound = false;
                        break;
                    }

                    if (!\in_array($recipes[$compoundName], $compoundValues)) {
                        $isCompound = false;
                        break;
                    }
                }

                if ($isCompound) {
                    if (!isset($compound['class'])) {
                        throw new \LogicException('A compound recipe matched but no classes are registered for this match');
                    }

                    if (!\is_string($compound['class']) && !\is_array($compound['class'])) {
                        throw new \LogicException('The class of a compound recipe should be a string or an array of string');
                    }

                    if (\is_string($compound['class'])) {
                        $classes .= ' '.$compound['class'];
                    } else {
                        $classes .= ' '.implode(' ', $compound['class']);
                    }
                }
            }
        }

        if (null !== $this->defaultVariants) {
            foreach ($this->defaultVariants as $defaultVariantName => $defaultVariantValue) {
                if (!isset($recipes[$defaultVariantName])) {
                    $classes .= ' '.$this->variants[$defaultVariantName][$defaultVariantValue];
                }
            }
        }

        return trim($classes);
    }
}