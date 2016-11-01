<?php
/**
* The Inflector provides a number of string conversion methods.
*
* @package Buan
*/
namespace Buan;
class Inflector {

	/*-------------------------------------------------------- GENERIC CONVERSIONS
	# These methods take any string and convert to a format.
	*/
	static public function toLowerHyphenated($input) {
		return str_replace(" ", "-", preg_replace("/[^a-z0-9\-]+/i", " ", strtolower($input)));
	}

	/*------------------------------------------------------- SPECIFIC CONVERSIONS
	# These methods are generic conversions that are used by the more specific
	# methods described further below.
	*/

	/**
	* Conversion: stringInputParam > StringInputParam
	*
	* @param string String in lowerCamelCaps format
	* @return string
	*/
	static public function lowerCamelCaps_upperCamelCaps($input) {

		// Result
		return ucfirst($input);
	}

	/**
	* Conversion: StringInputParam > stringInputParam
	*
	* @param string String in UpperCamelCaps format
	* @return string
	*/
	static public function upperCamelCaps_lowerCamelCaps($input) {

		// Result
		$input[0] = strtolower($input[0]);
		return $input;
	}

	/**
	* Conversion: StringInputParam > string_input_param
	*
	* @param string String in UpperCamelCaps format
	* @return string
	*/
	static public function upperCamelCaps_lowerUnderscored($input) {

		// Convert and return
		return strtolower(preg_replace("/([a-z0-9])([A-Z])/", "$1_$2", $input));
	}

	/**
	* Conversion: string_input_param > StringInputParam
	*
	* @param string String in lower_underscored format
	* @return string
	*/
	static public function lowerUnderscored_upperCamelCaps($input) {

		// Conversion cache
		static $convCache = array();

		// Convert, cache and return
		return isset($convCache[$input]) ? $convCache[$input] : $convCache[$input] = str_replace(' ', '', ucwords(str_replace('_', ' ', $input)));
	}

	/**
	* Conversion: stringInputParam > string_input_param
	*
	* @param string String in lowerCamelCaps format
	* @return string
	*/
	static public function lowerCamelCaps_lowerUnderscored($input) {

		// Convert and return
		return strtolower(preg_replace("/([a-z0-9])([A-Z])/", "$1_$2", $input));
	}

	/**
	* Conversion: string_input_param > stringInputParam
	*
	* @param string String in lower_underscored format
	* @return string
	*/
	static public function lowerUnderscored_lowerCamelCaps($input) {

		// Convert, cache and return
		$output = str_replace(' ', '', ucwords(str_replace('_', ' ', $input)));
		$output[0] = strtolower($output[0]);
		return $output;
	}

	/**
	* Conversion: string-input-param > StringInputParam
	*
	* @param string String in lower-hyphenated format
	* @return string
	*/
	static public function lowerHyphenated_upperCamelCaps($input) {

		// Convert, cache and return
		return str_replace(' ', '', ucwords(str_replace('-', ' ', $input)));
	}

	/**
	* Conversion: string-input-param > stringInputParam
	*
	* @param string String in lower-hyphenated format
	* @return string
	*/
	static public function lowerHyphenated_lowerCamelCaps($input) {

		// Convert, cache and return
		$output = str_replace(' ', '', ucwords(str_replace('-', ' ', $input)));
		$output[0] = strtolower($output[0]);
		return $output;
	}

	/**
	* Conversion: StringInputParam > string-input-param
	*
	* @param string String in UpperCamelCaps format
	* @return string
	*/
	static public function upperCamelCaps_lowerHyphenated($input) {

		// Convert and return
		return strtolower(preg_replace("/([a-z0-9])([A-Z])/", "$1-$2", $input));
	}

	/**
	* Conversion: stringInputParam > string-input-param
	*
	* @param string String in lowerCamelCaps format
	* @return string
	*/
	static public function lowerCamelCaps_lowerHyphenated($input) {

		// Convert and return
		return strtolower(preg_replace("/([a-z0-9])([A-Z])/", "$1-$2", $input));
	}

