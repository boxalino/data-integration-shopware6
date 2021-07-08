<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Util\DiFlaggedIdHandlerInterface;
use Boxalino\DataIntegration\Service\Util\DiFlaggedIdsTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegrationDoc\Service\Integration\Mode\InstantIntegrationTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;

/**
 * Class Instant
 *
 * It is used to update elements (status, properties, etc)
 * It does not remove existing items
 * It does not add new items
 *
 * @package Boxalino\DataIntegration\Service\Integration\Mode
 */
abstract class Instant extends AbstractIntegrationHandler
    implements IntegrationDocHandlerInterface
{
    use InstantIntegrationTrait;

    public function integrate(): void
    {
        $this->setHandlerIntegrateTime((new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT));
        $this->addSystemConfigurationOnHandlers();
        $this->integrateInstant();
        $this->updateDiTimesheet();
    }

    /**
     * Access the saved IDs from the entity repository
     *
     * @return array
     */
    public function getIds(): array
    {
        if(empty($this->ids))
        {
            $this->ids = $this->diFlaggedService->getFlaggedIdsByEntityNameAndDateFromTo(
                $this->getEntityName(),
                $this->diTimesheetService->getDiTimesheetRunAtByAccountTypeMode(
                    $this->getDiConfiguration()->getAccount(),
                    $this->getEntityName(),
                    $this->getIntegrationType()
                ),
                $this->getHandlerIntegrateTime()
            );
        }

        return $this->ids;
    }


}
