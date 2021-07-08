<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Util\DiFlaggedIdsTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Mode\FullIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;

abstract class Full extends AbstractIntegrationHandler
    implements IntegrationDocHandlerInterface
{
    use FullIntegrationTrait;

    public function integrate(): void
    {
        $this->setHandlerIntegrateTime((new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT));
        $this->addSystemConfigurationOnHandlers();
        $this->integrateFull();
        $this->updateDiTimesheet();
        $this->clearDiFlaggedIds();
    }


}
