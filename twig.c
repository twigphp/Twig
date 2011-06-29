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

#include "Zend/zend_interfaces.h"

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

int TWIG_ARRAY_KEY_EXISTS(zval *array, zval *key)
{
	void *dummy;

	if (Z_TYPE_P(array) != IS_ARRAY) {
		return 0;
	}
	convert_to_string(key);
	if (zend_hash_find(Z_ARRVAL_P(array), Z_STRVAL_P(key), Z_STRLEN_P(key) + 1, &dummy) == SUCCESS) {
		return 1;
	}
	return 0;
}

int TWIG_INSTANCE_OF(zval *object, zend_class_entry *interface TSRMLS_DC)
{
	if (Z_TYPE_P(object) != IS_OBJECT) {
		return 0;
	}
	return instanceof_function(Z_OBJCE_P(object), interface TSRMLS_CC);
}

int TWIG_INSTANCE_OF_USERLAND(zval *object, char *interface TSRMLS_DC)
{
	zend_class_entry **pce;
	if (Z_TYPE_P(object) != IS_OBJECT) {
		return 0;
	}
	if (zend_lookup_class(interface, strlen(interface), &pce TSRMLS_CC) == FAILURE) {
		return 0;
	}
	return instanceof_function(Z_OBJCE_P(object), *pce TSRMLS_CC);
}

int TWIG_ISSET_ARRAY_ELEMENT(zval *array, zval *item)
{
	return 0;
}

zval *TWIG_RETURN_ARRAY_ELEMENT(zval *object, zval *item)
{

}

zval *TWIG_PROPERTY(zval *object, char *propname)
{
}

int TWIG_CALL_BOOLEAN(zval *property, char *functionName)
{

}

char *TWIG_STRTOLOWER_ZVAL(zval *item)
{
	char *item_dup;

	if (Z_TYPE_P(item) != IS_STRING) {
		return NULL;
	}
	item_dup = estrndup(Z_STRVAL_P(item), Z_STRLEN_P(item));
	php_strtolower(item_dup, Z_STRLEN_P(item));
	return item_dup;
}

zval *TWIG_CALL_USER_FUNC_ARRAY(zval *object, char *function, zval *arguments)
{
	zend_fcall_info fci;
	zval ***args;
	HashTable *table = HASH_OF(arguments);
	HashPosition pos;
	int i = 0;
	zval *retval_ptr;
	zval *zfunction;

	args = safe_emalloc(sizeof(zval **), table->nNumOfElements, 0);

	zend_hash_internal_pointer_reset_ex(table, &pos);

	while (zend_hash_get_current_data_ex(table, (void **)&args[i], &pos) == SUCCESS) {
		i++;
		zend_hash_move_forward_ex(table, &pos);
	}

	MAKE_STD_ZVAL(zfunction);
	ZVAL_STRING(zfunction, function, 0);
	fci.size = sizeof(fci);
	fci.function_table = EG(function_table);
	fci.function_name = zfunction;
	fci.symbol_table = NULL;
	fci.object_ptr = object;
	fci.retval_ptr_ptr = &retval_ptr;
	fci.param_count = table->nNumOfElements;
	fci.params = args;
	fci.no_separation = 0;

	if (zend_call_function(&fci, NULL TSRMLS_CC) == FAILURE) {
		zend_throw_exception_ex(zend_exception_get_default(TSRMLS_C), 0 TSRMLS_CC, "Could not execute %s::%s()", zend_get_class_entry(object)->name, function);
	}
	efree(fci.params);
	return retval_ptr;
}

zval *TWIG_GET_STATIC_PROPERTY(zval *class, char *prop_name)
{
	zval **tmp_zval;
	zend_class_entry *ce;

	if (class == NULL || Z_TYPE_P(class) != IS_OBJECT) {
		return NULL;
	}

	ce = zend_get_class_entry(class);
	tmp_zval = zend_std_get_static_property(ce, prop_name, strlen(prop_name), 0 TSRMLS_CC);
	return *tmp_zval;
}

zval *TWIG_GET_ARRAY_ELEMENT_ZVAL(zval *class, zval *prop_name)
{
	zval *tmp_zval;
	char *tmp_name;

	if (class == NULL || Z_TYPE_P(class) != IS_ARRAY || Z_TYPE_P(prop_name) != IS_STRING) {
		return NULL;
	}
	tmp_name = Z_STRVAL_P(prop_name);

	if (zend_hash_find(HASH_OF(class), tmp_name, strlen(tmp_name)+1, &tmp_zval) == SUCCESS) {
		return tmp_zval;
	}
	return NULL;
}

