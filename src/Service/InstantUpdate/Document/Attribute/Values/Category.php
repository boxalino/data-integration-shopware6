<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Attribute\Values;

use Boxalino\DataIntegration\Service\InstantUpdate\Document\DocHandlerTrait;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\DocPropertiesHandlerInterface;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\AttributeHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Hierarchical;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Service\DocPropertiesTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProduct\AttributeHandlerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Category as CategorySchema;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;

/**
 * Class Category
 * Exports the translation of the categories
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Attribute
 */
class Category extends AttributeHandler
    implements DocPropertiesHandlerInterface, AttributeHandlerInterface
{

    use DocPropertiesTrait;
    use DocHandlerTrait;

    /**
     * @var StringLocalized
     */
    protected $localizedStringBuilder;

    public function __construct(
        Connection $connection,
        StringLocalized $localizedStringBuilder
    ){
        $this->localizedStringBuilder = $localizedStringBuilder;
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
        $propertyName = "categories";
        $content[$propertyName] = [];
        foreach($this->getData($propertyName) as $item)
        {
            $schema = [];
            $schema['numerical'] = false;
            $schema['value_id'] = $item[$this->getInstantUpdateIdField()];
            foreach(array_filter(explode("|", $item['parent_value_ids'] ?? "")) as $parentId)
            {
                $schema['parent_value_ids'][] = $parentId;
            }

            foreach($this->getConfiguration()->getLanguages() as $language)
            {
                $localized = new Localized();
                $localized->setValue($item[$language])->setLanguage($language);
                $schema['value_label'][] = $localized;
            }


            $content[$propertyName][] = $schema;
        }

        return $content;
    }

    /**
     * the category labels must be exported as well
     * and the links to the parent IDs
     * fields: value_id, label, parent_value_ids
     */
    public function getQuery()
    {
        $rootCategoryId = $this->getConfiguration()->getNavigationCategoryId();
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from("category")
            ->rightJoin('category', '( ' . $this->getLocalizedCategoryQuery()->__toString() . ') ',
                'translations', 'translations.category_id = category.id')
            ->andWhere('category.version_id = :categoryLiveVersion')
            ->andWhere('category.path LIKE :rootCategoryId OR LOWER(HEX(category.id))=:root')
            ->addGroupBy("category.id")
            ->setParameter('root', $rootCategoryId, ParameterType::STRING)
            ->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter('rootCategoryId', "%|$rootCategoryId|%", ParameterType::STRING)
            ->setParameter('productLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('categoryLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    protected function getFields() : array
    {
        $translationFields = preg_filter('/^/', 'translations.', $this->getConfiguration()->getLanguages());
        return array_merge($translationFields,
            [
                'LOWER(HEX(category.id)) AS ' . $this->getInstantUpdateIdField(),
                "LOWER(HEX(category.parent_id)) AS parent_value_ids"
            ]
        );
    }

    protected function getLocalizedCategoryQuery()
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getLocalizedFields())
            ->from("product_category_tree")
            ->leftJoin('product_category_tree', '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                'translation', 'translation.category_id = product_category_tree.category_id  AND product_category_tree.category_version_id = translation.category_version_id')
            ->andWhere('product_category_tree.category_version_id = :categoryLiveVersion')
            ->andWhere('product_category_tree.product_version_id = :productLiveVersion')
            ->andWhere('product_category_tree.product_id IN (:ids)')
            ->addGroupBy("product_category_tree.category_id");
        #->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
        #->setParameter('productLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
        #->setParameter('categoryLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    protected function getLocalizedFields() : array
    {
        $translationFields = preg_filter('/^/', 'translation.', $this->getConfiguration()->getLanguages());
        return array_merge($translationFields,
            [
                'product_category_tree.category_id'
            ]
        );
    }

    /**
     * Accessing category name translation (name)
     * If there is no translation available, the default one is used
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     * @throws \Exception
     */
    protected function getLocalizedFieldsQuery() : QueryBuilder
    {
        return $this->localizedStringBuilder->getLocalizedFields('category_translation','category_id', 'category_id',
            'category_version_id','name',['category_translation.category_id', 'category_translation.category_version_id'],
            $this->getConfiguration()->getLanguagesMap(), $this->getConfiguration()->getDefaultLanguageId()
        );
    }

}
