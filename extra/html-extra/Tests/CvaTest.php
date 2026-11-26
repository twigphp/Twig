<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Extra\Html\Cva;
use Twig\Extra\Html\HtmlExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

class CvaTest extends TestCase
{
    /**
     * @dataProvider recipeProvider
     */
    public function testRecipes(array $recipe, array $recipes, string $expected): void
    {
        $recipeClass = new Cva($recipe['base'] ?? '', $recipe['variants'] ?? [], $recipe['compounds'] ?? [], $recipe['defaultVariants'] ?? []);

        $this->assertEquals($expected, $recipeClass->apply($recipes));
    }

    public function testApply(): void
    {
        $recipe = new Cva('font-semibold border rounded', [
            'colors' => [
                'primary' => 'text-primary',
                'secondary' => 'text-secondary',
            ],
            'sizes' => [
                'sm' => 'text-sm',
                'md' => 'text-md',
                'lg' => 'text-lg',
            ],
        ], [
            [
                'colors' => ['primary'],
                'sizes' => ['sm'],
                'class' => 'text-red-500',
            ],
        ]);

        $this->assertEquals('font-semibold border rounded text-primary text-sm text-red-500', $recipe->apply(['colors' => 'primary', 'sizes' => 'sm']));
    }

    public function testApplyWithNullRecipeValue(): void
    {
        $recipe = new Cva('font-semibold', [
            'colors' => [
                'primary' => 'text-primary',
            ],
            'sizes' => [
                'sm' => 'text-sm',
            ],
        ], [], [
            'sizes' => 'sm',
        ]);

        $this->assertSame('font-semibold text-sm', $recipe->apply(['colors' => null, 'sizes' => null]));
    }

    public function testApplySameRecipesAgain(): void
    {
        $createRecipe = static fn () => new Cva("font-semibold  border\nrounded border", [
            'colors' => [
                'primary' => 'text-primary',
                'secondary' => 'text-secondary',
            ],
        ]);
        $recipe = $createRecipe();

        $this->assertSame('font-semibold border rounded text-primary', $recipe->apply(['colors' => 'primary']));
        $this->assertSame('font-semibold border rounded text-secondary', $recipe->apply(['colors' => 'secondary']));
        $this->assertSame('font-semibold border rounded text-primary', $recipe->apply(['colors' => 'primary']));
        $this->assertSame('font-semibold border rounded text-primary', $createRecipe()->apply(['colors' => 'primary']));
    }

    public function testObjectValuesDoNotExposeTheirProperties(): void
    {
        $object = new class {
            public $public = 'PUBLIC';
            protected $protected = 'PROTECTED';
            private $private = 'PRIVATE';
        };

        $this->assertSame('base', (new Cva('base', ['c' => ['p' => $object]]))->apply(['c' => 'p']));
        $this->assertSame('base x', (new Cva('base', ['c' => ['p' => 'x']], [['c' => ['p'], 'class' => $object]]))->apply(['c' => 'p']));
        $this->assertSame('base', (new Cva('base', ['c' => ['p' => $object]], [], ['c' => 'p']))->apply([]));
        $this->assertSame('base', (new Cva('base', [], [['c' => $object, 'class' => 'matched']]))->apply(['c' => 'PRIVATE']));
    }

    public function testArrayAccessVariantsDoNotExposeTheirOffsets(): void
    {
        $twig = new Environment(new ArrayLoader([
            'variant' => "{{ html_cva('base', {c: bag}).apply({c: 'secret'}) }}",
            'default_variant' => "{{ html_cva('base', {c: bag}, [], {c: 'secret'}).apply({}) }}",
        ]), ['autoescape' => false]);
        $twig->addExtension(new HtmlExtension());
        $twig->addExtension(new SandboxExtension(new SecurityPolicy([], [], [Cva::class => ['apply']], [], ['html_cva']), true));
        $context = ['bag' => new \ArrayObject(['secret' => 'SECRET'])];

        $this->assertSame('base', $twig->render('variant', $context));
        $this->assertSame('base', $twig->render('default_variant', $context));
    }

    public function testApplyWithNullString(): void
    {
        $recipe = new Cva('font-semibold border rounded', [
            'colors' => [
                'primary' => 'text-primary',
                'secondary' => 'text-secondary',
            ],
            'sizes' => [
                'sm' => 'text-sm',
                'md' => 'text-md',
                'lg' => 'text-lg',
            ],
        ], [
            [
                'colors' => ['primary'],
                'sizes' => ['sm'],
                'class' => 'text-red-500',
            ],
        ]);

        $this->assertEquals('font-semibold border rounded text-primary text-sm text-red-500 flex justify-center', $recipe->apply(['colors' => 'primary', 'sizes' => 'sm'], 'flex', null, 'justify-center'));
    }

