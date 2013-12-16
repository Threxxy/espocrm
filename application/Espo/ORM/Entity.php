<?php

namespace Espo\ORM;

abstract class Entity implements IEntity
{
	public $id = null;
	
	private $isNew = false;

	/**
	 * Entity name.
	 * @var string
	 */
	protected $entityName;
	
	/**
	 * @var array Defenition of fields.
	 * @todo make protected
	 */	
	public $fields = array();
	
	/**
	 * @var array Defenition of relations.
	 * @todo make protected
	 */	
	public $relations = array();
	
	
	/**
	 * @var array Field-Value pairs.
	 */
	protected $container = array();
	
	
	public function __construct($defs = array())
	{
		if (empty($this->entityName)) {
			$this->entityName = end(explode('\\', get_class($this)));
		}
		
		if (!empty($defs['fields'])) {
			$this->fields = $defs['fields'];
		}
		
		if (!empty($defs['relations'])) {
			$this->relations = $defs['relations'];
		}		
	}	
	
	public function clear($name)
	{
		unset($this->container[$name]);
	}
		
	public function reset()
	{
		$this->container = array();
	}	
	
	public function set($p1, $p2 = null)
	{
		if (is_array($p1)) {
			if ($p2 === null) {
				$p2 = false;
			}
			$this->populateFromArray($p1, $p2);
			return;
		} else {
			$name = $p1;
			$value = $p2;
			if ($name == 'id') {
				$this->id = $value;
			}
			if (array_key_exists($name, $this->fields)) {	
				$this->container[$name] = $value;
			}
		}
	}
	
	public function get($name)
	{
		if ($name == 'id') {
			return $this->id;
		}
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->$method();		
		}
		
		if (isset($this->container[$name])) {
			return $this->container[$name];
		}
		return null;
	}
	
	public function has($name)
	{
		if ($name == 'id') {
			return isset($this->id);
		}
		if (isset($this->container[$name])) {
			return true;
		}
		return false;
	}	
	
	public function populateFromArray(array $arr, $onlyAccessible = true)
	{
		$this->reset();
	
		foreach ($this->fields as $field => $fieldDefs) {
			if (isset($arr[$field])) {
				if ($field == 'id') {
					$this->id = $arr[$field]; 
					continue;
				}
				if ($onlyAccessible) {
					if (isset($fieldDefs['notAccessible']) && $fieldDefs['notAccessible'] == true) {
						continue;
					}
				}
				
				$this->container[$field] = $arr[$field];
			}
		}
	}
	
	public function isNew()
	{
		return $this->isNew;
	}
	
	public function setIsNew($isNew)
	{
		$this->isNew = $isNew;
	}	
		
	public function getEntityName()
	{
		return $this->entityName;
	}	
	
	public function hasField($fieldName)
	{
		return isset($this->fields[$fieldName]);
	}	
	
	public function hasRelation($relationName)
	{
		return isset($this->relations[$relationName]);
	}
	
	public function toArray()
	{
		$arr = $this->container;
		if (isset($this->id)) {
			$arr['id'] = $this->id;
		}
		return $arr;
	}
}