zval *TWIG_GET_ARRAY_ELEMENT(zval *class, char *prop_name)
{
	zval **tmp_zval;

	if (class == NULL || Z_TYPE_P(class) != IS_ARRAY) {
		return NULL;
	}

	if (zend_hash_find(HASH_OF(class), prop_name, strlen(prop_name)+1, (void**)&tmp_zval) == SUCCESS) {
		return *tmp_zval;
	}
	return NULL;
}

int TWIG_CALL_B_0(zval *object, char *method)
{
}

zval *TWIG_CALL_S(zval *object, char *method, char *arg0)
{
}

zval *TWIG_CALL_ZZ(zval *object, char *method, zval *arg1, zval *arg2)
{
}

void TWIG_NEW(zval *object, char *class, zval *value)
{
	zend_class_entry **pce;

	if (zend_lookup_class(class, strlen(class), &pce TSRMLS_CC) == FAILURE) {
		return;
	}

	Z_TYPE_P(object) = IS_OBJECT;
	object_init_ex(object, *pce);
	Z_SET_REFCOUNT_P(object, 1);
	Z_UNSET_ISREF_P(object);
}

static void twig_add_method_to_class(zend_function *mptr TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	zval *retval = va_arg(args, zval*);

	add_next_index_string(retval, mptr->common.function_name, 1);
}

static int twig_add_property_to_class(zend_property_info *pptr TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	zend_class_entry *ce = *va_arg(args, zend_class_entry**);
	zval *retval = va_arg(args, zval*);
	char *class_name, *prop_name;

	if (pptr->flags & ZEND_ACC_SHADOW) {
		return 0;
	}

	zend_unmangle_property_name(pptr->name, pptr->name_length, &class_name, &prop_name);

	add_next_index_string(retval, prop_name, 1);

	return 0;
}

/* {{{ _adddynproperty */
static int twig_add_dyn_property_to_class(zval **pptr TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	zend_class_entry *ce = *va_arg(args, zend_class_entry**);
	zval *retval = va_arg(args, zval*), member;
	char *class_name, *prop_name;

	if (hash_key->arKey[0] == '\0') {
		return 0; /* non public cannot be dynamic */
	}

	ZVAL_STRINGL(&member, hash_key->arKey, hash_key->nKeyLength-1, 0);
	if (zend_get_property_info(ce, &member, 1 TSRMLS_CC) == &EG(std_property_info)) {
		zend_unmangle_property_name((&EG(std_property_info))->name, (&EG(std_property_info))->name_length, &class_name, &prop_name);
		add_next_index_string(retval, prop_name, 1);
	}
	return 0;
}

static void twig_add_class_to_cache(zval *cache, zval *object, char *class_name)
{
	zval *tmp_class, *tmp_properties, *tmp_item, *tmp_object_item = NULL;
	zval *class_info, *class_methods, *class_properties;
	zend_class_entry *class_ce;

	class_ce = zend_get_class_entry(object);

	ALLOC_INIT_ZVAL(class_info);
	ALLOC_INIT_ZVAL(class_methods);
	ALLOC_INIT_ZVAL(class_properties);
	array_init(class_info);
	array_init(class_methods);
	array_init(class_properties);
	// add all methods to self::cache[$class]['methods']
	zend_hash_apply_with_arguments(&class_ce->function_table TSRMLS_CC, (apply_func_args_t) twig_add_method_to_class, 1, class_methods);
	zend_hash_apply_with_arguments(&class_ce->properties_info TSRMLS_CC, (apply_func_args_t) twig_add_property_to_class, 2, &class_ce, class_properties);

	if (object && Z_OBJ_HT_P(object)->get_properties) {
		HashTable *properties = Z_OBJ_HT_P(object)->get_properties(object TSRMLS_CC);
		zend_hash_apply_with_arguments(properties TSRMLS_CC, (apply_func_args_t) twig_add_dyn_property_to_class, 2, &class_ce, class_properties);
	}
	add_assoc_zval(class_info, "methods", class_methods);
	add_assoc_zval(class_info, "properties", class_properties);
	add_assoc_zval(cache, class_name, class_info);
}

/* {{{ proto mixed twig_template_get_attributes(TwigTemplate template, mixed object, mixed item, array arguments, string type, boolean isDefinedTest)
   A C implementation of TwigTemplate::getAttribute() */
