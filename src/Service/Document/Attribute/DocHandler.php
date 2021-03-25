<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Attribute;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttribute;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttributeHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandlerInterface;

/**
 * Class DocHandler
 * Generator for the doc_attribute document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252280945/doc+attribute
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute
 */
class DocHandler extends DocAttribute
    implements DocAttributeHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getDoc(): string
    {
        $this->createDocLines();
        return parent::getDoc();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        try {
            $data = [];
            $this->addPropertiesOnHandlers();
            $this->setLanguages($this->getConfiguration()->getLanguages());

            foreach($this->getHandlers() as $handler)
            {
                if($handler instanceof DocSchemaPropertyHandlerInterface)
                {
                    /** @var Array: [property-name => [$schema, $schema], property-name => [], [..]] $data */
                    foreach($handler->getValues() as $propertyName => $content)
                    {
                        /** @var Attribute | DocHandlerInterface $doc */
                        $doc = $this->getDocPropertySchema($this->getDocType(), $content);
                        $doc->setName($propertyName)->setCreationTm(date("Y-m-d H:i:s"));
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
            $this->logger->info($exception->getMessage());
        }

        return $this;
    }

}
