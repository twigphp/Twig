``html_attr``
================

.. versionadded:: 2.x
    The ``html_attr`` function was added in Twig 2.x.

The ``html_attr`` function returns a string by joining html attributes together:

.. code-block:: twig

    {% set attr = {
            id: 123,
            disabled: true,
            hidden: false,
            type: 'button',
            'aria-expandend': 'false',
        } %}
    <button{{ html_attr(attr) }}></button>

    {# example output: <button id="123" disabled type="button" aria-expandend="false"></button> #}

.. note::

    The ``html_attr`` function is part of the ``HtmlExtension`` which is not
    installed by default. Install it first:

    .. code-block:: bash

        $ composer req twig/html-extra

    Then, use the ``twig/extra-bundle`` on Symfony projects or add the extension
    explictly on the Twig environment::

        use Twig\Extra\Html\HtmlExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new HtmlExtension());
