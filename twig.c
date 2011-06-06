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

ZEND_BEGIN_ARG_INFO_EX(twig_template_get_attribute_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 6)
	ZEND_ARG_INFO(0, template)
	ZEND_ARG_INFO(0, object)
	ZEND_ARG_INFO(0, item)
	ZEND_ARG_INFO(0, arguments)
	ZEND_ARG_INFO(0, type)
	ZEND_ARG_INFO(0, isDefinedTest)
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

/* {{{ proto mixed twig_template_get_attributes(TwigTemplate template, mixed object, mixed item, array arguments, string type, boolean isDefinedTest)
   A C implementation of TwigTemplate::getAttribute() */
PHP_FUNCTION(twig_template_get_attributes)
{
	zval *template;
	zval *object;
	zval *item;
	zval *arguments;
	char *type = NULL;
	int   type_len = 0;
	zend_bool isDefinedTest = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ozzasbl", &template, &object, &item, &arguments, &type, &type_len, &isDefinedTest) == FAILURE) {
		return NULL;
	}

/*
	// array
	if (Twig_TemplateInterface::METHOD_CALL !== $type) {
		if ((is_array($object) && array_key_exists($item, $object))
			|| ($object instanceof ArrayAccess && isset($object[$item]))
		) {
			if ($isDefinedTest) {
				return true;
			}

			return $object[$item];
		}
*/
	if (strcmp("method", type) == 0) {
		if ((Z_TYPE(object) == IS_ARRAY && TWIG_ARRAY_KEY_EXISTS(item, object))
			|| (TWIG_INSTANCE_OF(object, "ArrayAccess") && TWIG_ISSET_ARRAY_ELEMENT(object, item))
		) {
			if (isDefinedTest) {
				RETURN_TRUE;
			}

			TWIG_RETURN_ARRAY_ELEMENT(object, item);
		}
/*
		if (Twig_TemplateInterface::ARRAY_CALL === $type) {
			if ($isDefinedTest) {
				return false;
			}
			if (!$this->env->isStrictVariables()) {
				return null;
			}
*/
		if (strcmp("array", type) == 0) {
			if (isDefinedTest) {
				RETURN_FALSE;
			}
			if (!TWIG_CALL(TWIG_BOOLEAN, TWIG_PROPERTY(getThis(), "env"), "isStrictVariables")) {
				RETURN_NULL;
			}
/*
			if (is_object($object)) {
				throw new Twig_Error_Runtime(sprintf('Key "%s" in object (with ArrayAccess) of type "%s" does not exist', $item, get_class($object)));
			// array
			} else {
				throw new Twig_Error_Runtime(sprintf('Key "%s" for array with keys "%s" does not exist', $item, implode(', ', array_keys($object))));
			}
		}
	}
*/
			if (Z_TYPE_P(object) == IS_OBJECT) {
				TWIG_THROW_EXCEPTION("Twig_Error_Runtime", "Key \"%s\" in object (with ArrayAccess) of type \"%s\" does not exist", item, TWIG_GET_CLASS(object));
			} else {
				TWIG_THROW_EXCEPTION("Twig_Error_Runtime", "Key \"%s\" for array with keys \"%s\" does not exist", item, TWIG_IMPLODE(", ", TWIG_ARRAY_KEYS(object)));
			}
		}
	}

/*
	if (!is_object($object)) {
		if ($isDefinedTest) {
			return false;
		}
*/
	if (!Z_TYPE_P(object) == IS_OBJECT) {
		if (isDefinedTest) {
			RETURN_FALSE;
		}
/*
		if (!$this->env->isStrictVariables()) {
			return null;
		}
		throw new Twig_Error_Runtime(sprintf('Item "%s" for "%s" does not exist', $item, $object));
	}
*/
		if (!TWIG_CALL(TWIG_BOOLEAN, TWIG_PROPERTY(getThis(), "env"), "isStrictVariables")) {
			RETURN_FALSE;
		}
		TWIG_THROW_EXCEPTION("Twig_Error_Runtime", "Item \"%s\" for \"%s\" does not exist", item, object);
	}
