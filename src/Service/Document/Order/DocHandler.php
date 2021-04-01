<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Order;
use Boxalino\DataIntegrationDoc\Service\Generator\DocGeneratorInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocOrderHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocOrder;
use Psr\Log\LoggerInterface;

/**
 * Class DocHandler
 * Generates the content for the doc_order document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313666/doc+order
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class DocHandler extends DocOrder
    implements DocOrderHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getDoc(): string
    {
        if(empty($this->docs))
        {
            $this->generateDocData();
            $this->createDocLines();
        }
        return parent::getDoc();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        try {
            foreach($this->getDocData() as $id=>$content)
            {
                /** @var Order | DocHandlerInterface $doc */
                $doc = $this->getDocPropertySchema(DocOrderHandlerInterface::DOC_TYPE, $content);
                $doc->setCreationTm(date("Y-m-d H:i:s"));

                $this->addDocLine($doc);
            }

        } catch (\Throwable $exception)
        {
            $this->logger->info($exception->getMessage());
        }

        return $this;
    }


    /**
     * Create the product groups content (based on the IDs to be updated)
     *
     * @return array
     */
    public function generateDocData() : array
    {
        $this->addPropertiesOnHandlers();
        return parent::generateDocData();
    }

}
