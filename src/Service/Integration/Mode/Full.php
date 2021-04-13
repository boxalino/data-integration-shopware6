<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegrationDoc\Service\Integration\Mode\FullIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

abstract class Full extends IntegrationHandler
    implements IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;
    use FullIntegrationTrait;

    public function integrate(): void
    {
        $this->addSystemConfigurationOnHandlers();
        $this->integrateFull();
    }


}
