<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegration\Service\Util\DiFlaggedIdHandlerInterface;
use Boxalino\DataIntegration\Service\Util\DiTimesheetHandlerInterface;
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


}
