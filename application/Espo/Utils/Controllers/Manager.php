<?php

namespace Espo\Utils\Controllers;

use Espo\Utils as Utils,
	Espo\Utils\Api as Api,
	Espo\Utils\Controllers\Controller as Controller;

class Manager
{

	public function __construct()
	{

	}

	/**
    * Manage of all controllers
	*
	* @param array $controllerParams - array('controller' => 'Layout', 'action' => ':name',	'scope' => ':controller');
	* @param array $params - route params, ex. /:controller/layout/:name/ -  array(controller=>Value, name=>Value)
	* @param array $data - request data
	*
	* @return array
	*/
	public function call($controllerParams, $params, $data = '')
	{
	  $baseUtils = new Utils\BaseUtils();

	  $espoPath = $baseUtils->getObject('Configurator')->get('espoPath');
	  $controllerPath= $baseUtils->concatPath($espoPath, 'Utils/Controllers');

	  $crud = $baseUtils->getObject('Configurator')->get('crud');
	  $baseAction = $crud->$controllerParams['HttpMethod'];
	  if (empty($baseAction)) {
      	return $this->response(false, 'Cannot find action for HTTP Method ['.$controllerParams['HttpMethod'].']', 404);
	  }

	  $controller = (object) array(
	  	'name' => strtolower($controllerParams['controller']),
	  	'baseAction' => $baseAction,
	  	'scope' => $controllerParams['scope'],
	  	'action' => $controllerParams['action'],
	  );


	  if (!$baseUtils->isScopeExists($controller->scope)) {
      	return $this->response(false, 'Controller for Scope ['.$controller->scope.'] does not exist.', 404);
	  }

	  //define default values
	  $classInfo = new \stdClass();
      $classInfo->name = $this->getClassName($controllerPath, 'Controller');
      $classInfo->path = $this->getClassPath($classInfo->name);
      $classInfo->method = $this->getDefinedMethod($classInfo->name, $controller->baseAction, $controller->action);


	  //Espo\Utils\Controlles\Layout  and  Custom\Espo\Utils\Controlles\Layout
	  $controllerClass = $this->getClassName($controllerPath, $controller->name);
      $classInfo = $this->setClassInfo($controllerClass, $classInfo, $controller);
      //END: Espo\Utils\Controlles\Layout and Custom\Espo\Utils\Controlles\Layout


	  //path in Modules dir
	  $controllerDir = $baseUtils->concatPath( $baseUtils->getScopePath($controller->scope), $baseUtils->getObject('Configurator')->get('controllerPath') );

	  //ex. Modules\Crm\Controllers\Layout  and  Cusom\Modules\Crm\Controllers\Layout
	  $controllerClass = $this->getClassName($controllerDir, $controller->name);
	  $classInfo = $this->setClassInfo($controllerClass, $classInfo, $controller);
	  //END: ex. Modules\Crm\controllers\Layout and Cusom\Modules\Crm\controllers\Layout


      //ex. Modules\Crm\Controllers\AccountLayout  and  Custom\Modules\Crm\Controllers\AccountLayout
	  $controllerClass = $this->getClassName($controllerDir, $controller->scope.'-'.$controller->name);
	  $classInfo = $this->setClassInfo($controllerClass, $classInfo, $controller);
	  //END: ex. Modules\Crm\Controllers\AccountLayout and Custom\Modules\Crm\Controllers\AccountLayout


	  //CHECK ACTION
	  if (empty($classInfo->method)) {
      	return $this->response(false, 'Actions ['.$controller->baseAction.'] and ['.$controller->action.'] do not exist.', 404);
	  }
	  //END: CHECK ACTION


	  //call class method
      $className = $classInfo->name;
	  $classMethod = $classInfo->method;

	  require_once($classInfo->path);
	  $class = new $className();

	  //call before method if exists: beforeRead, beforeDetailSmall, beforeReadDetailSmall
	  $beforeMethod = $this->getDefinedMethod($className, $controller->baseAction, $controller->action, 'before');
      if ( !empty($beforeMethod) ) {
			$class->$beforeMethod($params, $data);
	  } //END: call before method if exists

	  $result = $class->$classMethod($params, $data);

      //call after method if exists: afterRead, afterDetailSmall, afterReadDetailSmall
	  $afterMethod = $this->getDefinedMethod($className, $controller->baseAction, $controller->action, 'after');
      if ( !empty($afterMethod) ) {
			try {
            	$class->$afterMethod($params, $data, $result);
			} catch (\Exception $e) {
                $class->$afterMethod($params, $data);
			}
	  } //END: call after method if exists


	  if (is_array($result)) {

	  	$returnResult = array_values($result);
		if (!empty($returnResult[2])) {
        	return $this->response($returnResult[0], $returnResult[1], $returnResult[2]);
		}
		if (!empty($returnResult[1])) {
        	return $this->response($returnResult[0], $returnResult[1]);
		}
		if (!empty($returnResult[0])) {
        	return $this->response($returnResult[0]);
		}

		return $this->response(false, 'Cannot find requested controller', 404);
	  }

	  return $this->response($result);
	}



