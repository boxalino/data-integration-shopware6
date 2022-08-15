<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;
use Boxalino\DataIntegration\Service\Util\ShopwareMediaTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\DataAbstractionLayer\MediaRepositoryDecorator;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;

/**
 * Class Category
 * Exports the translation of the categories
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute
 */
class Category extends ModeIntegrator
{

    use ShopwareMediaTrait;
    use DocAttributeValueTrait;
    use ShopwareLocalizedTrait;

    /**
     * @var StringLocalized
     */
    protected $localizedStringBuilder;

    /**
     * @param Connection $connection
     * @param StringLocalized $localizedStringBuilder
     * @param LoggerInterface $boxalinoLogger
     * @param UrlGeneratorInterface $generator
     * @param MediaRepositoryDecorator $mediaRepository
     */
    public function __construct(
        Connection $connection,
        StringLocalized $localizedStringBuilder,
        LoggerInterface $boxalinoLogger,
        UrlGeneratorInterface $generator,
        EntityRepositoryInterface $mediaRepository
    ){
        $this->logger = $boxalinoLogger;
        $this->localizedStringBuilder = $localizedStringBuilder;
        $this->mediaRepository = $mediaRepository;
        $this->mediaUrlGenerator = $generator;
        $this->context = Context::createDefaultContext();
        parent::__construct($connection);
    }

    /**
     * Structure: [property-name => [$schema, $schema], property-name => [], [..]]
     *
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $content[DocSchemaInterface::FIELD_CATEGORIES] = [];
        foreach($this->getData(DocSchemaInterface::FIELD_CATEGORIES) as $item)
        {
            $schema = $this->initializeSchemaForRow($item);
            foreach(array_filter(explode("|", $item[DocSchemaInterface::FIELD_PARENT_VALUE_IDS] ?? "")) as $parentId)
            {
                $schema[DocSchemaInterface::FIELD_PARENT_VALUE_IDS][] = $parentId;
            }

            // adding  name
            $name = $this->getLocalizedPropertyById($item[$this->getDiIdField()], "name");
            $schema = $this->addingPropertyToSchema(DocSchemaInterface::FIELD_VALUE_LABEL, $schema, $name);

            // adding description
            $description = $this->getLocalizedPropertyById($item[$this->getDiIdField()], "description");
            $schema = $this->addingPropertyToSchema(DocSchemaInterface::FIELD_DESCRIPTION, $schema, $description);

            // adding link
            $link = $this->getLocalizedPropertyById($item[$this->getDiIdField()], DocSchemaInterface::FIELD_LINK);
            $schema = $this->addingPropertyToSchema(DocSchemaInterface::FIELD_LINK, $schema, $link);

            $content[DocSchemaInterface::FIELD_CATEGORIES][] = $schema;
        }

        return $content;
    }

    /**
     * Main query for all categories export (linked to a given sales channel)
     */
    public function _getQuery(?string $propertyName = null) : QueryBuilder
    {
        $rootCategoryId = $this->getSystemConfiguration()->getNavigationCategoryId();
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from("category")
            ->andWhere('category.version_id = :categoryLiveVersion')
            ->andWhere('category.path LIKE :rootCategoryId OR LOWER(HEX(category.id))=:root')
            ->addGroupBy("category.id")
            ->setParameter('root', $rootCategoryId, ParameterType::STRING)
            ->setParameter('rootCategoryId', "%|$rootCategoryId|%", ParameterType::STRING)
            ->setParameter('categoryLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * @return string[]
     */
    public function _getQueryFields() :  array
    {
        return [
            "LOWER(HEX(category.id)) AS " . $this->getDiIdField(),
            "LOWER(HEX(category.parent_id)) AS " . DocSchemaInterface::FIELD_PARENT_VALUE_IDS,
            "category.active AS " . DocSchemaInterface::FIELD_STATUS,
            "LOWER(HEX(category.media_id)) AS " . DocSchemaInterface::FIELD_IMAGES,
        ];
    }

    /**
     * @param string|null $propertyName
     * @return QueryBuilder
     */
    public function getQueryInstant(?string $propertyName = null) : QueryBuilder
    {
        $rootCategoryId = $this->getSystemConfiguration()->getNavigationCategoryId();
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from("category")
            ->leftJoin("category", "product_category_tree", "product_category_tree",
            "category.id = product_category_tree.category_id AND category.version_id=product_category_tree.category_version_id")
            ->andWhere('product_category_tree.product_version_id = :productLiveVersion')
            ->andWhere('category.version_id = :categoryLiveVersion')
            ->andWhere('category.path LIKE :rootCategoryId OR LOWER(HEX(category.id))=:root')
            ->andWhere('product_category_tree.product_id IN (:ids)')
            ->addGroupBy("product_category_tree.category_id")
            ->setParameter('root', $rootCategoryId, ParameterType::STRING)
            ->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter('rootCategoryId', "%|$rootCategoryId|%", ParameterType::STRING)
            ->setParameter('productLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('categoryLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * Generic accessor for the localized information on category
     *
     * @param string $propertyName
     * @throws \Exception
     */
    public function getLocalizedQueryResults(string $propertyName) : array
    {
        $this->setPrefix(DocSchemaInterface::FIELD_CATEGORIES);

        $groupBy = "$this->prefix.category_id";
        if($propertyName === DocSchemaInterface::FIELD_LINK)
        {
            $groupBy = "$this->prefix.foreign_key";
        }
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields($groupBy))
            ->from('( ' . $this->getLocalizedFieldsQuery($propertyName)->__toString() . ')', $this->getPrefix())
            ->addGroupBy($groupBy)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query->execute()->fetchAll();
    }


    /**
     * Accessing category name translation (name)
     * If there is no translation available, the default one is used
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     * @throws \Exception
     */
    protected function getLocalizedFieldsQuery(string $propertyName) : QueryBuilder
    {
        if($propertyName === DocSchemaInterface::FIELD_LINK)
        {
            return $this->localizedStringBuilder->getLocalizedFields('seo_url', 'id', 'id',
                'foreign_key','seo_path_info', ['seo_url.foreign_key', 'seo_url.sales_channel_id'],
                $this->getSystemConfiguration()->getLanguagesMap(), $this->getSystemConfiguration()->getDefaultLanguageId(),
                [
                    "seo_url.is_canonical=1",
                    "seo_url.route_name='frontend.navigation.page'",
                    "LOWER(HEX(seo_url.sales_channel_id))='{$this->getSystemConfiguration()->getSalesChannelId()}' OR seo_url.sales_channel_id IS NULL"
                ]
            );
        }

        return $this->localizedStringBuilder->getLocalizedFields('category_translation','category_id',
            'category_id','category_version_id', $propertyName,
            ['category_translation.category_id', 'category_translation.category_version_id'],
            $this->getSystemConfiguration()->getLanguagesMap(), $this->getSystemConfiguration()->getDefaultLanguageId(),
            ["category_translation.category_version_id = :live"]
        );
    }


}
