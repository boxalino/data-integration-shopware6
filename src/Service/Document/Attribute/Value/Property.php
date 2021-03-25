<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Document\Attribute\Value\DocAttributeValueTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegration\Service\Util\ShopwareMediaTrait;
use Boxalino\DataIntegration\Service\Util\ShopwarePropertyTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
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
 * Class Property
 * Exports the translation of the properties available in the eshop
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute\Value
 */
class Property extends IntegrationSchemaPropertyHandler
{

    use ShopwarePropertyTrait;
    use ShopwareMediaTrait;
    use DocAttributeValueTrait;

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
        foreach($this->getPropertyNames() as $property)
        {
            $propertyName = $property['name'];
            $content[$propertyName] = [];
            $this->setPropertyId($property[$this->getDiIdField()]);

            foreach($this->getData($propertyName) as $item)
            {
                $content[$propertyName][] = $this->initializeSchemaForRow($item);
            }
        }

        return $content;
    }

    /**
     * Get the options translation per property group
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $fields = array_merge(
            $this->getFields("property_group_option.id"),
            ["LOWER(HEX(property_group_option.media_id)) AS " . DocSchemaInterface::FIELD_IMAGES]
        );
        $query = $this->connection->createQueryBuilder();
        $query->select($fields)
            ->from("property_group_option")
            ->leftJoin('property_group_option', '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                $this->prefix, "$this->prefix.property_group_option_id = property_group_option.id")
            ->andWhere($this->getLanguageHeaderConditional())
            ->addGroupBy('property_group_option.id');

        if(!is_null($this->propertyId))
        {
            $query->andWhere("LOWER(HEX(property_group_option.property_group_id)) = '$this->propertyId'")
                ->setParameter("propertyGroupId", Uuid::fromHexToBytes($this->propertyId), ParameterType::BINARY);
        }

        return $query;
    }


}
