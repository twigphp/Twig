``raw``
=======

The ``raw`` tag marks sections as being raw text that should not be parsed.
For example to put Twig syntax as example into a template you can use this
snippet:

.. code-block:: jinja

    {% raw %}
        <ul>
        {% for item in seq %}
            <li>{{ item }}</li>
        {% endfor %}
        </ul>
    {% endraw %}
