<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\User;

use Boxalino\DataIntegrationDoc\Service\Doc\User;
use Boxalino\DataIntegrationDoc\Service\Generator\DocGeneratorInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocUserHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocUser;

/**
 * Class DocHandler
 * Generates the content for the doc_user document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252182638/doc_user
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class DocHandler extends DocUser
    implements DocUserHandlerInterface
{

    use IntegrationDocHandlerTrait;

    /**
     * @return string
     */
    public function getDocContent(): string
    {
        /** @var User | DocHandlerInterface $doc */
        $doc = $this->getDocSchemaGenerator();
        $this->addDocLine($doc);
        return parent::getDocContent();
    }


}
