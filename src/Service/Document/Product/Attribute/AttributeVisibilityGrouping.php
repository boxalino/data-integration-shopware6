<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\ShopwarePropertyTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Typed\StringAttribute;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class AttributeVisibilityGrouping
 *
 * The attribute_visibility_grouping property (array) is set at the level of GROUP
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252149870/doc%2Bproduct#Properties-specific-to-the-product-group-%26-SKU
 *
 * In SW6, the Storefront Properties defined at the level of the variant allows for each variant (for a given property set)
 * to be displayed in the listing, which makes it identifiable as part of the API response and visibile content
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class AttributeVisibilityGrouping extends ModeIntegrator
{
    use DeltaInstantTrait;
    use ShopwarePropertyTrait;

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getSystemConfiguration()->getLanguages();
        $iterator = $this->getQueryIterator($this->getStatementQuery());

        foreach ($iterator->getIterator() as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_ATTRIBUTE_VISIBILITY_GROUPING] = [];
            }

            if(is_null($item[DocSchemaInterface::FIELD_INTERNAL_ID]))
            {
                continue;
            }

            $values = explode(",", $item[DocSchemaInterface::FIELD_INTERNAL_ID]);
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_ATTRIBUTE_VISIBILITY_GROUPING] = $values;

            /** @var StringAttribute $schema */
            $schema = $this->getStringAttributeSchema($values, "configurator_group_config");
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING][] = $schema;
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->_getProductQuery($this->_getQueryFields())
            ->leftJoin('product','( ' . $this->_getStorefrontPropertiesNameQuery()->__toString() . ') ', 'storefront_properties',
                "product.id=storefront_properties.product_id AND storefront_properties.product_version_id=product.version_id")
            ->addGroupBy('product.id')
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION))
            ->setParameter("languageId", Uuid::fromHexToBytes($this->getSystemConfiguration()->getDefaultLanguageId()), ParameterType::STRING);

        return $query;
    }

    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function _getStorefrontPropertiesNameQuery() : QueryBuilder
    {
        $fields =[
            "storefront_properties.id AS product_id",
            "storefront_properties.version_id AS product_version_id",
            "property_group_name.name AS property_name"
        ];
        $query = $this->connection->createQueryBuilder();
        $query->select($fields)
            ->from('( ' . $this->_getStorefrontPropertiesQuery()->__toString() . ') ', 'storefront_properties')
            ->leftJoin('storefront_properties',
                '( ' . $this->getPropertyGroupDefaultTranslationQuery($this->getPropertyGroupDefaultTranslationFields())->__toString() . ') ',
                'property_group_name', "JSON_EXTRACT(cgf, '$.id') = property_group_name.{$this->getDiIdField()}"
            )
            ->andWhere("JSON_EXTRACT(cgf, '$.expressionForListings') = true")
            ->andWhere("storefront_properties.main_variant_id IS NULL");

        return $query;
    }

    /**
     * Query to reiterate through all the group configurations on the product
     *
     * UPDATE THIS IF YOUR BI CREATES VARIANTS USING MORE THAN 5 CONCURRENT PROPERTIES ON A PRODUCT
     * @return QueryBuilder
     */
    protected function _getStorefrontPropertiesQuery() : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(["t.id", "t.main_variant_id", "t.version_id", "JSON_EXTRACT(t.configurator_group_config, CONCAT('$[', x.configurator, ']')) AS cgf"])
            ->from("product", "t")
            ->innerJoin("t","(SELECT 0 AS configurator UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5)",
                "x","JSON_EXTRACT(t.configurator_group_config, CONCAT('$[', x.configurator, ']')) IS NOT NULL"
            );

        return $query;
    }

    /**
     * @return string[]
     */
    public function _getQueryFields() : array
    {
        return [
            "LOWER(HEX(product.id)) AS {$this->getDiIdField()}",
            "GROUP_CONCAT(storefront_properties.property_name) AS " . DocSchemaInterface::FIELD_INTERNAL_ID
        ];
    }


}
