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

List of Twig filters compatible with the ``apply`` tag:

- capitalize:
.. code-block:: twig

{% apply capitalize %}
    This text becomes capitalized
{% endapply %}

      {# outputs "this text becomes capitalized" #}

- date:
.. code-block:: twig

{% set myDate = "2023-12-14" %}
      {% apply date("Y-m-d") %}
          {{ myDate }}
      {% endapply %}

      {# outputs "2023-12-14" #}

- date_modify:
.. code-block:: twig

{% set myDate = "2023-12-14" %}
      {% apply date_modify("+1 day")|date("Y-m-d") %}
          {{ myDate }}
      {% endapply %}

      {# outputs "2023-12-15" #}

- escape:
.. code-block:: html+twig

{% apply escape('html') %}
    <strong>SOME TEXT</strong>
{% endapply %}

     {# outputs "<strong>SOME TEXT</strong>" #}

- format:
.. code-block:: twig

{% set items = "I like %s and %s." %}
      {% apply format("foo", "bar") %}
          {{ items }}
      {% endapply %}

      {# outputs I like foo and bar if the foo parameter equals the foo string. #}

- length:
.. code-block:: twig

{% set myString = "Hello, World!" %}
      {% apply length %}
          {{ myString }}
      {% endapply %}

      {# outputs "14" #}

- lower:
.. code-block:: twig

{% apply  lower %}
    This text becomes lowercase
{% endapply %}

      {# outputs "this text becomes lowercase" #}

- nl2br:
.. code-block:: twig

{% set myText = "Line 1\nLine 2" %}
      {% apply  nl2br %}
          {{ myText }}
      {% endapply %}

      {# outputs "
        Line 1
        Line 2
      "#}

- raw:
.. code-block:: twig

{% apply  raw %}
This text contains <html> tags
{% endapply %}

{# outputs "This text contains tags" #}

- replace:
.. code-block:: twig

{% apply replace({'old': 'new'}) %}
    This text with old becomes new
{% endapply %}

{# outputs "This text with new becomes new" #}

- slice:
.. code-block:: twig

{% set myString = "Hello, World!" %}
{% apply slice(0, 5) %}
    {{ myString }}
{% endapply %}

{# outputs "Hello" #}

- spaceless:
.. code-block:: html+twig

{% apply spaceless %}
    <div>
        <strong>foo</strong>
    </div>
{% endapply %}

{# output '<div><strong>foo</strong></div>' #}

- slug:
.. code-block:: twig

{% set item = 'Wôrķšƥáçè ~~sèťtïñğš~~' %}
{% apply slug %}
    {{ item }}
{% endapply %}

{# output 'Workspace-settings' #}

- slug:
.. code-block:: twig

{% set foo = "<p>one</p>,<br><p>two</p>,<br><p>three</p>" %}
{% apply striptags('<br><p>') %}
    {{ foo }}
{% endapply %}

{# output '&lt;p&gt;one&lt;/p&gt;,&lt;br&gt;&lt;p&gt;two&lt;/p&gt;,&lt;br&gt;&lt;p&gt;three&lt;/p&gt;' #}

- title:
.. code-block:: twig
{% apply title %}
    this is a title
{% endapply %}

{# output "This Is A Title" #}

- trim:
.. code-block:: html+twig
{% apply title %}
    <p>           This text has leading and trailing spaces           </p>
{% endapply %}

{# output "<p>This text has leading and trailing spaces</p>" #}
