<?php
namespace Boxalino\DataIntegration\Service\Util;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\EmptyMediaIdException;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Collection;

/**
 * Trait for accessing Shopware Media content
 *
 * @package Boxalino\DataIntegration\Service\Util
 */
trait ShopwareMediaTrait
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $mediaUrlGenerator;

    /**
     * @var EntityRepository
     */
    protected $mediaRepository;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Collection
     */
    protected $mediaCollection;

    /**
     * @param string|null $mediaId
     * @return string|null
     */
    public function getImageByMediaId(?string $mediaId) : string
    {
        $image = "";
        $mediaItem = null;
        try{
            if($this->mediaCollection)
            {
                if($this->mediaCollection->has($mediaId))
                {
                    $mediaItem = $this->mediaCollection->get($mediaId);
                }
            }
            /** @var MediaEntity $media */
            $media = $mediaItem ?? $this->mediaRepository->search(new Criteria([$mediaId]), $this->context)->get($mediaId);
            $image = $this->mediaUrlGenerator->getAbsoluteMediaUrl($media);
        } catch(EmptyMediaFilenameException $exception)
        {
            if($this->getSystemConfiguration()->isTest())
            {
                $this->logger->info("Shopware: Media Path Export not available for media ID $mediaId: " . $exception->getMessage());
            }
        } catch(EmptyMediaIdException $exception)
        {
            if($this->getSystemConfiguration()->isTest())
            {
                $this->logger->info("Shopware: Media Path Export not available for media ID $mediaId: " . $exception->getMessage());
            }
        } catch(\Exception $exception)
        {
            if($this->getSystemConfiguration()->isTest())
            {
                $this->logger->info("Shopware: Media Path Export not available for media ID $mediaId: " . $exception->getMessage());
            }
        }

        return $image;
    }


}
