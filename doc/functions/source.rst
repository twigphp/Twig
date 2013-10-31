``source``
==========

.. versionadded:: 1.15
    The include function was added in Twig 1.15.

The ``source`` function returns the content of a template without rendering it:

.. code-block:: jinja

    {{ source('template.html') }}
    {{ source(some_var) }}

The function uses the same template loaders as the ones used to include
templates. So, if you are using the filesystem loader, the templates are looked
for in the paths defined by it.

Arguments
---------

* ``name``: The name of the template to read