	/**
    * Check if methods exist in class and return the method name according to priority
	*
	* @param string $className
	*
	* @return sting
	*/
	function getDefinedMethod($className, $baseAction, $action, $prefix = '')
	{
		$allActions= get_class_methods($className);
		$classMethod = '';

		if (!empty($prefix)) {
          $prefix .= '-';
		}

        $baseUtils = new Utils\BaseUtils();

		//method as 'read'
		$prefixBaseAction = $baseUtils->toCamelCase($prefix.$baseAction);
		if ( method_exists($className, $prefixBaseAction) ) {
		   	$classMethod = $prefixBaseAction;
		}

		//method as 'detailSmall'
        $prefixAction = $baseUtils->toCamelCase($prefix.$action);
		if ( method_exists($className, $prefixAction) ) {
		   	$classMethod = $prefixAction;
		}

		//method as 'readDetailSmall'
		$fullAction = $baseUtils->toCamelCase($prefix.$baseAction.'-'.$action);
		if (method_exists($className, $fullAction)) {
		   	$classMethod = $fullAction;
		}

        return $classMethod;
	}

	/**
    * If method exists, then redefine classInfo
	*
	* @param string $className
	* @param object $classInfo
	* @param object $controller
	* @param bool $isCustom - is need to check custom folder
	*
	* @return object
	*/
	protected function setClassInfo($className, \stdClass $classInfo, \stdClass $controller, $isCustom = true)
	{
		$classPath = $this->getClassPath($className);
		$classMethod = $this->getDefinedMethod($className, $controller->baseAction, $controller->action);

		if (file_exists($classPath) && !empty($classMethod) ) {
			$classInfo->name = $className;
			$classInfo->path = $classPath;
			$classInfo->method = $classMethod;
		}

		if ($isCustom) {
            $baseUtils = new Utils\BaseUtils();
			$espoCustomDir = $baseUtils->getObject('Configurator')->get('espoCustomPath');
			$controllerClass = $espoCustomDir.'\\'.$className;
        	$classInfo = $this->setClassInfo($controllerClass, $classInfo, $controller, false);
		}

		return $classInfo;
	}



    /**
    * Get class name from path and name
	*
	* @param string $path
	* @param string $name
	*
	* @return string
	*/
	protected function getClassName($path, $name = '')
	{
		$baseUtils = new Utils\BaseUtils();

		if (!empty($name)) {
		  	$path = $baseUtils->concatPath($path, $baseUtils->toCamelCase($name, true));
		}

		return $baseUtils->toFormat($path, '\\');
	}


	/**
    * Get full class path (inc. "application" and file extension) from path and name
	*
	* @param string $path
	* @param string $name
	*
	* @return string
	*/
	protected function getClassPath($path, $name = '')
	{
		$baseUtils = new Utils\BaseUtils();

        if (!empty($name)) {
        	$path = $baseUtils->concatPath($path, $baseUtils->toCamelCase($name, true));
    	}

		return $baseUtils->concatPath('application', $baseUtils->toFormat($path, '/').'.php');
	}


	/**
    * Prepare response to output
	*
	* @param mixed $data
	* @param string $errMessage
	* @param int $errorCode
	*
	* @return object
	*/
	public function response($data=null, $errMessage='Error', $errorCode=404)
	{
		return (object) array(
			'data' => $data,
			'errMessage' => $errMessage,
			'errCode' => $errorCode,
		);
	}




}


?>