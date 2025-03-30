``html_cva``
============

.. versionadded:: 3.12

    The ``html_cva`` function was added in Twig 3.12.

`CVA (Class Variant Authority)`_ is a concept from the JavaScript world and used
by the well-known `shadcn/ui`_ library.
The CVA concept is used to render multiple variations of components, applying
a set of conditions and recipes to dynamically compose CSS class strings (color, size, etc.),
to create highly reusable and customizable templates.

The concept of CVA is powered by a ``html_cva()`` Twig
function where you define ``base`` classes that should always be present and then different
``variants`` and the corresponding classes:

.. code-block:: html+twig

    {# templates/alert.html.twig #}
    {% set alert = html_cva(
        base: 'alert',
        variants: {
            color: {
                blue: 'bg-blue',
                red: 'bg-red',
                green: 'bg-green',
            },
            size: {
                sm: 'text-sm',
                md: 'text-md',
                lg: 'text-lg',
            }
        }
    ) %}

    <div class="{{ alert.apply({color, size}, class) }}">
        ...
    </div>

Then use the ``color`` and ``size`` variants to select the needed classes:

.. code-block:: twig

    {# index.html.twig #}
    {{ include('alert.html.twig', {'color': 'blue', 'size': 'md'}) }}
    {# class="alert bg-blue text-md" #}

    {{ include('alert.html.twig', {'color': 'green', 'size': 'sm'}) }}
    {# class="alert bg-green text-sm" #}

    {{ include('alert.html.twig', {'color': 'red', 'class': 'flex items-center justify-center'}) }}
    {# class="alert bg-red flex items-center justify-center" #}

CVA and Tailwind CSS
--------------------

CVA work perfectly with Tailwind CSS. The only drawback is that you can have class conflicts.
To "merge" conflicting classes together and keep only the ones you need, use the
``tailwind_merge()`` filter from `tales-from-a-dev/twig-tailwind-extra`_
with the ``html_cva()`` function:

.. code-block:: bash

    $ composer require tales-from-a-dev/twig-tailwind-extra

.. code-block:: html+twig

    {% set alert = html_cva(
        ...
    ) %}

    <div class="{{ alert.apply({color, size}, class)|tailwind_merge }}">
        ...
    </div>

Compound Variants
-----------------

You can define compound variants. A compound variant is a variant that applies
when multiple other variant conditions are met:

.. code-block:: html+twig

    {% set alert = html_cva(
        base: 'alert',
        variants: {
            color: {
                blue: 'bg-blue',
                red: 'bg-red',
                green: 'bg-green',
            },
            size: {
                sm: 'text-sm',
                md: 'text-md',
                lg: 'text-lg',
            }
        },
        compound_variants: [{
            # if color = red AND size = (md or lg), add the `font-bold` class
            color: ['red'],
            size: ['md', 'lg'],
            class: 'font-bold',
        }]
    ) %}

    <div class="{{ alert.apply({color, size}) }}">
        ...
    </div>

    {# index.html.twig #}

    {{ include('alert.html.twig', {color: 'red', size: 'lg'}) }}
    {# class="alert bg-red text-lg font-bold" #}

    {{ include('alert.html.twig', {color: 'green', size: 'sm'}) }}
    {# class="alert bg-green text-sm" #}

    {{ include('alert.html.twig', {color: 'red', size: 'md'}) }}
    {# class="alert bg-green text-md font-bold" #}

Default Variants
----------------

If no variants match, you can define a default set of classes to apply:

.. code-block:: html+twig

    {% set alert = html_cva(
        base: 'alert',
        variants: {
            color: {
                blue: 'bg-blue',
                red: 'bg-red',
                green: 'bg-green',
            },
            size: {
                sm: 'text-sm',
                md: 'text-md',
                lg: 'text-lg',
            },
            rounded: {
                sm: 'rounded-sm',
                md: 'rounded-md',
                lg: 'rounded-lg',
            }
        },
        default_variant: {
            rounded: 'md',
        }
    ) %}

    <div class="{{ alert.apply({color, size}) }}">
         ...
    </div>

    {# index.html.twig #}

    {{ include('alert.html.twig', {color: 'red', size: 'lg'}) }}
    {# class="alert bg-red text-lg rounded-md" #}

.. note::

    The ``html_cva`` function is part of the ``HtmlExtension`` which is not
    installed by default. Install it first:

    .. code-block:: bash

        $ composer require twig/html-extra

    Then, on Symfony projects, install the ``twig/extra-bundle``:

    .. code-block:: bash

            $ composer require twig/extra-bundle

    Otherwise, add the extension explicitly on the Twig environment::

            use Twig\Extra\Html\HtmlExtension;

            $twig = new \Twig\Environment(...);
            $twig->addExtension(new HtmlExtension());

This function works best when used with `TwigComponent`_.

.. _`CVA (Class Variant Authority)`: https://cva.style/docs/getting-started/variants
.. _`shadcn/ui`: https://ui.shadcn.com
.. _`tales-from-a-dev/twig-tailwind-extra`: https://github.com/tales-from-a-dev/twig-tailwind-extra
.. _`TwigComponent`: https://symfony.com/bundles/ux-twig-component/current/index.html
