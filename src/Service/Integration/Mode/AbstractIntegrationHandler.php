<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegration\Service\Util\DiFlaggedIdHandlerInterface;
use Boxalino\DataIntegration\Service\Util\DiTimesheetHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\StopSyncException;
use Doctrine\DBAL\Connection;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;

/**
 * @package Boxalino\DataIntegration\Service\Integration\Mode
 */
abstract class AbstractIntegrationHandler extends IntegrationHandler
{

    use IntegrationDocHandlerTrait;

    /** @var DiTimesheetHandlerInterface */
    protected $diTimesheetService;

    /** @var DiFlaggedIdHandlerInterface */
    protected $diFlaggedService;

    public function __construct(
        Connection $connection,
        DiTimesheetHandlerInterface $diTimesheet,
        DiFlaggedIdHandlerInterface $diFlagged
    ){
        $this->diFlaggedService = $diFlagged;
        $this->diTimesheetService = $diTimesheet;
        $this->connection = $connection;

        parent::__construct();
    }

    /**
     * @return string
     */
    abstract public function getEntityName() : string;

    /**
     * Clear the ids that have been synced
     */
    public function clearDiFlaggedIds(): void
    {
        try{
            $this->diFlaggedService->deleteFlaggedIdsByEntityNameAndDate(
                $this->getEntityName(),
                $this->diTimesheetService->getDiFlaggedIdDeleteConditionalByAccountType(
                    $this->getDiConfiguration()->getAccount(),
                    $this->getEntityName()
                )
            );
        } catch(\Throwable $exception)
        {
            throw $exception;
        }
    }

    /**
     * Adds a run time that the DI timesheet has run successfully
     */
    public function updateDiTimesheet(): void
    {
        try{
            $this->diTimesheetService->timesheet(
                $this->getEntityName(),
                $this->getDiConfiguration()->getAccount(),
                $this->getDiConfiguration()->getMode(),
                $this->getHandlerIntegrateTime()
            );
        } catch(\Throwable $exception)
        {
            throw $exception;
        }
    }

    /**
     * Review if the integrate run (data export trigger) is allowed
     * It can be limited by a scheduler configuration or some other custom logic
     *
     * @return bool
     */
    public function isIntegrateAllowed() : bool
    {
        if($this->getSystemConfiguration()->getSchedulerEnabled())
        {
            $dailyStart = $this->getSystemConfiguration()->getSchedulerDailyStart();
            $dailyEnd = $this->getSystemConfiguration()->getSchedulerDailyEnd();
            $currentStoreTime = (new \DateTime())->format("H");
            if($dailyEnd < $dailyStart)
            {
                $dailyEnd+=24;
            }

            if($currentStoreTime === min(max($currentStoreTime, $dailyStart), $dailyEnd))
            {
                return true;
            }

            throw new StopSyncException(
                "DI integration stopped: the current time $currentStoreTime is outside the time range allowed via scheduler (from $dailyStart to $dailyEnd)"
            );
        }

        return true;
    }


}