PHP_FUNCTION(twig_template_get_attributes)
{
	zval *template;
	zval *object;
	zval *item;
	zval *arguments;
	zval *ret;
	char *type = NULL;
	int   type_len = 0;
	zend_bool isDefinedTest = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ozzasb", &template, &object, &item, &arguments, &type, &type_len, &isDefinedTest) == FAILURE) {
		return;
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
		if ((TWIG_ARRAY_KEY_EXISTS(object, item))
			|| (TWIG_INSTANCE_OF(object, zend_ce_arrayaccess) && TWIG_ISSET_ARRAY_ELEMENT(object, item))
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
			if (!TWIG_CALL_BOOLEAN(TWIG_PROPERTY(template, "env"), "isStrictVariables")) {
				return;
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
		if (!TWIG_CALL_BOOLEAN(TWIG_PROPERTY(template, "env"), "isStrictVariables")) {
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
	char *class_name;
	zend_uint class_name_len;
	zval *tmp_self_cache;
	
	zend_get_object_classname(object, &class_name, &class_name_len TSRMLS_CC);
	tmp_self_cache = TWIG_GET_STATIC_PROPERTY(template, "cache");

	if (!TWIG_GET_ARRAY_ELEMENT(tmp_self_cache, class_name)) {
		twig_add_class_to_cache(tmp_self_cache, object, class_name);
	}

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
		zval *tmp_class, *tmp_properties, *tmp_item, *tmp_object_item = NULL;

		tmp_class = TWIG_GET_ARRAY_ELEMENT(tmp_self_cache, class_name);
		tmp_properties = TWIG_GET_ARRAY_ELEMENT(tmp_class, "properties");
		tmp_item = TWIG_GET_ARRAY_ELEMENT_ZVAL(tmp_properties, item);

		if (tmp_item) {
			convert_to_string(tmp_item);
			tmp_object_item = TWIG_PROPERTY(object, Z_STRVAL_P(tmp_item));
		}

		if (tmp_item || tmp_object_item || TWIG_ARRAY_KEY_EXISTS(object, item) // FIXME: Array key? is that array access here?
		) {
			if (isDefinedTest) {
				RETURN_TRUE;
			}
			if (TWIG_CALL_S(TWIG_PROPERTY(template, "env"), "hasExtension", "sandbox")) {
				TWIG_CALL_ZZ(TWIG_CALL_S(TWIG_PROPERTY(template, "env"), "getExtension", "sandbox"), "checkPropertyAllowed", object, item);
			}

			convert_to_string(item);
			return_value = TWIG_PROPERTY(object, Z_STRVAL_P(item)); // this is obviously wrong
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
	{
		char *lcItem = TWIG_STRTOLOWER_ZVAL(item);
		char *method = NULL;
		char *tmp_method_name_get = emalloc(4 + strlen(lcItem));
		char *tmp_method_name_is =  emalloc(3 + strlen(lcItem));
		zval *tmp_class, *tmp_methods;

		sprintf(tmp_method_name_get, "get%s", lcItem);
		sprintf(tmp_method_name_is, "is%s", lcItem);

		tmp_class = TWIG_GET_ARRAY_ELEMENT(tmp_self_cache, class_name);
		tmp_methods = TWIG_GET_ARRAY_ELEMENT(tmp_class, "methods");

		if (TWIG_PROPERTY(tmp_methods, lcItem)) {
			method = Z_STRVAL_P(item);
		} else if (TWIG_PROPERTY(tmp_methods, tmp_method_name_get)) {
			method = tmp_method_name_get;
		} else if (TWIG_PROPERTY(tmp_methods, tmp_method_name_is)) {
			method = tmp_method_name_is;
		} else if (TWIG_PROPERTY(tmp_methods, "__call")) {
			method = Z_STRVAL_P(item);
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
			zval *env = TWIG_PROPERTY(template, "env");
			if (TWIG_CALL_B_0(env, "isStrictVariables")) {
				return;
			}
			TWIG_THROW_EXCEPTION("Twig_Error_Runtime", "Method \"%s\" for object \"%s\" does not exist", item, TWIG_GET_CLASS(object));
		}
		if (isDefinedTest) {
			efree(tmp_method_name_get);
			efree(tmp_method_name_is);
			efree(lcItem);
			RETURN_FALSE;
		}
/*
	if ($this->env->hasExtension('sandbox')) {
		$this->env->getExtension('sandbox')->checkMethodAllowed($object, $method);
	}
*/
		if (TWIG_CALL_S(TWIG_PROPERTY(template, "env"), "hasExtension", "sandbox")) {
			TWIG_CALL_ZZ(TWIG_CALL_S(TWIG_PROPERTY(template, "env"), "getExtension", "sandbox"), "checkMethodAllowed", object, item);
		}
/*
	$ret = call_user_func_array(array($object, $method), $arguments);
*/
		ret = TWIG_CALL_USER_FUNC_ARRAY(object, method, arguments);
		efree(tmp_method_name_get);
		efree(tmp_method_name_is);
		efree(lcItem);
	}
/*
	if ($object instanceof Twig_TemplateInterface) {
		return new Twig_Markup($ret);
	}
*/
	if (TWIG_INSTANCE_OF_USERLAND(object, "Twig_TemplateInterface")) {
		TWIG_NEW(return_value, "Twig_Markup", ret);
	}
/*
	return $ret;
*/
	if (ret) {
		RETVAL_ZVAL(ret, 0, 1);
	}
}
