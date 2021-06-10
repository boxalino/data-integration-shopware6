<?php
namespace Boxalino\DataIntegration\Service\Util;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Interface DiFlaggedIdManagerInterface
 *
 * @package Boxalino\DataIntegration\Service
 */
interface DiFlaggedIdHandlerInterface
{

    /**
     * @param string $entityName
     * @return array
     */
    public function getFlaggedIdsByEntityName(string $entityName) : array;

    /**
     * @param string $entityName
     * @param string $date
     * @return array
     */
    public function getFlaggedIdsByEntityNameAndDate(string $entityName, string $date) : array;

    /**
     * @param string $entityName
     * @return void
     */
    public function deleteFlaggedIdsByEntityName(string $entityName) : void;

    /**
     * @param string $entityName
     * @param string $date
     * @return void
     */
    public function deleteFlaggedIdsByEntityNameAndDate(string $entityName, string $date) : void;

    /**
     * @param string $entityName
     * @param string $date
     * @param int $limit
     * @return Criteria
     */
    public function getCriteriaByEntityNameDateAndLimit(string $entityName, string $date, int $limit = 200) : Criteria;



}
