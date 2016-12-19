/*
   +----------------------------------------------------------------------+
   | Twig Extension                                                       |
   +----------------------------------------------------------------------+
   | Copyright (c) 2016 Sensiolabs                                        |
   +----------------------------------------------------------------------+
   | Redistribution and use in source and binary forms, with or without   |
   | modification, are permitted provided that the conditions mentioned   |
   | in the accompanying LICENSE file are met (BSD-3-Clause).             |
   +----------------------------------------------------------------------+
   | Author: Julien PAULI <jpauli@php.net>                                |
   +----------------------------------------------------------------------+
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "zend.h"
#include "php_twig.h"
#include "spprintf.h"
#include "ext/standard/php_var.h"
#include "ext/standard/php_string.h"
#include "ext/standard/php_smart_string.h"
#include "ext/spl/spl_exceptions.h"

#include "Zend/zend_object_handlers.h"
#include "Zend/zend_interfaces.h"
#include "Zend/zend_exceptions.h"
#include "Zend/zend_operators.h"

ZEND_BEGIN_ARG_INFO_EX(twig_get_attribute_arginfo, NULL, ZEND_RETURN_VALUE, 4)
	ZEND_ARG_OBJ_INFO(0, env, Twig_Environment, 0)
	ZEND_ARG_OBJ_INFO(0, source, Twig_Source, 0)
	ZEND_ARG_INFO(0, object)
	ZEND_ARG_INFO(0, item)
	ZEND_ARG_ARRAY_INFO(0, arguments, 1)
	ZEND_ARG_INFO(0, type)
	ZEND_ARG_INFO(0, isDefinedTest)
	ZEND_ARG_INFO(0, ignoreStrictCheck)
ZEND_END_ARG_INFO()

static zend_string *twig_environment_str, *twig_source_str, *twig_error_runtime_str, *twig_template_str, *__call_str, *twig_extension_sandbox_str;
static zval nil = {0};

static zend_function_entry twig_functions[] = {
	PHP_FE(twig_get_attribute, twig_get_attribute_arginfo)
	PHP_FE_END
};

PHP_MINIT_FUNCTION(twig)
{
	twig_environment_str       = zend_string_init("Twig_Environment", strlen("Twig_Environment"), 1);
	twig_source_str            = zend_string_init("Twig_Source", strlen("Twig_Source"), 1);
	twig_error_runtime_str     = zend_string_init("Twig_Error_Runtime", strlen("Twig_Error_Runtime"), 1);
	twig_template_str          = zend_string_init("Twig_Template", strlen("Twig_Template"), 1);
	__call_str                 = zend_string_init("__call", strlen("__call"), 1);
	twig_extension_sandbox_str = zend_string_init("Twig_Extension_Sandbox", strlen("Twig_Extension_Sandbox"), 1);

	return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(twig)
{
	zend_string_release(twig_environment_str);
	zend_string_release(twig_source_str);
	zend_string_release(twig_error_runtime_str);
	zend_string_release(twig_template_str);
	zend_string_release(__call_str);
	zend_string_release(twig_extension_sandbox_str);

	return SUCCESS;
}

PHP_RSHUTDOWN_FUNCTION(twig)
{
	ZEND_SECURE_ZERO(&TWIG_G(r), sizeof(twig_request_globals));

	return SUCCESS;
}

PHP_GINIT_FUNCTION(twig)
{
	ZEND_SECURE_ZERO(twig_globals, sizeof(zend_twig_globals));
}

zend_module_entry twig_module_entry = {
	STANDARD_MODULE_HEADER,
	"twig",
	twig_functions,
	PHP_MINIT(twig),
	PHP_MSHUTDOWN(twig),
	NULL,
	PHP_RSHUTDOWN(twig),
	NULL,
	PHP_TWIG_VERSION,
	PHP_MODULE_GLOBALS(twig),
	PHP_GINIT(twig),
	NULL,
	NULL,
	STANDARD_MODULE_PROPERTIES_EX
};

#ifdef COMPILE_DL_TWIG
ZEND_GET_MODULE(twig)
#endif

/****************************************
 *                                      *
 *        End of Zend stuff             *
 *                                      *
 ****************************************/

