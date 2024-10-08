``guard``
=========

.. versionadded:: 3.15

    The ``guard`` tag was added in Twig 3.15.

The ``guard`` statement checks if some Twig callables are available at
**compilation time** to bypass code compilation that would otherwise fail.

.. code-block:: twig

    {% guard function importmap %}
        {{ importmap('app') }}
    {% endguard %}

The first argument is the Twig callable to test: ``filter``, ``function``, or
``test``. The second argument is the Twig callable name you want to test.

You can also generate different code if the callable does not exist:

.. code-block:: twig

    {% guard function importmap %}
        {{ importmap('app') }}
    {% else %}
        {# the importmap function doesn't exist, generate fallback code #}
    {% endguard %}
