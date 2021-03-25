<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegration\Service\Document\Product\Attribute\Entity as ProductEntityConfiguration;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Localized;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class Entity
 * Access the product_groups & sku information from the product table
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute
 */
class Entity extends IntegrationSchemaPropertyHandler
{

    /**
     * @var ProductEntityConfiguration
     */
    protected $productEntityConfiguration;

    /**
     * Entity constructor.
     * @param Connection $connection
     * @param Entity $entityConfiguration
     */
    public function __construct(
        Connection $connection,
        ProductEntityConfiguration $entityConfiguration
    ){
        $this->productEntityConfiguration = $entityConfiguration;
        parent::__construct($connection);
    }

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getConfiguration()->getLanguages();
        foreach ($this->getData() as $item)
        {
            $propertyName = $item[$this->getDiIdField()];
            $docAttributeName = $this->productEntityConfiguration->getDocPropertyByField($propertyName);
            if($docAttributeName)
            {
                $content[$docAttributeName] = [];
                $content[$docAttributeName][DocSchemaInterface::FIELD_LABEL] = $this->getPropertyLabel($propertyName, $languages);

                $docMappingName = $this->productEntityConfiguration->getProperties()[$propertyName];
                if( in_array($docMappingName, $this->getBooleanSchemaTypes()) ||
                    in_array($docMappingName, $this->getSingleValueSchemaTypes())
                ){
                    continue;
                }

                if(in_array($docMappingName, $this->getMultivalueSchemaTypes()))
                {
                    if(in_array($docMappingName, $this->getTypedLocalizedSchemaProperties())
                    ){
                        $content[$docAttributeName][DocSchemaInterface::FIELD_LOCALIZED] = true;
                        continue;
                    }

                    if(in_array($docMappingName, $this->getTypedSchemaProperties()))
                    {
                        /** @var Typed | null $typedProperty */
                        $typedProperty = $this->productEntityConfiguration->getAttributeSchema($docMappingName);
                        if($typedProperty)
                        {
                            $content[$docAttributeName][DocSchemaInterface::FIELD_FORMAT] = $typedProperty->getType();
                        }

                        continue;
                    }
                }
            }
        }

        return $content;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(["COLUMN_NAME AS " . $this->getDiIdField()])
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where("TABLE_NAME = N'product'");

        return $query;
    }


}