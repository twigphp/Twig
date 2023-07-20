``sequence``
============

``sequence`` checks if a variable is a sequence:

.. code-block:: twig

    {% set users = ["Alice", "Bob"] %}
    {# evaluates to true if the users variable is a sequence #}
    {% if users is sequence %}
        {% for user in users %}
            Hello {{ user }}!
        {% endfor %}
    {% endif %}
