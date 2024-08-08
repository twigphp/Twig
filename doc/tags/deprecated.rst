``deprecated``
==============

Twig generates a deprecation notice (via a call to the ``trigger_error()``
PHP function) where the ``deprecated`` tag is used in a template:

.. code-block:: twig

    {# base.twig #}
    {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' %}
    {% extends 'layout.twig' %}

You can also deprecate a macro in the following way:

.. code-block:: twig

    {% macro welcome(name) %}
        {% deprecated 'The "welcome" macro is deprecated, use "hello" instead.' %}

        ...
    {% endmacro %}

Note that by default, the deprecation notices are silenced and never displayed nor logged.
See :ref:`deprecation-notices` to learn how to handle them.

.. versionadded:: 3.11

    The ``package`` and ``version`` options were added in Twig 3.11.

You can optionally add the package and the version that introduced the deprecation:

.. code-block:: twig

    {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' package='twig/twig' %}
    {% deprecated 'The "base.twig" template is deprecated, use "layout.twig" instead.' package='twig/twig' version='3.11' %}

.. note::

    Don't use the ``deprecated`` tag to deprecate a ``block`` as the
    deprecation cannot always be triggered correctly.
