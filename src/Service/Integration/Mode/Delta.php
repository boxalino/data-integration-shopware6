<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegrationDoc\Service\Integration\Mode\DeltaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

abstract class Delta extends IntegrationHandler
    implements IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;
    use DeltaIntegrationTrait;

    public function integrate(): void
    {
        $this->addSystemConfigurationOnHandlers();
        $this->integrateDelta();
    }


}
