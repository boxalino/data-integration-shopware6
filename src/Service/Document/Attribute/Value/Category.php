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
	    $rows = $this->getData(DocSchemaInterface::FIELD_CATEGORIES);
	    $rootCategoryId = $this->getSystemConfiguration()->getNavigationCategoryId();
	    $categoryLookup = $this->_navigationLookup($rows);
	    $navigationStatusMemo = [];
		
        foreach($rows as $item)
        {
	        $schema = $this->initializeSchemaForRow($item);
	        if($item[DocSchemaInterface::FIELD_PARENT_VALUE_IDS])
	        {
		        $schema[DocSchemaInterface::FIELD_PARENT_VALUE_IDS][] = $item[DocSchemaInterface::FIELD_PARENT_VALUE_IDS];
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
	        
	        // adding numeric attributes for level, visible
	        $schema[DocSchemaInterface::FIELD_NUMERIC][] = $this->getNumericAttributeSchema([$item['visible']] , "visible", null)->toArray();
	        $schema[DocSchemaInterface::FIELD_NUMERIC][] = $this->getNumericAttributeSchema([$item['level']] , "level", null)->toArray();
	        $schema[DocSchemaInterface::FIELD_NUMERIC][] = $this->getNumericAttributeSchema([$item['active']] , "active", null)->toArray();

	        // adding numeric attribute for whether the category is actually reachable in the storefront navigation menu
	        $isUsedInNavigation = (int) $this->isUsedInNavigation($item[$this->getDiIdField()], $categoryLookup, $rootCategoryId, $navigationStatusMemo);
	        $schema[DocSchemaInterface::FIELD_NUMERIC][] = $this->getNumericAttributeSchema([$isUsedInNavigation] , "use_in_navigation", null)->toArray();

	        // adding string attribute for page
	        $schema[DocSchemaInterface::FIELD_STRING][] = $this->getStringAttributeSchema([$item['type']] , "type")->toArray();

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
	        "category.active AS active",
            "LOWER(HEX(category.media_id)) AS " . DocSchemaInterface::FIELD_IMAGES,
	        "category.level AS level",
	        "category.visible AS visible",
	        "category.type AS type",
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
	
	/**
	 * @param array $rows
	 * @return array
	 */
	protected function _navigationLookup(array $rows) : array
	{
		$categoryLookup = [];
		foreach($rows as $row)
		{
			$categoryLookup[$row[$this->getDiIdField()]] = [
				"active" => (bool) $row["active"],
				"visible" => (bool) $row['visible'],
				"parentId" => $row[DocSchemaInterface::FIELD_PARENT_VALUE_IDS] ?: null,
			];
		}
		
		return $categoryLookup;
	}
	
	/**
	 * A category only shows up in the storefront navigation menu (Shopware\Core\Content\Category\Service\NavigationLoader)
	 * if it - and every one of its ancestors up to the sales channel's navigation root - is active and visible.
	 * A single inactive/hidden parent hides the whole branch below it, even if a descendant is itself active+visible.
	 *
	 * @param array<string, array{active: bool, visible: bool, parentId: ?string}> $categoryLookup
	 * @param array<string, bool> $memo
	 */
	protected function isUsedInNavigation(string $categoryId, array $categoryLookup, string $rootCategoryId, array &$memo) : bool
	{
		if($categoryId === $rootCategoryId)
		{
			return false;
		}
		
		if(isset($memo[$categoryId]))
		{
			return $memo[$categoryId];
		}
		
		if(!isset($categoryLookup[$categoryId]))
		{
			return $memo[$categoryId] = false;
		}
		
		$category = $categoryLookup[$categoryId];
		if(!$category['active'] || !$category['visible'])
		{
			return $memo[$categoryId] = false;
		}
		
		$parentId = $category['parentId'];
		if(is_null($parentId) || $parentId === $rootCategoryId)
		{
			return $memo[$categoryId] = true;
		}
		
		return $memo[$categoryId] = $this->isUsedInNavigation($parentId, $categoryLookup, $rootCategoryId, $memo);
	}


}
