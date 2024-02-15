``apply``
=========

The ``apply`` tag allows you to apply Twig filters on a block of template data:

.. code-block:: twig

    {% apply upper %}
        This text becomes uppercase
    {% endapply %}

You can also chain filters and pass arguments to them:

.. code-block:: html+twig

    {% apply lower|escape('html') %}
        <strong>SOME TEXT</strong>
    {% endapply %}

    {# outputs "&lt;strong&gt;some text&lt;/strong&gt;" #}

All twig filters that return a string can be applied with the ``apply`` tag.