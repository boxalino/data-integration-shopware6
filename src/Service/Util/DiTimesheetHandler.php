<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util;

use Boxalino\DataIntegrationDoc\Service\Integration\Mode\DeltaIntegrationInterface;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;

/**
 * DiTimesheetHandlerInterface service
 *
 * @package Boxalino\DataIntegration\Service\Util
 */
class DiTimesheetHandler implements DiTimesheetHandlerInterface
{

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(
        Connection $connection
    ){
        $this->connection = $connection;
    }

    /**
     * @param string $type
     * @param string $accountName
     * @param string $mode
     * @param string $diHandlerTime
     * @throws \Doctrine\DBAL\DBALException
     */
    public function timesheet(string $type, string $accountName, string $mode, string $diHandlerTime) : void
    {
        $tableName = $this->getDiTimesheetTableName();
        $query=<<<SQL
# boxalino::di::timesheet::insert
INSERT INTO $tableName (account, type, mode, run_at, updated_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE run_at = '$diHandlerTime', updated_at=NOW();
SQL;

        $this->connection->executeUpdate($query, [$accountName, $type, $mode, $diHandlerTime]);
    }

    /**
     * @param string $accountName
     * @param string $type
     * @param string $mode
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDiTimesheetRunAtByAccountTypeMode(string $accountName, string $type, string $mode) : string
    {
        /** @var STRING $query for instant timesheet check - it is sufficient to get the last run_at for the account */
        $query = <<<SQL
# boxalino::di::timesheet::run_at
SELECT MAX(run_at) FROM (
    SELECT run_at  FROM {$this->getDiTimesheetTableName()} WHERE account = "{$accountName}" AND type="$type"
    UNION SELECT NOW() - INTERVAL 1 DAY AS run_at
) min_run_at_select;
SQL;

        if(in_array($mode, [DeltaIntegrationInterface::INTEGRATION_MODE]))
        {
            $query = <<<SQL
# boxalino::di::timesheet::run_at
SELECT MAX(min_run_at) FROM (
    SELECT MIN(run_at) AS min_run_at FROM {$this->getDiTimesheetTableName()} WHERE account = "{$accountName}" AND type="$type" AND mode <> "I" GROUP BY mode
    UNION SELECT NOW() - INTERVAL 1 DAY AS min_run_at
) min_run_at_select;
SQL;
        }

        return $this->connection->fetchColumn($query);
    }

    /**
     * Decide the logic for cleaning out the di_flagged_id_<type> details based on succesfull sync times for the accounts
     *
     * @param string $accountName
     * @param string $type
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDiFlaggedIdDeleteConditionalByAccountType(string $accountName, string $type) : string
    {
        $conditional = "account <> '{$accountName}' AND ";
        if($this->isSingleAccountSetup($accountName))
        {
            $conditional = "";
        }

        $query = <<<SQL
# boxalino::di::timesheet::delete_flagged_ids
SELECT MAX(min_run_at) FROM (
    SELECT MIN(run_at) AS min_run_at FROM {$this->getDiTimesheetTableName()} WHERE $conditional type="$type" AND mode <> "I" GROUP BY mode
    UNION SELECT NOW() - INTERVAL 1 DAY AS min_run_at
) min_run_at_select;
SQL;

        return $this->connection->fetchColumn($query);
    }

    /**
     * @param string $accountName
     * @return bool
     */
    public function isSingleAccountSetup(string $accountName) : bool
    {
        $query = <<<SQL
# boxalino::di::timesheet::singleaccount
SELECT COUNT(DISTINCT(account)) FROM {$this->getDiTimesheetTableName()} WHERE account <> "{$accountName}";
SQL;
        if((int) $this->connection->fetchColumn($query) > 0)
        {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getFallbackRunAt() : string
    {
        return (new \DateTime())->modify("-1 day")->format(Defaults::STORAGE_DATE_TIME_FORMAT);
    }

    /**
     * @return string
     */
    public function getDiTimesheetTableName() : string
    {
        return DiTimesheetHandlerInterface::ENTITY_NAME;
    }


}
