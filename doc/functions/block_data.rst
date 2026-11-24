``block_data``
==============

.. versionadded:: 3.31

    The ``block_data`` function was added in Twig 3.31.

The ``block_data`` function renders a block and returns the data that the block exported with :doc:`block_export<block_export>`, instead of its output:

.. code-block:: twig

    {% block attributes %}
        {% do block_export({id: 'email', required: true}) %}
        id="email" required
    {% endblock %}

    {{ block_data('attributes').id }}
    {# email #}

The output of the block is discarded.
If the block does not call ``block_export``, the function returns an empty array.
If the block calls ``block_export`` several times, the last call wins.
This includes the blocks and templates that the block renders, for example with the :doc:`block<block>` function or the :doc:`include<include>` function.
The data exported inside a nested ``block_data`` call is returned by that call only, not by the outer one.

Like :doc:`block<block>`, the function uses the block as overridden by the current template, and it can look for the block in another template:

.. code-block:: twig

    {{ block_data('attributes', 'common_blocks.html.twig') }}

.. note::

    The ``block_data`` function is part of the ``HtmlExtension`` which is not
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

Arguments
---------

* ``name``: The block name
* ``template``: The template where to look for the block