/*
	// get some information about the object
	$class = get_class($object);
	if (!isset(self::$cache[$class])) {
		$r = new ReflectionClass($class);
		self::$cache[$class] = array('methods' => array(), 'properties' => array());
		foreach ($r->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			self::$cache[$class]['methods'][strtolower($method->getName())] = true;
		}

		foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
			self::$cache[$class]['properties'][$property->getName()] = true;
		}
	}
*/
/*
	// object property
	if (Twig_TemplateInterface::METHOD_CALL !== $type) {
		if (isset(self::$cache[$class]['properties'][$item])
			|| isset($object->$item) || array_key_exists($item, $object)
		) {
			if ($isDefinedTest) {
				return true;
			}
			if ($this->env->hasExtension('sandbox')) {
				$this->env->getExtension('sandbox')->checkPropertyAllowed($object, $item);
			}

			return $object->$item;
		}
	}
*/
	if (strcmp("method", type) != 0) {
		if (TWIG_ISSET("self::$cache[$class]['properties'][$item]")
			|| TWIG_ISSET(object, item) || TWIG_ARRAY_KEY_EXISTS(item, object)
		) {
			if (isDefinedTest) {
				RETURN_TRUE;
			}
			if (TWIG_CALL(TWIG_PROPERTY(getThis(), "env"), "hasExtension", "sandbox")) {
				TWIG_CALL(TWIG_CALL(TWIG_PROPERTY(getThis(), "env"), "getExtension", "sandbox"), "checkPropertyAllowed", object, item);
			}

			TWIG_RETURN_OBJPROP_ELEMENT(object, item);
		}
	}
/*
	// object method
	$lcItem = strtolower($item);
	if (isset(self::$cache[$class]['methods'][$lcItem])) {
		$method = $item;
	} elseif (isset(self::$cache[$class]['methods']['get'.$lcItem])) {
		$method = 'get'.$item;
	} elseif (isset(self::$cache[$class]['methods']['is'.$lcItem])) {
		$method = 'is'.$item;
	} elseif (isset(self::$cache[$class]['methods']['__call'])) {
		$method = $item;
*/
	char *lsItem = TWIG_STRTOLOWER(item);
	char *method = NULL;
	if (TWIG_ISSET(TWIG_SELF, "cache", "class", "methods", "lcItem")) {
		method = item;
	} else if (TWIG_ISSET(TWIG_SELF, "cache", "class", "methods", "get" lcItem)) {
		method = "get" item;
	} else if (TWIG_ISSET(TWIG_SELF, "cache", "class", "methods", "is" lcItem)) {
		method = "is" item;
	} else if (TWIG_ISSET(TWIG_SELF, "cache", "class", "methods", "__call")) {
		method = item;
/*
	} else {
		if ($isDefinedTest) {
			return false;
		}
		if (!$this->env->isStrictVariables()) {
			return null;
		}
		throw new Twig_Error_Runtime(sprintf('Method "%s" for object "%s" does not exist', $item, get_class($object)));
	}
	if ($isDefinedTest) {
		return true;
	}
*/
	} else {
		if (isDefinedTest) {
			RETURN_FALSE;
		}
		if (TWIG_CALL(TWIG_PROPERTY(getThis(), "env", "isStrictVariables"))) {
			RETURN_NULL;
		}
		TWIG_THROW_EXCEPTION("Twig_Error_Runtime", "Method \"%s\" for object \"%s\" does not exist", item, TWIG_GET_CLASS(object));
	}
	if (isDefinedTest) {
		RETURN_FALSE;
	}
/*
	if ($this->env->hasExtension('sandbox')) {
		$this->env->getExtension('sandbox')->checkMethodAllowed($object, $method);
	}
*/
	if (TWIG_CALL(TWIG_PROPERTY(getThis(), "env"), "hasExtension", "sandbox")) {
		TWIG_CALL(TWIG_CALL(TWIG_PROPERTY(getThis(), "env"), "getExtension", "sandbox"), "checkMethodAllowed", object, method);
	}
/*
	$ret = call_user_func_array(array($object, $method), $arguments);
*/
	zval *ret = TWIG_CALL_USER_FUNC_ARRAY(object, method, arguments);
/*
	if ($object instanceof Twig_TemplateInterface) {
		return new Twig_Markup($ret);
	}
*/
	if (TWIG_INSTANCE_OF(object, "Twig_TemplateInterface")) {
		zval *retval = TWIG_NEW("Twig_Markup", ret);
		TWIG_RETURN(retval);
	}
/*
	return $ret;
*/
	TWIG_REUTNR(ret);
}
