<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Typed\StringAttribute;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class GroupReleaseDate
 * Exports the group release dates (oldest)
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class GroupReleaseDate extends ModeIntegrator
{

    use DeltaInstantTrait;

    PUBLIC CONST FIELD_GROUP_RELEASE_DATE = "di_group_release_date";

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $iterator = $this->getQueryIterator($this->getStatementQuery());
        foreach ($iterator->getIterator() as $item)
        {
            if(is_null($item[$this->getDiIdField()]))
            {
                continue;
            }

            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING] = [];
            }

            if(!empty($item[self::FIELD_GROUP_RELEASE_DATE]))
            {
                /** @var StringAttribute $oldestReleaseDateSchema */
                $oldestReleaseDateSchema = $this->getStringAttributeSchema([$item[self::FIELD_GROUP_RELEASE_DATE]], self::FIELD_GROUP_RELEASE_DATE);
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING][] = $oldestReleaseDateSchema->toArray();
            }
        }

        return $content;
    }

    /**
     * Only take into account the value of active products (children)
     *
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from("(" .$this->getProductQuery()->__toString().")", "product")
            ->where('product.active = 1')
            ->groupBy('product.parent_id')
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter("channelRootCategoryId", $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * @return string[]
     */
    protected function _getQueryFields() : array
    {
        return [
            "parent_id AS {$this->getDiIdField()}",
            "MIN(STR_TO_DATE(release_date, '%Y-%m-%d %H:%i')) AS " . self::FIELD_GROUP_RELEASE_DATE
        ];
    }

    /**
     * @return QueryBuilder
     */
    protected function getProductQuery() : QueryBuilder
    {
        return $this->_getProductQuery($this->getProductFields());
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getProductFields(): array
    {
        return [
            'LOWER(HEX(product.id)) AS ' . $this->getDiIdField(),
            "IF(product.parent_id IS NULL, LOWER(HEX(product.id)), LOWER(HEX(product.parent_id))) AS parent_id",
            "product.updated_at", "product.created_at", "product.active", "product.release_date"
        ];
    }


}
