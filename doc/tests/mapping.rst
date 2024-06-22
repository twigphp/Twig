``mapping``
===========

``mapping`` checks if a variable is a mapping:

.. code-block:: twig

    {% set users = {alice: "Alice Dupond", bob: "Bob Smith"} %}
    {# evaluates to true if the users variable is a mapping #}
    {% if users is mapping %}
        {% for key, user in users %}
            {{ key }}: {{ user }};
        {% endfor %}
    {% endif %}