#define TWIG_IS_STRICT_VARIABLES twig_call_boolean(env, "isStrictVariables")
#define TWIG_HAS_EXTENSION twig_has_extension(env)

#define CHECK_TH_CLASSES do { \
	if (UNEXPECTED(TWIG_G_R(twig_environment_ce) == NULL)) { \
		TWIG_G_R(twig_environment_ce) = zend_lookup_class(twig_environment_str); \
		if (UNEXPECTED(TWIG_G_R(twig_environment_ce) == NULL)) { \
			zend_error_noreturn(E_ERROR, "Class %s not found", ZSTR_VAL(twig_environment_str)); \
		} \
		TWIG_G_R(twig_source_ce) = zend_lookup_class(twig_source_str); \
		if (UNEXPECTED(TWIG_G_R(twig_source_ce) == NULL)) { \
			zend_error_noreturn(E_ERROR, "Class %s not found", ZSTR_VAL(twig_source_str)); \
		} \
		TWIG_G_R(twig_error_runtime_ce) = zend_lookup_class(twig_error_runtime_str); \
			if (UNEXPECTED(TWIG_G_R(twig_error_runtime_ce) == NULL)) { \
			zend_error_noreturn(E_ERROR, "Class %s not found", ZSTR_VAL(twig_error_runtime_str)); \
		} \
		TWIG_G_R(twig_template_ce) = zend_lookup_class(twig_template_str); \
			if (UNEXPECTED(TWIG_G_R(twig_template_ce) == NULL)) { \
			zend_error_noreturn(E_ERROR, "Class %s not found", ZSTR_VAL(twig_template_str)); \
		} \
	} \
} while (0);

static int twig_array_key_exists(zval *array, zval *key)
{
	if (Z_TYPE_P(array) != IS_ARRAY) {
		return 0;
	}

	switch (Z_TYPE_P(key)) {
		case IS_NULL:
			return zend_hash_exists(Z_ARRVAL_P(array), CG(empty_string));
		case IS_TRUE:
			return zend_hash_index_exists(Z_ARRVAL_P(array), 1);
		case IS_FALSE:
			return zend_hash_index_exists(Z_ARRVAL_P(array), 0);
		case IS_LONG:
			return zend_hash_index_exists(Z_ARRVAL_P(array), Z_LVAL_P(key));
		case IS_DOUBLE:
			return zend_hash_index_exists(Z_ARRVAL_P(array), zend_dval_to_lval(Z_DVAL_P(key)));
		case IS_STRING:
			return zend_symtable_exists(Z_ARRVAL_P(array), Z_STR_P(key));
		default:
			return 0;
	}
}

static zval *twig_get_arrayobject_element(zval *object, zval *offset)
{
	if ((Z_TYPE_P(object) == IS_OBJECT) && instanceof_function(Z_OBJCE_P(object), zend_ce_arrayaccess)) {
		return Z_OBJ_HANDLER_P(object, read_dimension)(object, offset, 0, &nil);
	}
	return NULL;
}

static int twig_isset_arrayobject_element(zval *object, zval *offset)
{
	if (Z_TYPE_P(object) == IS_OBJECT && instanceof_function(Z_OBJCE_P(object), zend_ce_arrayaccess)) {
		return Z_OBJ_HANDLER_P(object, has_dimension)(object, offset, 0);
	}
	return 0;
}

static zval twig_call_user_func_array(zval *object, char *function, zval *arguments)
{
	zend_fcall_info fci = {0};
	zval retval;

	zval zfunction;

	zend_fcall_info_args(&fci, arguments);

	ZVAL_STRING(&zfunction, function);

	fci.size           = sizeof(fci);
#if IS_PHP_70
	fci.function_table = EG(function_table);
	fci.symbol_table   = NULL;
#endif
	fci.function_name  = zfunction;
	fci.object         = Z_OBJ_P(object);
	fci.retval         = &retval;
	fci.no_separation  = 0;

	if (zend_call_function(&fci, NULL) == FAILURE) {
		ZVAL_FALSE(&retval);
	}

	if (fci.params) {
		efree(fci.params);
	}

	zval_ptr_dtor(&zfunction);

	return retval;
}

