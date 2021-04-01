<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document;

use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\UserIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

/**
 * Class OrderIntegrationHandler
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class UserIntegrationHandler extends IntegrationHandler
    implements IntegrationDocHandlerInterface, UserIntegrationHandlerInterface
{

    use IntegrationDocHandlerTrait;

    /**
     * @return \ArrayIterator
     */
    public function getDocs(): \ArrayIterator
    {
        $this->addPropertiesOnHandlers();
        return parent::getDocs();
    }

    /**
     * @return string
     */
    public function getIntegrationStrategy(): string
    {
        return UserIntegrationHandlerInterface::INTEGRATION_MODE;
    }

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return UserIntegrationHandlerInterface::INTEGRATION_TYPE;
    }


}
