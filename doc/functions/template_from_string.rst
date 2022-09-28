``template_from_string``
========================

The ``template_from_string`` function loads a template from a string:

.. code-block:: twig

    {{ include(template_from_string("Hello {{ name }}")) }}
    {{ include(template_from_string(page.template)) }}

To ease debugging, you can also give the template a name that will be part of
any related error message:

.. code-block:: twig

    {{ include(template_from_string(page.template, "template for page " ~ page.name)) }}

.. note::

    The ``template_from_string`` function is not available by default.

    On Symfony projects, you need to load it in your ``services.yaml`` file:

    .. code-block:: yaml

        services:
            Twig\Extension\StringLoaderExtension:

    or ``services.php`` file::

        $services->set(\Twig\Extension\StringLoaderExtension::class);

    Otherwise, add the extension explicitly on the Twig environment::

        use Twig\Extension\StringLoaderExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new StringLoaderExtension());

.. note::

    Even if you will probably always use the ``template_from_string`` function
    with the ``include`` function, you can use it with any tag or function that
    takes a template as an argument (like the ``embed`` or ``extends`` tags).

Arguments
---------

* ``template``: The template
* ``name``: A name for the template
