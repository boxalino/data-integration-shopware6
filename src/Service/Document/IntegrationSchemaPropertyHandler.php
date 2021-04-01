<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\DocOrderAttributeTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocProductAttributeTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocPropertiesTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandlerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class IntegrationPropertyHandler
 *
 * Handles the data integration logic for product attributes
 * (from the documented schema and/or available in the project)
 *
 * @package Boxalino\DataIntegration\Service\Document
 */
abstract class IntegrationSchemaPropertyHandler extends DocSchemaPropertyHandler
    implements \JsonSerializable, DocSchemaPropertyHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;
    use DocProductAttributeTrait;
    use DocOrderAttributeTrait;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(
        Connection $connection
    ){
        $this->connection = $connection;
        parent::__construct();
    }

    /**
     * @return array
     */
    public function getData(?string $propertyName = null) : array
    {
        try{
            return $this->getQuery($propertyName)->execute()->fetchAll();
        } catch (\Throwable $exception)
        {
            throw new \Exception($exception->getMessage());
        }
    }


    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    abstract function getQuery(?string $propertyName = null) : QueryBuilder;

}
