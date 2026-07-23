``block``
=========

Blocks are used for inheritance and act as placeholders and replacements at
the same time. They are documented in detail in the documentation for the
:doc:`extends<../tags/extends>` tag.

Block names must consist of alphanumeric characters, and underscores. The first character can't be a digit and dashes are not permitted.

.. versionadded:: 3.29

    The ``docs`` option was added in Twig 3.29.

Document a block by adding a ``docs`` option after its name:

.. code-block:: twig

    {% block content docs="The main content of the page" %}
        ...
    {% endblock %}

While Twig itself does not use this documentation, it is stored on the parsed
``block`` node so that tools like IDEs or documentation generators can analyze
it.

.. seealso::

    :doc:`block<../functions/block>`, :doc:`parent<../functions/parent>`, :doc:`use<../tags/use>`, :doc:`extends<../tags/extends>`
