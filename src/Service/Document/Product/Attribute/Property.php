<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegration\Service\Util\ShopwarePropertyTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Repeated;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Property
 * Exporter for the configured product properties
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Property extends IntegrationSchemaPropertyHandler
{

    use ShopwarePropertyTrait;

    public function __construct(
        Connection $connection,
        StringLocalized $localizedStringBuilder
    ){
        $this->localizedStringBuilder = $localizedStringBuilder;
        parent::__construct($connection);
    }

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getConfiguration()->getLanguages();
        foreach($this->getPropertyNames() as $property)
        {
            $propertyName = $property['name'];
            $this->setPropertyId($property[$this->getDiIdField()]);
            foreach ($this->getData($property[$this->getDiIdField()]) as $item)
            {
                if(is_null($item[DocSchemaInterface::FIELD_INTERNAL_ID]))
                {
                    continue;
                }
                if(!isset($content[$item[$this->getDiIdField()]]))
                {
                    $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING_LOCALIZED] = [];
                }

                /** @var Repeated $schema */
                $schema = $this->getRepeatedLocalizedSchema($item, $languages, $propertyName);
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING_LOCALIZED][] = $schema;
            }
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getQuery(?string $propertyName = null): QueryBuilder
    {
        $fields = array_merge(
            $this->getFields("product_property.product_id"),
            ["LOWER(HEX($this->prefix.property_group_option_id)) AS " . DocSchemaInterface::FIELD_INTERNAL_ID]
        );
        $query = $this->connection->createQueryBuilder();
        $query->select($fields)
            ->from("product_property")
            ->leftJoin('product_property', '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                $this->getPrefix(), "$this->prefix.property_group_option_id = product_property.property_group_option_id")
            ->leftJoin("product_property", "product", "product", "product.id=product_property.product_id AND product.version_id=product_property.product_version_id")
            ->leftJoin("product_property", "property_group_option", "pgo", "product_property.property_group_option_id=pgo.id")
            ->andWhere($this->getLanguageHeaderConditional())
            ->andWhere('product.version_id = :live')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            #->addGroupBy('property_group_option.id')
            ->andWhere("LOWER(HEX(pgo.property_group_id)) = '$propertyName'")
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('propertyGroupId', Uuid::fromHexToBytes($propertyName), ParameterType::BINARY);

        return $query;
    }


}
