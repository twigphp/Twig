``merge``
=========

The ``merge`` filter merges sequences and mappings:

For sequences, new values are added at the end of the existing ones:

.. code-block:: twig

    {% set values = [1, 2] %}

    {% set values = values|merge(['apple', 'orange']) %}

    {# values now contains [1, 2, 'apple', 'orange'] #}

For mappings, the merging process occurs on the keys; if the key does not
already exist, it is added but if the key already exists, its value is
overridden:

.. code-block:: twig

    {% set items = {'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'unknown'} %}

    {% set items = items|merge({ 'peugeot': 'car', 'renault': 'car' }) %}

    {# items now contains {'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'car', 'renault': 'car'} #}

.. tip::

    If you want to ensure that some values are defined in a mapping (by given
    default values), reverse the two elements in the call:

    .. code-block:: twig

        {% set items = {'apple': 'fruit', 'orange': 'fruit'} %}

        {% set items = {'apple': 'unknown'}|merge(items) %}

        {# items now contains {'apple': 'fruit', 'orange': 'fruit'} #}

.. note::

    Internally, Twig uses the PHP `array_merge`_ function. It supports
    Traversable objects by transforming those to arrays.

.. _`array_merge`: https://www.php.net/array_merge
