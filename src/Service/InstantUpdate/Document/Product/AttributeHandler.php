<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Product;

use Boxalino\DataIntegration\Service\InstantUpdate\Document\DocHandlerTrait;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\DocPropertiesHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\DocPropertiesTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProduct\Attribute;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProduct\AttributeHandlerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class AttributeHandler
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document
 */
class AttributeHandler extends Attribute
    implements \JsonSerializable, AttributeHandlerInterface, DocPropertiesHandlerInterface
{

    use DocPropertiesTrait;
    use DocHandlerTrait;

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
     * @return string
     */
    public function getInstantUpdateIdField() : string
    {
        return self::INSTANT_UPDATE_ID_FIELD;
    }

}
