<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;
use Boxalino\DataIntegrationDoc\Doc\Schema\Repeated;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegrationDoc\Doc\Schema\RepeatedLocalized;
use Boxalino\DataIntegrationDoc\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaIntegrationTrait;

/**
 * Class Brand
 * Load brand information for the product (translation)
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Brand extends ModeIntegrator
{
    use ShopwareLocalizedTrait;
    use DeltaInstantAddTrait;

    /**
     * @var array | null
     */
    protected $localizedPropertyValues = null;

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
        foreach ($this->getData() as $item)
        {
            /** @var Repeated $brand */
            $brand = $this->getRepeatedLocalizedSchema(
                array_merge($item, $this->getLocalizedPropertyById($item[DocSchemaInterface::FIELD_INTERNAL_ID])),
                $languages
            );
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_BRANDS][] = $brand;
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
            ->from('product')
            ->leftJoin("product", 'product', 'parent',
                'product.parent_id = parent.id AND product.parent_version_id = parent.version_id')
            ->andWhere('product.version_id = :live')
            ->andWhere('product.product_manufacturer_version_id = :live OR parent.product_manufacturer_version_id = :live')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->addGroupBy('product.id')
            ->orderBy("product.created_at", "DESC")
            ->addOrderBy("product.auto_increment", "DESC")
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }

    /**
     * @return string[]
     */
    protected function _getQueryFields() : array
    {
        return ["LOWER(HEX(product.id)) AS {$this->getDiIdField()}",
            "IF(product.product_manufacturer_id IS NULL, LOWER(HEX(parent.product_manufacturer_id)), LOWER(HEX(product.product_manufacturer_id))) AS " . DocSchemaInterface::FIELD_INTERNAL_ID
        ];
    }

    /**
     * Identify localized content by the brand id
     *
     * @param string $id
     * @return array
     * @throws \Exception
     */
    protected function getLocalizedPropertyById(string $id) : ?array
    {
        $localizedValues = $this->getLocalized();
        foreach($localizedValues as $row)
        {
            if($row[$this->getDiIdField()] === $id)
            {
                return $row;
            }
        }

        return null;
    }

    /**
     * Get informaiton about the brands/manufacturers
     * @return array
     * @throws \Exception
     */
    protected function getLocalized() : array
    {
        if(is_null($this->localizedPropertyValues))
        {
            $this->setPrefix("manufacturer");
            $query = $this->connection->createQueryBuilder();
            $query->select($this->getFields("manufacturer.product_manufacturer_id"))
                ->from('( ' . $this->getLocalizedFieldsQuery()->__toString() . ')', 'manufacturer')
                ->andWhere('manufacturer.product_manufacturer_version_id = :live')
                ->addGroupBy('manufacturer.product_manufacturer_id')
                ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

            $this->localizedPropertyValues = $query->execute()->fetchAll();
        }

        return $this->localizedPropertyValues;
    }

    /**
     * Prepare brand translations
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    public function getLocalizedFieldsQuery() : QueryBuilder
    {
        return $this->localizedStringBuilder->getLocalizedFields('product_manufacturer_translation', 'product_manufacturer_id',
            'product_manufacturer_id','product_manufacturer_version_id','name',
            ['product_manufacturer_translation.product_manufacturer_id', 'product_manufacturer_translation.product_manufacturer_version_id'],
            $this->getSystemConfiguration()->getLanguagesMap(), $this->getSystemConfiguration()->getDefaultLanguageId()
        );
    }


}
