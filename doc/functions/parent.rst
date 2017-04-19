``parent``
==========

When a template uses inheritance, it's possible to render the contents of the
parent block when overriding a block by using the ``parent`` function:

.. code-block:: jinja

    {% extends "base.html" %}

    {% block sidebar %}
        <h3>Table Of Contents</h3>
        ...
        {{ parent() }}
    {% endblock %}

The ``parent()`` call will return the content of the ``sidebar`` block as
defined in the ``base.html`` template.

``parent()`` can also be used in conjunction with the :doc:`block<../tags/use>` 
tag, in which case it seems to pull from the most recently defined parent block: 

.. code-block:: jinja

    {% extends "base.html" %}
    
    {% use "blocks.html" %}
    
    {% block sidebar %}
        {{ parent() }}
    {% endblock %}
    
    {% block title %}{% endblock %}
    {% block content %}{% endblock %}

In this example, ``parent()`` will call the sidebar block from the ``blocks.html`` template.

.. seealso:: :doc:`extends<../tags/extends>`, :doc:`block<../functions/block>`, :doc:`block<../tags/block>`, :doc:`block<../tags/use>`