static int twig_call_boolean(zval *object, char *functionName)
{
	zval ret;

	ret = twig_call_user_func_array(object, functionName, NULL);

	return i_zend_is_true(&ret);
}

static zval *twig_get_array_element_zval(zval *class, zval *prop_name)
{
	if (Z_TYPE_P(class) == IS_ARRAY) {
		switch(Z_TYPE_P(prop_name)) {
			case IS_NULL:
				return zend_hash_find(HASH_OF(class), CG(empty_string));
			case IS_TRUE:
				return zend_hash_index_find(HASH_OF(class), 1);
			case IS_FALSE:
				return zend_hash_index_find(HASH_OF(class), 0);
			case IS_DOUBLE:
				return zend_hash_index_find(HASH_OF(class), zend_dval_to_lval(Z_DVAL_P(prop_name)));
			case IS_LONG:
				return zend_hash_index_find(HASH_OF(class), Z_LVAL_P(prop_name));
			case IS_STRING:
				return zend_symtable_find(HASH_OF(class), Z_STR_P(prop_name));
			default:
				return NULL;
		}
	}

	return twig_get_arrayobject_element(class, prop_name);
}

static zval *twig_read_property(zval *object, zval *propname)
{
	zval *tmp = NULL;
	zend_class_entry *cur_scope = zend_get_executed_scope();

	if (Z_OBJ_HT_P(object)->read_property) {
		if (!cur_scope) {
			EXECUTOR_SCOPE = Z_OBJCE_P(object);
		}
		tmp = Z_OBJ_HT_P(object)->read_property(object, propname, BP_VAR_IS, NULL, &nil);
		if (!cur_scope) {
			EXECUTOR_SCOPE = NULL;
		}
	}
	return tmp;
}

static int twig_has_property(zval *object, zval *propname, zend_string *prop)
{
	int retval = 0;

	if (Z_OBJ_HT_P(object)->has_property) {
		retval = Z_OBJ_HT_P(object)->has_property(object, propname, 0, NULL);
	}
	if ((retval == 0) && (Z_OBJ_HT_P(object)->get_properties)) {
		return zend_hash_exists( Z_OBJ_HT_P(object)->get_properties(object), prop);
	}

	return retval;
}

static int twig_call_method(zval *object, char *method /*lowercased */, zval *result, zval *arg1, zval *arg2)
{
	int success;
	zval retval;

	if (arg2) {
		zend_call_method(object, Z_OBJCE_P(object), NULL, method, strlen(method), &retval, 2, arg1, arg2);
	} else {
		zend_call_method(object, Z_OBJCE_P(object), NULL, method, strlen(method), &retval, 1, arg1, NULL);
	}

	success = Z_TYPE(retval) == IS_TRUE;

	if (result) {
		ZVAL_COPY(result, &retval);
	}

	zval_ptr_dtor(&retval);

	return success;
}

static int twig_has_extension(zval *obj)
{
	zval str;
	int ret;

	ZVAL_STR_COPY(&str, twig_extension_sandbox_str);

	ret = twig_call_method(obj, "hasextension", NULL, &str, NULL);

	zval_ptr_dtor(&str);

	return ret;
}

static int twig_add_array_key_to_string(zval *pDest, int num_args, va_list args, zend_hash_key *hash_key)
{
	smart_string *buf = va_arg(args, smart_string*);

	if (buf->len != 0) {
		smart_string_appends(buf, ", ");
	}

	if (!hash_key->key) {
		smart_string_append_long(buf, hash_key->h);
	} else {
		zend_string *key, *tmp_str;
		key     = php_addcslashes(hash_key->key, 0, "'\\", 2);
		tmp_str = php_str_to_str(ZSTR_VAL(key), ZSTR_LEN(key), "\0", 1, "' . \"\\0\" . '", 12);

		smart_string_appendl(buf, ZSTR_VAL(tmp_str), ZSTR_LEN(tmp_str));
		zend_string_release(key);
		zend_string_release(tmp_str);
	}

	return ZEND_HASH_APPLY_KEEP;
}

