<?php

namespace Espo\Core\Utils\Database;

use Espo\Core\Utils\Util,
	Espo\ORM\Entity;

class Converter
{
	private $metadata;

	private $schemaConverter;



	private $schemaFromMetadata = null;

	/**
	* @var array $meta - metadata array
	*/
	//private $meta;


    public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
		$this->metadata = $metadata;

        $this->ormConverter = new Converters\Orm($this->metadata);

        $this->schemaConverter = new Converters\Schema();
	}


	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getOrmConverter()
	{
    	return $this->ormConverter;
	}

    protected function getSchemaConverter()
	{
    	return $this->schemaConverter;
	}


	public function getSchemaFromMetadata()
	{
		return $this->schemaFromMetadata;
	}

	protected function setSchemaFromMetadata(\Doctrine\DBAL\Schema\Schema $schema)
	{
		$this->schemaFromMetadata = $schema;
	}

	/**
	* Main method of convertation from metadata to orm metadata and database schema
	*
	* @return bool
	*/
	public function process()
	{
		$GLOBALS['log']->add('Debug', 'Converter:process() - Start: converting metadata to orm format and database schema');

        $entityDefs = $this->getMetadata()->get('entityDefs');

		$databaseMeta = array();
        foreach($entityDefs as $entityName => $entityMeta) {

			if (empty($entityMeta)) {
		    	$GLOBALS['log']->add('ERROR', 'Converter:process(), Entity:'.$entityName.' - metadata cannot be converted into database format');
				continue;
			}

     		$databaseMeta = Util::merge($databaseMeta, $this->getOrmConverter()->process($entityName, $entityMeta, $entityDefs));
        }

        $databaseMeta = $this->getOrmConverter()->prepare($databaseMeta);

		$schema = $this->getSchemaConverter()->process($databaseMeta, $entityDefs);
		$this->setSchemaFromMetadata($schema);

		//save database meta to a file espoMetadata.php
        $result = $this->getMetadata()->setEspoMetadata($databaseMeta);


		$GLOBALS['log']->add('Debug', 'Converter:process() - End: converting metadata to orm format and database schema, result=['.$result.']');

        return $result;
	}




}


?>