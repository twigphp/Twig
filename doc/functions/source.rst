``source``
==========

The ``source`` function returns the content of a resource without rendering it.
The resource can be a Twig template or any other file exposed by the configured
template loader:

.. code-block:: twig

    {{ source('template.html.twig') }}
    {{ source(some_var) }}

The function uses the same template loaders as the ones used to include
templates. With the filesystem loader, it can read any file under the configured
loader paths, even when the file does not contain Twig syntax.

.. warning::

    The return value is considered safe and is not escaped automatically. Use
    the ``escape`` filter explicitly when the returned content should be
    escaped:

    .. code-block:: twig

        {{ source('message.txt')|escape }}

    Only pass trusted resource names to ``source()`` and configure loader paths
    to contain no secrets or untrusted files. In sandboxed templates, allow the
    ``source`` function only when every loader-accessible resource is safe for
    template authors to read.

When you set the ``ignore_missing`` flag, Twig will return an empty string if
the resource does not exist:

.. code-block:: twig

    {{ source('template.html.twig', ignore_missing = true) }}

Arguments
---------

* ``name``: The name of the resource to read
* ``ignore_missing``: Whether to ignore missing resources or not
