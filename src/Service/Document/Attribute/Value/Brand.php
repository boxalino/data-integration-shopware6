<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegration\Service\Util\ShopwareMediaTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
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
use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;

/**
 * Class Brand
 * Export manufacturer information available in the database
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute\Value
 */
class Brand extends IntegrationSchemaPropertyHandler
{
    use ShopwareMediaTrait;
    use DocAttributeValueTrait;
    use ShopwareLocalizedTrait;

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
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $content[DocSchemaInterface::FIELD_BRANDS] = [];
        foreach ($this->getData(DocSchemaInterface::FIELD_BRANDS) as $item)
        {
            $schema = $this->initializeSchemaForRow($item);

            // adding  name
            $name = $this->getLocalizedPropertyById($item[$this->getDiIdField()], "name");
            $schema = $this->addingPropertyToSchema(DocSchemaInterface::FIELD_VALUE_LABEL, $schema, $name);

            // adding brand description
            $description = $this->getLocalizedPropertyById($item[$this->getDiIdField()], "description");
            $schema = $this->addingPropertyToSchema(DocSchemaInterface::FIELD_DESCRIPTION, $schema, $description);

            $content[DocSchemaInterface::FIELD_BRANDS][] = $schema;
        }

        return $content;
    }

    /**
     * Main query for all brands select
     *
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            "LOWER(HEX(product_manufacturer.id)) AS {$this->getDiIdField()}",
            "product_manufacturer.link AS " . DocSchemaInterface::FIELD_LINK,
            "LOWER(HEX(product_manufacturer.media_id)) AS " . DocSchemaInterface::FIELD_IMAGES
        ])
            ->from('product_manufacturer')
            ->andWhere('product_manufacturer.version_id = :live')
            ->addGroupBy('product_manufacturer.id')
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * Generic accessor for the localized fields
     *
     * @param string $propertyName
     * @throws \Exception
     */
    public function getLocalizedQueryResults(string $propertyName) : array
    {
        $this->setPrefix(DocSchemaInterface::FIELD_BRANDS);

        $groupBy = "$this->prefix.product_manufacturer_id";
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields($groupBy))
            ->from('( ' . $this->getLocalizedFieldsQuery($propertyName)->__toString() . ')', $this->prefix)
            ->andWhere("$this->prefix.product_manufacturer_version_id = :live")
            ->addGroupBy($groupBy)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query->execute()->fetchAll();
    }

    /**
     * Prepare brand translations (name, description)
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    public function getLocalizedFieldsQuery(string $propertyName) : QueryBuilder
    {
        return $this->localizedStringBuilder->getLocalizedFields('product_manufacturer_translation', 'product_manufacturer_id',
            'product_manufacturer_id','product_manufacturer_version_id', $propertyName,
            ['product_manufacturer_translation.product_manufacturer_id', 'product_manufacturer_translation.product_manufacturer_version_id'],
            $this->getConfiguration()->getLanguagesMap(), $this->getConfiguration()->getDefaultLanguageId()
        );
    }

}