static char *twig_implode_array_keys(zval *array)
{
	smart_string collector = { 0, 0, 0 };

	zend_hash_apply_with_arguments(HASH_OF(array), twig_add_array_key_to_string, 1, &collector);
	smart_string_0(&collector);

	return collector.c;
}

static void twig_runtime_error(zval *source, char *message, ...)
{
	zend_string *buffer;
	va_list args;
	zend_fcall_info_cache fcic = {0};
	zend_fcall_info fci        = {0};
	zval constructor_retval, ex, constructor_args[3];

	va_start(args, message);
	buffer = vstrpprintf(0, message, args);
	ZEND_ASSERT(buffer);
	va_end(args);

	object_init_ex(&ex, TWIG_G_R(twig_error_runtime_ce));

	ZVAL_STR(&constructor_args[0], buffer);
	ZVAL_LONG(&constructor_args[1], -1);

	fci.size           = sizeof(fci);
	fci.params         = constructor_args;
	fci.param_count    = 3;
	fci.retval         = &constructor_retval;

	fcic.called_scope     = TWIG_G_R(twig_error_runtime_ce);
	fcic.calling_scope    = TWIG_G_R(twig_error_runtime_ce);
	fcic.function_handler = TWIG_G_R(twig_error_runtime_ce)->constructor;
	fcic.object           = Z_OBJ(ex);
	fcic.initialized      = 1;

	ZVAL_COPY_VALUE(&constructor_args[2], source);

	zend_call_function(&fci, &fcic);

	zval_ptr_dtor(&constructor_args[1]);
	zval_ptr_dtor(&constructor_retval);

	Z_ADDREF(ex);
	zend_throw_exception_object(&ex);
}

/* {{{ proto mixed twig_get_attribute(Twig_Environment env, Twig_Source source, mixed object, mixed item, array arguments, string type, boolean isDefinedTest, boolean ignoreStrictCheck)
   A C implementation of TwigTemplate::getAttribute() */
