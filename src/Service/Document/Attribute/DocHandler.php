<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Doc\Attribute;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttribute;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttributeHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaPropertyHandlerInterface;

/**
 * Class DocHandler
 * Generator for the doc_attribute document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252280945/doc+attribute
 *
 * The doc_attribute is exported only for FULL and INSTANT data integrations
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute
 */
class DocHandler extends DocAttribute
    implements DocAttributeHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;

    public function integrate(): void
    {
        if($this->getSystemConfiguration()->isTest())
        {
            $this->getLogger()->info("Boxalino DI: load for {$this->getDocType()}");
        }

        $this->createDocLines();
        parent::integrate();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        try {
            $data = [];
            $this->addSystemConfigurationOnHandlers();
            $this->setLanguages($this->getSystemConfiguration()->getLanguages());
            foreach($this->getHandlers() as $handler)
            {
                if($handler instanceof DocSchemaPropertyHandlerInterface)
                {
                    /** @var Array: [property-name => [$schema, $schema], property-name => [], [..]] $data */
                    foreach($handler->getValues() as $propertyName => $content)
                    {
                        /** @var Attribute | DocHandlerInterface $doc */
                        $doc = $this->getDocSchemaGenerator($content);
                        $doc->setCreationTm(date("Y-m-d H:i:s"));
                        $this->applyPropertyConfigurations($doc);

                        $this->addDocLine($doc);
                    }
                }
            }

            /**
             * other properties outside of product table & properties
             */
            $this->addConfiguredProperties();

        } catch (\Throwable $exception)
        {
            $this->getLogger()->info($exception->getMessage());
        }

        return $this;
    }

}