    public function testApplyDoesNotCacheLongClassLists(): void
    {
        $classListCache = new \ReflectionProperty(Cva::class, 'classListCache');
        $classListCache->setValue(null, []);

        $this->assertSame('text-primary', (new Cva(str_repeat('text-primary ', 500)))->apply([]));
        $this->assertEmpty($classListCache->getValue());

        $this->assertSame('font-semibold border', (new Cva('font-semibold border font-semibold'))->apply([]));
        $this->assertSame(['font-semibold border font-semibold' => 'font-semibold border'], $classListCache->getValue());
    }

    public static function recipeProvider(): iterable
    {
        yield 'base null' => [
            ['variants' => [
                'colors' => [
                    'primary' => 'text-primary',
                    'secondary' => 'text-secondary',
                ],
                'sizes' => [
                    'sm' => 'text-sm',
                    'md' => 'text-md',
                    'lg' => 'text-lg',
                ],
            ]],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'text-primary text-sm',
        ];

        yield 'base empty' => [
            [
                'base' => '',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ]],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'text-primary text-sm',
        ];

        yield 'base array' => [
            [
                'base' => ['font-semibold', 'border', 'rounded'],
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ]],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm',
        ];

        yield 'no recipes match' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
            ],
            ['colors' => 'red', 'sizes' => 'test'],
            'font-semibold border rounded',
        ];

        yield 'simple variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm',
        ];

        yield 'simple variants as array' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => ['text-primary', 'uppercase'],
                        'secondary' => ['text-secondary', 'uppercase'],
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary uppercase text-sm',
        ];

        yield 'simple variants with custom' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
            ],
            ['colors' => 'secondary', 'sizes' => 'md'],
            'font-semibold border rounded text-secondary text-md',
        ];

        yield 'compound variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => 'primary',
                        'sizes' => ['sm'],
                        'class' => 'text-red-100',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm text-red-100',
        ];

        yield 'compound variants with true' => [
            [
                'base' => 'button',
                'variants' => [
                    'colors' => [
                        'blue' => 'btn-blue',
                        'red' => 'btn-red',
                    ],
                    'disabled' => [
                        'true' => 'disabled',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => 'blue',
                        'disabled' => ['true'],
                        'class' => 'font-bold',
                    ],
                ],
            ],
            ['colors' => 'blue', 'disabled' => 'true'],
            'button btn-blue disabled font-bold',
        ];

        yield 'compound variants as array' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary'],
                        'sizes' => ['sm'],
                        'class' => ['text-red-900', 'bold'],
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm text-red-900 bold',
        ];

        yield 'multiple compound variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-300',
                    ],
                    [
                        'colors' => ['primary'],
                        'sizes' => ['md'],
                        'class' => 'text-blue-300',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm text-red-300',
        ];

        yield 'compound with multiple variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-800',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm text-red-800',
        ];

        yield 'compound doesn\'t match' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['danger', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm',
        ];

        yield 'default variables' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                    'rounded' => [
                        'sm' => 'rounded-sm',
                        'md' => 'rounded-md',
                        'lg' => 'rounded-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['danger', 'secondary'],
                        'sizes' => 'sm',
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                    'rounded' => 'md',
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm rounded-md',
        ];

        yield 'default variables all overwrite' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                    'rounded' => [
                        'sm' => 'rounded-sm',
                        'md' => 'rounded-md',
                        'lg' => 'rounded-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['danger', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                    'rounded' => 'md',
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm', 'rounded' => 'lg'],
            'font-semibold border rounded text-primary text-sm rounded-lg',
        ];

        yield 'default variables without matching variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                    'rounded' => [
                        'sm' => 'rounded-sm',
                        'md' => 'rounded-md',
                        'lg' => 'rounded-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['danger', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                    'rounded' => 'md',
                ],
            ],
            [],
            'font-semibold border rounded text-primary text-sm rounded-md',
        ];

        yield 'default variables with boolean' => [
            [
                'base' => 'button',
                'variants' => [
                    'colors' => [
                        'blue' => 'btn-blue',
                        'red' => 'btn-red',
                    ],
                    'disabled' => [
                        'true' => 'disabled',
                        'false' => 'opacity-100',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'blue',
                    'disabled' => 'false',
                ],
            ],
            [],
            'button btn-blue opacity-100',
        ];

        yield 'default variables with false' => [
            [
                'base' => 'button',
                'variants' => [
                    'disabled' => [
                        'true' => 'disabled',
                        'false' => 'opacity-100',
                    ],
                ],
                'defaultVariants' => [
                    'disabled' => false,
                ],
            ],
            [],
            'button opacity-100',
        ];

        yield 'default variables with true' => [
            [
                'base' => 'button',
                'variants' => [
                    'disabled' => [
                        'true' => 'disabled',
                        'false' => 'opacity-100',
                    ],
                ],
                'defaultVariants' => [
                    'disabled' => true,
                ],
            ],
            [],
            'button disabled',
        ];

        yield 'compound variants with default variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                ],
            ],
            [],
            'font-semibold border rounded text-primary text-sm text-red-500',
        ];

        yield 'compound variants with partial default variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                ],
            ],
            ['colors' => 'primary'],
            'font-semibold border rounded text-primary text-sm text-red-500',
        ];

        yield 'compound variants with null recipe falling back to default variant' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                ],
            ],
            ['colors' => null, 'sizes' => 'sm'],
            'font-semibold border rounded text-sm text-primary text-red-500',
        ];

        yield 'compound variants overridden by recipe does not match default' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                ],
            ],
            ['colors' => 'secondary', 'sizes' => 'sm'],
            'font-semibold border rounded text-secondary text-sm',
        ];

        yield 'compound variants with boolean default variant' => [
            [
                'base' => 'button',
                'variants' => [
                    'colors' => [
                        'blue' => 'btn-blue',
                    ],
                    'disabled' => [
                        'true' => 'disabled',
                        'false' => 'opacity-100',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['blue'],
                        'disabled' => [false],
                        'class' => 'cursor-pointer',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'blue',
                    'disabled' => false,
                ],
            ],
            [],
            'button btn-blue opacity-100 cursor-pointer',
        ];

        yield 'compound variants with boolean recipe overriding boolean default' => [
            [
                'base' => 'button',
                'variants' => [
                    'colors' => [
                        'blue' => 'btn-blue',
                    ],
                    'disabled' => [
                        'true' => 'disabled',
                        'false' => 'opacity-100',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['blue'],
                        'disabled' => [false],
                        'class' => 'cursor-pointer',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'blue',
                    'disabled' => false,
                ],
            ],
            ['disabled' => true],
            'button disabled btn-blue',
        ];

        yield 'boolean string variants true / true' => [
            [
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'disabled' => [
                        'true' => 'disable',
                    ],
                ],
            ],
            ['colors' => 'primary', 'disabled' => true],
            'text-primary disable',
        ];

        yield 'boolean string variants true / false' => [
            [
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'disabled' => [
                        'true' => 'disable',
                    ],
                ],
            ],
            ['colors' => 'primary', 'disabled' => false],
            'text-primary',
        ];

        yield 'boolean string variants false / true' => [
            [
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'disabled' => [
                        'false' => 'disable',
                    ],
                ],
            ],
            ['colors' => 'primary', 'disabled' => true],
            'text-primary',
        ];

        yield 'boolean string variants false / false' => [
            [
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'disabled' => [
                        'false' => 'disable',
                    ],
                ],
            ],
            ['colors' => 'primary', 'disabled' => false],
            'text-primary disable',
        ];

        yield 'boolean string variants missing' => [
            [
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'disabled' => [
                        'true' => 'disable',
                    ],
                ],
            ],
            ['colors' => 'primary'],
            'text-primary',
        ];

        yield 'boolean list variants true' => [
            [
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'disabled' => [
                        'true' => ['disable', 'opacity-50'],
                    ],
                ],
            ],
            ['colors' => 'primary', 'disabled' => true],
            'text-primary disable opacity-50',
        ];

        yield 'boolean list variants false' => [
            [
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'disabled' => [
                        'true' => ['disable', 'opacity-50'],
                    ],
                ],
            ],
            ['colors' => 'primary', 'disabled' => false],
            'text-primary',
        ];

        yield 'boolean list variants missing' => [
            [
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'disabled' => [
                        'true' => ['disable', 'opacity-50'],
                    ],
                ],
            ],
            ['colors' => 'primary'],
            'text-primary',
        ];
    }

    /**
     * @dataProvider provideAdditionalClassesCases
     */
    public function testAdditionalClasses(string|array $base, array|string $additionals, string $expected): void
    {
        $cva = new Cva($base);
        if (!$additionals) {
            $this->assertEquals($expected, $cva->apply([]));
        } else {
            $this->assertEquals($expected, $cva->apply([], ...(array) $additionals));
        }
    }

    public static function provideAdditionalClassesCases(): iterable
    {
        yield 'additionals_are_optional' => [
            '',
            'foo',
            'foo',
        ];

        yield 'additional_are_used' => [
            '',
            'foo',
            'foo',
        ];

        yield 'additionals_are_used' => [
            '',
            ['foo', 'bar'],
            'foo bar',
        ];

        yield 'additionals_preserve_order' => [
            ['foo'],
            ['bar', 'foo'],
            'foo bar',
        ];

        yield 'additional_are_deduplicated' => [
            '',
            ['bar', 'bar'],
            'bar',
        ];
    }
}
