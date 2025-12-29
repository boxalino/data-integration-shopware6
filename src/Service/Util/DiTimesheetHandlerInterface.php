<?php
namespace Boxalino\DataIntegration\Service\Util;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 */
interface DiTimesheetHandlerInterface
{

    public const ENTITY_NAME = 'boxalino_di_timesheet';

    /**
     * @param string $entityName
     * @param string $account
     * @param string $mode
     * @param string $dateFrom
     */
    public function timesheet(string $entityName, string $account, string $mode, string $dateFrom) : void;

    /**
     * @param string $accountName
     * @param string $type
     * @param string $mode
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDiTimesheetRunAtByAccountTypeMode(string $accountName, string $type, string $mode) : string;

    /**
     * Decide the logic for cleaning out the di_flagged_id_<type> details based on succesfull sync times for the accounts
     *
     * @param string $accountName
     * @param string $type
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDiFlaggedIdDeleteConditionalByAccountType(string $accountName, string $type) : string;

}
