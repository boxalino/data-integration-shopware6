<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document;

use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\NoRecordsFoundException;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Mode\InstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Shopware\Core\Defaults;

/**
 * Trait IntegrationIntegrationDocHandlerTrait
 *
 * @package Boxalino\DataIntegration\Service\Document
 */
trait IntegrationDocHandlerTrait
{

    /**
     * @var ConfigurationDataObject
     */
    protected $systemConfiguration;

    /**
     * @var string
     */
    protected $handlerIntegrateTime;

    /**
     * @return ConfigurationDataObject
     */
    public function getSystemConfiguration(): ConfigurationDataObject
    {
        return $this->systemConfiguration;
    }

    /**
     * @param ConfigurationDataObject $configuration
     * @return void
     */
    public function setSystemConfiguration(ConfigurationDataObject $configuration): void
    {
        $this->systemConfiguration = $configuration;
    }

    /**
     * @return string
     */
    public function getHandlerIntegrateTime(): string
    {
        if(!$this->handlerIntegrateTime)
        {
            $this->setHandlerIntegrateTime((new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT));
        }

        return $this->handlerIntegrateTime;
    }

    /**
     * @param string $handlerIntegrateTime
     */
    public function setHandlerIntegrateTime(string $handlerIntegrateTime) : void
    {
        $this->handlerIntegrateTime = $handlerIntegrateTime;
    }

    /**
     * setIds and setSystemConfiguration to all of the Attribute elements
     * for data access purposes
     */
    public function addSystemConfigurationOnHandlers()
    {
        foreach($this->getHandlers() as $handler)
        {
            if($handler instanceof IntegrationDocHandlerInterface)
            {
                $handler->setSystemConfiguration($this->getSystemConfiguration());
                $handler->setHandlerIntegrateTime($this->getHandlerIntegrateTime());
            }

            try{
                if($handler instanceof DocDeltaIntegrationInterface)
                {
                    if($handler->filterByCriteria())
                    {
                        $handler->setSyncCheck($this->getSyncCheck());
                    }
                }
            } catch (\Throwable $exception)
            {
            }

            try{
                if($handler instanceof DocInstantIntegrationInterface)
                {
                    if($handler->filterByIds())
                    {
                        $handler->setIds($this->getIds());
                    }
                }
            } catch (\Throwable $exception)
            {
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDiIdField() : string
    {
        return DocSchemaInterface::DI_ID_FIELD;
    }

    /**
     * The major items content is integrated in batches
     * due to the big amount of content required for the export
     */
    public function integrate(): void
    {
        try{
            $this->createDocLines();
        } catch (NoRecordsFoundException $exception)
        {
            //logical exception to break the loop
            //reset the docs in case the attributeHandlers were not run in the randomly order
            $this->resetDocs();
        } catch (\Throwable $exception)
        {
            throw $exception;
        }

        /** for instant data integrations - the generic load is sufficient */
        if($this->getSystemConfiguration()->getMode() == InstantIntegrationInterface::INTEGRATION_MODE)
        {
            parent::integrate();
            if($this->getSystemConfiguration()->isTest())
            {
                $this->getLogger()->info("Boxalino DI: load for {$this->getDocType()}");
            }
            return;
        }

        if(count($this->docs))
        {
            $this->integrateByChunk();
            return;
        }

        if($this->getSystemConfiguration()->getChunk())
        {
            $this->loadBq();
            if($this->getSystemConfiguration()->isTest())
            {
                $this->getLogger()->info("Boxalino DI: load for {$this->getDocType()}");
            }
            return;
        }

        throw new FailSyncException("Boxalino Product DI: no {$this->getDocType()} content viable for sync since " . $this->getSyncCheck());
    }

    /**
     * Synchronize content based on the batch size
     */
    public function integrateByChunk()
    {
        $chunk = (int)$this->getSystemConfiguration()->getChunk();
        $document = $this->getDocContent();
        $this->loadByChunk($document);

        $this->getSystemConfiguration()->setChunk($chunk+1);
        $this->integrate();
    }


}
