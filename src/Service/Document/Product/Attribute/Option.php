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
 * Class Option
 * Exporter for the configured product options
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Option extends ModeIntegrator
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
            ->from("product_option")
            ->leftJoin('product_option', '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                $this->getPrefix(), "$this->prefix.property_group_option_id = product_option.property_group_option_id")
            ->leftJoin("product_option", "( " . $this->_getProductQuery()->__toString() . " )", "product", "product.id=product_option.product_id")
            ->leftJoin("product_option", "property_group_option", "pgo", "product_option.property_group_option_id=pgo.id")
            ->andWhere($this->getLanguageHeaderConditional())
            ->andWhere("product.id IS NOT NULL")
            ->andWhere("product_option.product_version_id = :live")
            ->andWhere("LOWER(HEX(pgo.property_group_id)) = '$propertyName'")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING);

        return $query;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function _getQueryFields() : array
    {
        return array_merge(
            $this->getFields("product_option.product_id"),
            ["LOWER(HEX($this->prefix.property_group_option_id)) AS " . DocSchemaInterface::FIELD_INTERNAL_ID]
        );
    }


}
