<?php
namespace Boxalino\DataIntegration\Service\Util;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Interface DiFlaggedIdManagerInterface
 *
 * The boxalino_di_flagged_id table is a strategy for keeping track of content update (product, order, user)
 * These IDs are being used as filter logic for a) instant b) delta processes
 *
 * It is updated based on the defined subscribers in the Integration Layer
 *
 * The integration team`s duty is to review the default provided samples (from current repository)
 * and update the subscribed to events where it is needed, in order to avoid any conflicts
 *
 * @package Boxalino\DataIntegration\Service
 */
interface DiFlaggedIdHandlerInterface
{

    public const ENTITY_NAME_PREFIX = 'boxalino_di_flagged_id_';


    /***
     * @param string $entityName
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getFlaggedIdsByEntityNameAndDateFromTo(string $entityName, string $dateFrom, string $dateTo) : array;

    /**
     * @param string $entityName
     * @param string $date
     * @return void
     */
    public function deleteFlaggedIdsByEntityNameAndDate(string $entityName, string $date) : void;

    /**
     * @param string $entityName
     * @param array | null $ids
     * @param array | null $salesChannelIds
     */
    public function flag(string $entityName, array $ids = [], array $salesChannelIds = []) : void;


}
