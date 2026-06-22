<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegration\Service\Util\ShopwarePropertyTrait;
use Boxalino\DataIntegrationDoc\Doc\Schema\Repeated;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Property
 * Exporter for the configured product properties
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Property extends ModeIntegrator
{

    use ShopwarePropertyTrait;
    use DeltaInstantTrait;

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
        $languages = $this->getSystemConfiguration()->getLanguages();
        foreach($this->getPropertyNames() as $property)
        {
            $propertyName = $this->sanitizePropertyName($property['name']);
            $this->setPropertyId($property[$this->getDiIdField()]);

            /** on instant mode - export only the allowed properties */
            if(!$this->isPropertyAllowedOnInstantMode($propertyName) && $this->filterByIds())
            {
                continue;
            }

            $iterator = $this->getQueryIterator($this->getStatementQuery($property[$this->getDiIdField()]));

            foreach ($iterator->getIterator() as $item)
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
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING_LOCALIZED][] = $schema->toArray();
            }
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from("product_property")
            ->leftJoin('product_property', '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                $this->getPrefix(), "$this->prefix.property_group_option_id = product_property.property_group_option_id")
            ->leftJoin("product_property", "( " . $this->_getProductQuery()->__toString() . " )", "product", "product.id=product_property.product_id")
            ->leftJoin("product_property", "property_group_option", "pgo", "product_property.property_group_option_id=pgo.id")
            ->andWhere($this->getLanguageHeaderConditional())
            #->addGroupBy('property_group_option.id')
            ->andWhere("product.id IS NOT NULL")
            ->andWhere("product_property.product_version_id = :live")
            ->andWhere("LOWER(HEX(pgo.property_group_id)) = '$propertyName'")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('propertyGroupId', Uuid::fromHexToBytes($propertyName), ParameterType::BINARY);

        return $query;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function _getQueryFields() : array
    {
        return array_merge(
            $this->getFields("product_property.product_id"),
            ["LOWER(HEX($this->prefix.property_group_option_id)) AS " . DocSchemaInterface::FIELD_INTERNAL_ID]
        );
    }


}
