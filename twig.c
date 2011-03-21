/*
   +----------------------------------------------------------------------+
   | Twig Extension                                                       |
   +----------------------------------------------------------------------+
   | Copyright (c) 2011 Derick Rethans                                    |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Derick Rethans <derick@derickrethans.nl>                     |
   +----------------------------------------------------------------------+
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_twig.h"

ZEND_BEGIN_ARG_INFO_EX(twig_template_get_attribute_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 7)
	ZEND_ARG_INFO(0, template)
	ZEND_ARG_INFO(0, object)
	ZEND_ARG_INFO(0, item)
	ZEND_ARG_INFO(0, arguments)
	ZEND_ARG_INFO(0, type)
	ZEND_ARG_INFO(0, noStrictCheck)
	ZEND_ARG_INFO(0, line)
ZEND_END_ARG_INFO()

zend_function_entry twig_functions[] = {
	PHP_FE(twig_template_get_attributes, twig_template_get_attribute_args)
	{NULL, NULL, NULL}
};


zend_module_entry twig_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"twig",
	twig_functions,
	PHP_MINIT(twig),
	PHP_MSHUTDOWN(twig),
	PHP_RINIT(twig),	
	PHP_RSHUTDOWN(twig),
	PHP_MINFO(twig),
#if ZEND_MODULE_API_NO >= 20010901
	"0.0.1",
#endif
	STANDARD_MODULE_PROPERTIES
};


#ifdef COMPILE_DL_TWIG
ZEND_GET_MODULE(twig)
#endif

ZEND_DECLARE_MODULE_GLOBALS(twig)

PHP_INI_BEGIN()
PHP_INI_END()
 
static void twig_init_globals(zend_twig_globals *twig_globals)
{
}


PHP_MINIT_FUNCTION(twig)
{
	ZEND_INIT_MODULE_GLOBALS(twig, twig_init_globals, NULL);
	REGISTER_INI_ENTRIES();

	return SUCCESS;
}


PHP_MSHUTDOWN_FUNCTION(twig)
{
	UNREGISTER_INI_ENTRIES();

	return SUCCESS;
}



PHP_RINIT_FUNCTION(twig)
{
	return SUCCESS;
}



PHP_RSHUTDOWN_FUNCTION(twig)
{
	return SUCCESS;
}


PHP_MINFO_FUNCTION(twig)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "twig support", "enabled");
	php_info_print_table_end();

	DISPLAY_INI_ENTRIES();

}

PHP_FUNCTION(twig_template_get_attributes)
{
}
