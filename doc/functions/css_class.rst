``css_class``
=============

``css_class`` returns a string by conditionally joining class names together.

.. code-block:: jinja

    {{ css_class('a-class', 'another-class', {
        'errored': object.errored,
        'finished': object.finished,
        'pending': object.pending,
    }) }}
