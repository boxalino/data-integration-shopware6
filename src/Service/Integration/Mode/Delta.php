<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegrationDoc\Service\Integration\Mode\DeltaIntegrationTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Shopware\Core\Defaults;

abstract class Delta extends AbstractIntegrationHandler
    implements IntegrationDocHandlerInterface
{
    use DeltaIntegrationTrait;

    public function integrate(): void
    {
        $this->setHandlerIntegrateTime((new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT));
        $this->addSystemConfigurationOnHandlers();
        $this->integrateDelta();
        $this->updateDiTimesheet();
        $this->clearDiFlaggedIds();
    }


}
