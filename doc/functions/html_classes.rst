``html_classes``
================

.. versionadded:: 2.12
    The ``html_classes`` function was added in Twig 2.12.

The ``html_classes`` function returns a string by conditionally joining class
names together:

.. code-block:: jinja

    <p class="{{ html_classes('a-class', 'another-class', {
        'errored': object.errored,
        'finished': object.finished,
        'pending': object.pending,
    }) }}">How are you doing?</p>

.. note::

    The ``html_classes`` function is part of the ``HtmlExtension`` which is not
    enabled by default; you must add it explicitly on the Twig environment::

        use Twig\Extension\HtmlExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new HtmlExtension());
