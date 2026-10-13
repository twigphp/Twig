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

Like :doc:`block<block>`, the function uses the block as overridden by the current template, and it can look for the block in another template:

.. code-block:: twig

    {{ block_data('attributes', 'common_blocks.html.twig') }}

Arguments
---------

* ``name``: The block name
* ``template``: The template where to look for the block
