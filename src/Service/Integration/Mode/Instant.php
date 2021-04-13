<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegrationDoc\Service\Integration\Mode\InstantIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

abstract class Instant extends IntegrationHandler implements IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;
    use InstantIntegrationTrait;

    public function integrate(): void
    {
        $this->addSystemConfigurationOnHandlers();
        $this->integrateInstant();
    }


}
