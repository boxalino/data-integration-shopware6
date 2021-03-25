<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Document\Attribute\Value\DocAttributeValueTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegration\Service\Util\ShopwareMediaTrait;
use Boxalino\DataIntegration\Service\Util\ShopwarePropertyTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Boxalino\Exporter\Service\Component\ProductComponentInterface;
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
class Tag extends IntegrationSchemaPropertyHandler
{

    use DocAttributeValueTrait;

    /**
     * Structure: [property-name => [$schema, $schema], property-name => [], [..]]
     *
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach($this->getData() as $item)
        {
            $content["tag"][] = $this->initializeSchemaForRow($item);
        }

        return $content;
    }

    /**
     * Get the options translation per property group
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $fields = array_merge(
            ["LOWER(HEX(id)) AS {$this->getDiIdField()}"],
            preg_filter('/^/', 'name AS ', $this->getConfiguration()->getLanguages())
        );

        $query = $this->connection->createQueryBuilder();
        $query->select($fields)
            ->from("tag");

        return $query;
    }


}