PHP_FUNCTION(twig_get_attribute)
{
	zval *object, *zitem, *env, *source, *ret = NULL, *arguments = NULL;
	zval r = {0};

	zend_string *item_str;
	char   *type     = "any";
	size_t  type_len = 0;

	zend_bool isDefinedTest     = 0;
	zend_bool ignoreStrictCheck = 0;

	zend_class_entry *object_class = NULL;

	CHECK_TH_CLASSES

	ZEND_PARSE_PARAMETERS_START(4, 8)
		Z_PARAM_OBJECT_OF_CLASS(env, TWIG_G_R(twig_environment_ce))
		Z_PARAM_OBJECT_OF_CLASS(source, TWIG_G_R(twig_source_ce))
		Z_PARAM_ZVAL(object)
		Z_PARAM_ZVAL(zitem)
		Z_PARAM_OPTIONAL
		Z_PARAM_ARRAY(arguments)
		Z_PARAM_STRING(type, type_len)
		Z_PARAM_BOOL(isDefinedTest)
		Z_PARAM_BOOL(ignoreStrictCheck)
	ZEND_PARSE_PARAMETERS_END();

	item_str = zval_get_string(zitem);
/*
	// array
	if (Twig_Template::METHOD_CALL !== $type) {
		$arrayItem = is_bool($item) || is_float($item) ? (int) $item : $item;

		if ((is_array($object) && array_key_exists($arrayItem, $object))
			|| ($object instanceof ArrayAccess && isset($object[$arrayItem]))
		) {
			if ($isDefinedTest) {
				return true;
			}

			return $object[$arrayItem];
		}
*/

	if (strcmp("method", type) != 0) {
		if (Z_TYPE_P(zitem) == IS_DOUBLE || ZEND_SAME_FAKE_TYPE(_IS_BOOL, Z_TYPE_P(zitem)) ) {
			convert_to_long(zitem);
		}

		if (twig_array_key_exists(object, zitem) || twig_isset_arrayobject_element(object, zitem)) {
			if (isDefinedTest) {
				zend_string_release(item_str);
				RETURN_TRUE;
			}

			ret = twig_get_array_element_zval(object, zitem);

			if (!ret) {
				ret = &EG(uninitialized_zval);
			}

			zend_string_release(item_str);
			RETURN_ZVAL(ret, 1, 0);
		}
/*
		if (Twig_Template::ARRAY_CALL === $type) {
			if ($isDefinedTest) {
				return false;
			}
			if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
				return null;
			}
*/
		if (strcmp("array", type) == 0 || Z_TYPE_P(object) != IS_OBJECT) {
			if (isDefinedTest) {
				zend_string_release(item_str);
				RETURN_FALSE;
			}
			if (ignoreStrictCheck || !TWIG_IS_STRICT_VARIABLES) {
				zend_string_release(item_str);
				return;
			}
/*
			if ($object instanceof ArrayAccess) {
				$message = sprintf('Key "%s" in object with ArrayAccess of class "%s" does not exist.', $arrayItem, get_class($object));
			} elseif (is_object($object)) {
				$message = sprintf('Impossible to access a key "%s" on an object of class "%s" that does not implement ArrayAccess interface.', $item, get_class($object));
			} elseif (is_array($object)) {
				if (empty($object)) {
					$message = sprintf('Key "%s" does not exist as the array is empty.', $arrayItem);
				} else {
					$message = sprintf('Key "%s" for array with keys "%s" does not exist.', $arrayItem, implode(', ', array_keys($object)));
				}
			} elseif (self::ARRAY_CALL === $type) {
				if (null === $object) {
					$message = sprintf('Impossible to access a key ("%s") on a null variable.', $item);
				} else {
					$message = sprintf('Impossible to access a key ("%s") on a %s variable ("%s").', $item, gettype($object), $object);
				}
			} elseif (null === $object) {
				$message = sprintf('Impossible to access an attribute ("%s") on a null variable.', $item);
			} else {
				$message = sprintf('Impossible to access an attribute ("%s") on a %s variable ("%s").', $item, gettype($object), $object);
			}

			throw new Twig_Error_Runtime($message, -1, $this->getSourceContext());
		}
	}
*/
			if (Z_TYPE_P(object) == IS_OBJECT && instanceof_function(Z_OBJCE_P(object), zend_ce_arrayaccess)) {
				twig_runtime_error(source, "Key \"%s\" in object with ArrayAccess of class \"%s\" does not exist.", ZSTR_VAL(item_str), ZSTR_VAL(Z_OBJCE_P(object)->name));
			} else if (Z_TYPE_P(object) == IS_OBJECT) {
				twig_runtime_error(source, "Impossible to access a key \"%s\" on an object of class \"%s\" that does not implement ArrayAccess interface.", ZSTR_VAL(item_str), ZSTR_VAL(Z_OBJCE_P(object)->name));
			} else if (Z_TYPE_P(object) == IS_ARRAY) {
				if (0 == zend_hash_num_elements(Z_ARRVAL_P(object))) {
					twig_runtime_error(source, "Key \"%s\" does not exist as the array is empty.", ZSTR_VAL(item_str));
				} else {
					char *keys = twig_implode_array_keys(object);
					twig_runtime_error(source, "Key \"%s\" for array with keys \"%s\" does not exist.", ZSTR_VAL(item_str), keys);
					efree(keys);
				}
			} else if (strcmp("array", type) == 0) {
				if (Z_TYPE_P(object) == IS_NULL) {
					twig_runtime_error(source, "Impossible to access a key (\"%s\") on a null variable.", ZSTR_VAL(item_str));
				} else {
					zend_string *object_str = zval_get_string(object);
					twig_runtime_error(source, "Impossible to access a key (\"%s\") on a %s variable (\"%s\").", ZSTR_VAL(item_str), zend_zval_type_name(object), ZSTR_VAL(object_str));
					zend_string_release(object_str);
				}
			} else if (Z_TYPE_P(object) == IS_NULL) {
				twig_runtime_error(source, "Impossible to access an attribute (\"%s\") on a null variable.", ZSTR_VAL(item_str));
			} else {
				zend_string *object_str = zval_get_string(object);
				twig_runtime_error(source, "Impossible to access an attribute (\"%s\") on a %s variable (\"%s\").", ZSTR_VAL(item_str), zend_zval_type_name(object), ZSTR_VAL(object_str));
				zend_string_release(object_str);
			}

			zend_string_release(item_str);
			return;
		}
	}

/*
	if (!is_object($object)) {
		if ($isDefinedTest) {
			return false;
		}
*/

	if (Z_TYPE_P(object) != IS_OBJECT) {
		if (isDefinedTest) {
			zend_string_release(item_str);
			RETURN_FALSE;
		}
/*
		if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
			return null;
		}
		throw new Twig_Error_Runtime(sprintf('Impossible to invoke a method ("%s") on a %s variable ("%s")', $item, gettype($object), $object), -1, $this->getTemplateName());
	}
*/
		if (ignoreStrictCheck || !TWIG_IS_STRICT_VARIABLES) {
			zend_string_release(item_str);
			return;
		}

		if (Z_TYPE_P(object) == IS_NULL) {
			twig_runtime_error(source, "Impossible to invoke a method (\"%s\") on a null variable.", ZSTR_VAL(item_str));
		} else {
			zend_string *object_str = zval_get_string(object);
			twig_runtime_error(source, "Impossible to invoke a method (\"%s\") on a %s variable (\"%s\").", ZSTR_VAL(item_str), zend_zval_type_name(object), ZSTR_VAL(object_str));
			zend_string_release(object_str);
		}
		zend_string_release(item_str);
		return;
	}

	ZEND_ASSERT(Z_TYPE_P(object) == IS_OBJECT);

	object_class = Z_OBJCE_P(object);

/*
	// object property
	if (Twig_Template::METHOD_CALL !== $type && !$object instanceof self) {
		if (isset($object->$item) || array_key_exists((string) $item, $object)) {
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
	if (strcmp("method", type) != 0 && !instanceof_function(object_class, TWIG_G_R(twig_template_ce))) {
		if (twig_has_property(object, zitem, item_str)) {
			if (isDefinedTest) {
				zend_string_release(item_str);
				RETURN_TRUE;
			}
			if (TWIG_HAS_EXTENSION) {
				zval sandbox, result;
				ZVAL_STR(&sandbox, twig_extension_sandbox_str);

				twig_call_method(env, "getextension", &result, &sandbox, NULL);
				twig_call_method(&result, "checkpropertyallowed", NULL, object, zitem);

				zval_ptr_dtor(&result);
			}
			if (EG(exception)) {
				zend_string_release(item_str);
				return;
			}

			ret = twig_read_property(object, zitem);

			zend_string_release(item_str);

			ZEND_ASSERT(ret);
			ZEND_ASSERT(Z_TYPE_P(ret) != IS_UNDEF);

			RETURN_ZVAL(ret, 1, 0);
		}
	}
/*
	// object method
	if (!isset(self::$cache[$class]['methods'])) {
		self::$cache[$class]['methods'] = array_change_key_case(array_flip(get_class_methods($object)));
	}

	$call = false;
	$lcItem = strtolower($item);
	if (isset(self::$cache[$class]['methods'][$lcItem])) {
		$method = (string) $item;
	} elseif (isset(self::$cache[$class]['methods']['get'.$lcItem])) {
		$method = 'get'.$item;
	} elseif (isset(self::$cache[$class]['methods']['is'.$lcItem])) {
		$method = 'is'.$item;
	} elseif (isset(self::$cache[$class]['methods']['__call'])) {
		$method = (string) $item;
		$call = true;
*/
	{
		zend_string *lcItem = zend_string_tolower(item_str);
		zend_string *method = NULL, *getter, *isser, *haser;
		zval *zend_method;

		getter = strpprintf(0, "get%s", ZSTR_VAL(lcItem));
		isser  = strpprintf(0, "is%s", ZSTR_VAL(lcItem));
		haser  = strpprintf(0, "has%s", ZSTR_VAL(lcItem));

		if ((zend_method = zend_hash_find(&object_class->function_table, lcItem)) && (Z_FUNC_P(zend_method)->common.fn_flags & ZEND_ACC_PUBLIC)) {
			method = item_str;
		} else if ((zend_method = zend_hash_find(&object_class->function_table, getter)) && (Z_FUNC_P(zend_method)->common.fn_flags & ZEND_ACC_PUBLIC)) {
			method = getter;
		} else if ((zend_method = zend_hash_find(&object_class->function_table, isser)) && (Z_FUNC_P(zend_method)->common.fn_flags & ZEND_ACC_PUBLIC)) {
			method = isser;
		} else if ((zend_method = zend_hash_find(&object_class->function_table, haser)) && (Z_FUNC_P(zend_method)->common.fn_flags & ZEND_ACC_PUBLIC)) {
			method = haser;
		} else if ((zend_method = zend_hash_find(&object_class->function_table, __call_str))) {
			method = item_str;
/*
	} else {
		if ($isDefinedTest) {
			return false;
		}

		if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
			return;
		}

		 throw new Twig_Error_Runtime(sprintf('Neither the property "%1$s" nor one of the methods "%1$s()", "get%1$s()"/"is%1$s()" or "__call()" exist and have public access in class "%2$s".', $item, $class), -1, $this->getSourceContext());
	}

	if ($isDefinedTest) {
		return true;
	}
*/
		} else {
			zend_string_release(getter);
			zend_string_release(isser);
			zend_string_release(haser);
			zend_string_release(lcItem);

			if (isDefinedTest) {
				zend_string_release(item_str);
				RETURN_FALSE;
			}
			if (ignoreStrictCheck || !TWIG_IS_STRICT_VARIABLES) {
				zend_string_release(item_str);
				return;
			}
			twig_runtime_error(source, "Neither the property \"%s\" nor one of the methods \"%s()\", \"get%s()\"/\"is%s()\"/\"has%s()\" or \"__call()\" exist and have public access in class \"%s\".", ZSTR_VAL(item_str), ZSTR_VAL(item_str), ZSTR_VAL(item_str), ZSTR_VAL(item_str), ZSTR_VAL(item_str), ZSTR_VAL(Z_OBJCE_P(object)->name));
			zend_string_release(item_str);
			return;
		}

		zend_string_release(lcItem);

		if (isDefinedTest) {
			zend_string_release(isser);
			zend_string_release(haser);
			zend_string_release(getter);
			zend_string_release(item_str);
			RETURN_TRUE;
		}
/*
	if ($this->env->hasExtension('sandbox')) {
		$this->env->getExtension('sandbox')->checkMethodAllowed($object, $method);
	}
*/
		if (TWIG_HAS_EXTENSION) {
			zval sandbox, result, zmethod;

			ZVAL_STR(&sandbox, twig_extension_sandbox_str);
			ZVAL_STR(&zmethod, method);

			twig_call_method(env, "getextension", &result, &sandbox, NULL);
			twig_call_method(&result, "checkmethodallowed", NULL, object, &zmethod);

			zval_ptr_dtor(&result);
		}

		if (EG(exception)) {
			zend_string_release(isser);
			zend_string_release(getter);
			zend_string_release(haser);
			zend_string_release(item_str);
			return;
		}
/*
	// Some objects throw exceptions when they have __call, and the method we try
	// to call is not supported. If ignoreStrictCheck is true, we should return null.
	try {
	    $ret = $object->$method(...$arguments);
	} catch (BadMethodCallException $e) {
	    if ($call && ($ignoreStrictCheck || !$this->env->isStrictVariables())) {
	        return;
	    }
	    throw $e;
	}
*/
		r   = twig_call_user_func_array(object, ZSTR_VAL(method), arguments);
		ret = &r;
		zend_string_release(isser);
		zend_string_release(getter);
		zend_string_release(haser);

		if (EG(exception) && instanceof_function(EG(exception)->ce, spl_ce_BadMethodCallException)) {
			if (ignoreStrictCheck || !TWIG_IS_STRICT_VARIABLES) {
				zend_clear_exception();
			}
			zend_string_release(item_str);
			return;
		}
	}

	// return $ret;

	ZEND_ASSERT(ret);

	if (Z_TYPE_P(ret) != IS_UNDEF) {
		RETVAL_ZVAL(ret, 1, 0);
	}

	zend_string_release(item_str);
}
