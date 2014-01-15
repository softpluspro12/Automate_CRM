<?php

namespace Espo\ORM;

class EntityManager
{

	protected $pdo;

	protected $entityFactory;

	protected $repositoryFactory;

	protected $mapper;

	protected $metadata;

	protected $repositoryHash = array();

	public function __construct($params)
	{
		$this->initPDO($params);

		$this->metadata = new Metadata();

		if (!empty($params['metadata'])) {
			$this->setMetadata($params['metadata']);
		}

		$entityFactoryClassName = '\\Espo\\ORM\\EntityFactory';
		if (!empty($params['entityFactoryClassName'])) {
			$entityFactoryClassName = $params['entityFactoryClassName'];
		}
		$this->entityFactory = new $entityFactoryClassName($this, $this->metadata);

		$mapperClassName = '\\Espo\\ORM\\DB\\MysqlMapper';
		if (!empty($params['mapperClassName'])) {
			$mapperClassName = $params['mapperClassName'];
		}
		$this->mapper = new $mapperClassName($this->pdo, $this->entityFactory);

		$repositoryFactoryClassName = '\\Espo\\ORM\\RepositoryFactory';
		if (!empty($params['repositoryFactoryClassName'])) {
			$repositoryFactoryClassName = $params['repositoryFactoryClassName'];
		}
		$this->repositoryFactory = new $repositoryFactoryClassName($this, $this->entityFactory, $this->mapper);
		
		$this->init();
	}

	protected function initPDO($params)
	{
		$this->pdo = new \PDO('mysql:host='.$params['host'].';dbname=' . $params['dbname'], $params['user'], $params['password']);
	}

	public function getEntity($name, $id = null)
	{
		return $this->getRepository($name)->get($id);
	}
	
	public function saveEntity(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		return $this->getRepository($entityName)->save($entity);
	}
	
	public function removeEntity(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		return $this->getRepository($entityName)->remove($entity);
	}

	public function getRepository($name)
	{
		if (empty($this->repositoryHash[$name])) {
			$this->repositoryHash[$name] = $this->repositoryFactory->create($name);
		}
		return $this->repositoryHash[$name];
	}

	public function setMetadata(array $data)
	{
		$this->metadata->setData($data);
	}
	
	public function getMetadata()
	{
		return $this->metadata;
	}

	public function getPDO()
	{
		return $this->pdo;
	}

	public function normalizeRepositoryName($name)
	{
		return $name;
	}

	public function normalizeEntityName($name)
	{
		return $name;
	}
	
	public function createCollection($entityName, $data = array())
	{
		$seed = $this->getEntity($entityName);		
		$collection = new EntityCollection($data, $seed, $this->entityFactory);		
		return $collection;
	}
	
	protected function init()
	{
	}
}