	/*----------------------------------------------------------- SPECIFIC METHODS
	# These methods are used for specific conversions.
	*/

	/*------------------------------------------------------------------- MODEL */

	/**
	* Returns the class name used by the given Model.
	* Conversion: StringInputParam > StringInputParamModel
	*
	* @param string Model name in UpperCamelCaps format
	* @return string
	*/
	static public function modelName_modelClass($modelName) {

		// Result
		return $modelName.'Model';
	}

	/**
	* Returns the class name of the ModelManager used by the given Model.
	* Conversion: StringInputParam > StringInputParamManager
	*
	* @param string Model name in UpperCamelCaps format
	* @return string
	*/
	static public function modelName_modelManagerClass($modelName) {

		// Result
		return $modelName.'Manager';
	}

	/**
	* Returns the database table name used by the given Model.
	* Conversion: StringInputParam > string_input_param
	*
	* @param string Model name in UpperCamelCaps format
	* @return string
	*/
	static public function modelName_dbTableName($modelName) {

		// Result
		return self::upperCamelCaps_lowerUnderscored($modelName);
	}

	/**
	* Returns the name of the class method used to get/set values for the given
	* Model field name.
	* Conversion: string_input_param > StringInputParam
	*
	* @param string Model field name in lower_underscored format
	* @return string
	*/
	static public function modelField_classMethod($fieldName) {

		// Conversion cache
		static $convCache = array();
		if(isset($convCache[$fieldName])) return $convCache[$fieldName];

		// Result
		return $convCache[$fieldName] = self::lowerUnderscored_upperCamelCaps($fieldName);
	}

	/**
	* Returns the name of the database table field that is altered by the given
	* class method.
	* Conversion: StringInputParam > stringInputParam
	*
	* @param string Class method in UpperCamelCaps format
	* @return string
	*/
	static public function classMethod_modelField($classMethod) {

		// Result
		return strtolower($classMethod[0]).substr($classMethod, 1);
	}

	/*-------------------------------------------------------------- CONTROLLER */

	/**
	* Returns the name of the class represented by the given URL command argument.
	* Conversion: string-input-param > StringInputParamController
	*
	* @param string Controller as used in a URL command in lower-hyphenated format
	* @return string
	*/
	static public function controllerCommand_controllerClass($controllerCommand) {

		// Result
		$controllerCommand = strtolower(preg_replace("/[^a-zA-Z0-9\-]/i", "-", $controllerCommand));
		return self::lowerHyphenated_upperCamelCaps($controllerCommand.'-controller');
	}

	/**
	* Returns the URL command argument that represents the given Controller class.
	* Conversion: StringInputParamController > string-input-param
	*
	* @param string Controller class name in UpperCamelCaps format
	* @return string
	*/
	static public function controllerClass_controllerCommand($controllerClass) {

		// Result
		return self::upperCamelCaps_lowerHyphenated(preg_replace("/Controller$/", "", $controllerClass));
	}

	/**
	* Returns the method name that represents the given URL command argument.
	* Conversion: string-input-param > stringInputParam
	*
	* @param string Action as used in the URL command in lower-underscored format
	* @return string
	*/
	static public function actionCommand_actionMethod($actionCommand) {

		// Result
		$actionCommand = strtolower(preg_replace("/[^a-zA-Z0-9\-]/i", "-", $actionCommand));
		return self::lowerHyphenated_lowerCamelCaps($actionCommand);
	}

	/*--------------------------------------------------------------- EXTENSION */

	/**
	* Returns the Extension class name associated with the given extension/folder
	* name.
	* Conversion: string-input-param > StringInputParamExtension
	*
	* @param string Extension name (ie. folder name) in lower-hyphenated format
	* @return string
	*/
	static public function extensionName_extensionClass($extensionName) {

		// Result
		return self::lowerHyphenated_upperCamelCaps($extensionName).'Extension';
	}
}
?>