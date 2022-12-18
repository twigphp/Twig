``parent``
==========

When a template uses inheritance, it's possible to render the contents of the
parent block when overriding a block by using the ``parent`` function:

.. code-block:: html+twig

    {% extends "base.html" %}

    {% block sidebar %}
        <h3>Table Of Contents</h3>
        ...
        {{ parent() }}
    {% endblock %}

The ``parent()`` call will return the content of the ``sidebar`` block as
defined in the ``base.html`` template.

In addition, the ``parent`` function takes one optional argument that specifies the inheritance level
in case there are multiple levels of inheritance:

.. code-block:: twig

    {{ parent(level=2) }}

This allows you to render the contents of a template that's further up the inheritance tree,
without rendering the contents of the intermediate templates.

.. seealso::

    :doc:`extends<../tags/extends>`, :doc:`block<../functions/block>`, :doc:`block<../tags/block>`
