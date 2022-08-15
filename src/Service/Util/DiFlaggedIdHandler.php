<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package Boxalino\DataIntegration\Service\Util
 */
class DiFlaggedIdHandler implements DiFlaggedIdHandlerInterface
{

    use DiFlaggedIdsTrait;

    /**
     * @var string
     */
    protected $handlerIntegrateTime;

    public function __construct(
        Connection $connection
    ){
        $this->connection = $connection;
    }

    /**
     * @return string
     */
    public function getHandlerIntegrateTime(): string
    {
        if(!$this->handlerIntegrateTime)
        {
            $this->setHandlerIntegrateTime((new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT));
        }

        return $this->handlerIntegrateTime;
    }

    /**
     * @param string $handlerIntegrateTime
     */
    public function setHandlerIntegrateTime(string $handlerIntegrateTime) : void
    {
        $this->handlerIntegrateTime = $handlerIntegrateTime;
    }

    /**
     * Flaggs integrated entities for content update
     *
     * @param string $entityName
     * @param array $ids
     * @param array $salesChannelIds
     */
    public function flag(string $entityName, array $ids = [], array $salesChannelIds = []) : void
    {
        $tableName = $this->getDiFlaggedIdTableNameByType($entityName);
        $dataBind = $this->getDataBindByEntityNameIdsSalesChannelIds($ids, $salesChannelIds);

        $queue = new MultiInsertQueryQueue($this->connection, 50, false, true);
        foreach ($dataBind as $insert) {
            $queue->addInsert($tableName, $insert, ['id'=>ParameterType::BINARY]);
        }

        // try batch insert
        try {
            $queue->execute();
        } catch (\Exception $e) {
            $sql = <<<SQL
# boxalino::di::$tableName::insert
INSERT IGNORE INTO `$tableName` (`id`, `entity_id`, `sales_channel_id`, `created_at`) VALUES (:id, :entity_id, :sales_channel_id, :created_at);
SQL;

            // catch deadlock exception and retry with single insert
            $query = new RetryableQuery($this->connection->prepare($sql));

            foreach ($dataBind as $insert) {
                $query->execute($insert);
            }
        }
    }

    /**
     * @param string $entityName
     * @param array $ids
     * @param array|null $salesChannelIds
     * @return array
     */
    protected function getDataBindByEntityNameIdsSalesChannelIds(array $ids, array $salesChannelIds = []) : array
    {
        $dataBind = [];
        foreach($ids as $id)
        {
            if(empty($salesChannelIds))
            {
                $dataBind[] = $this->getDataBindRowForInsert($id);
                continue;
            }

            foreach($salesChannelIds as $salesChannelId)
            {
                $dataBind[] = $this->getDataBindRowForInsert($id, $salesChannelId);
            }
        }

        return $dataBind;
    }

    /**
     * @param string $entityName
     * @param string $id
     * @param string|null $salesChannel
     * @return array
     */
    protected function getDataBindRowForInsert(string $id, ?string $salesChannel = null) : array
    {
        return [
            "id" => Uuid::fromHexToBytes($id),
            "entity_id" => $id,
            "sales_channel_id" => $salesChannel,
            "created_at" => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ];
    }


}
