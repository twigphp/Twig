``set``
=======

Inside code blocks you can also assign values to variables. Assignments use
the ``set`` tag and can have multiple targets:

.. code-block:: jinja

    {% set foo = 'foo' %}
This sets a variable called 'foo' with the value of 'foo'

.. code-block:: jinja

    {% set foo = [1, 2] %}
This sets a variable called 'foo' with a value which is an array with two keys, 1 and 2


.. code-block:: jinja

    {% set foo = {'foo': 'bar'} %}
This sets a variable called foo with an associative array. The associative array has an attribute called 'foo' which itself has a value of 'bar'


.. code-block:: jinja

    {% set foo = 'foo' ~ 'bar' %}
This sets a variables called foo with a concatenated string (``~`` concatenates) of 'foo' and 'bar', meaning the true value is 'foobar'


.. code-block:: jinja

    {% set foo, bar = 'foo', 'bar' %}
This sets two variables: foo and bar, with the values of 'foo' and 'bar' respectively.


The ``set`` tag can also be used to 'capture' chunks of text:

.. code-block:: jinja

    {% set foo %}
      <div id="pagination">
        ...
      </div>
    {% endset %}

.. caution::

    If you enable automatic output escaping, Twig will only consider the
    content to be safe when capturing chunks of text.
