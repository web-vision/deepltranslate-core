<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * This class defines instructions, how to work within DeepL translations
 * @internal No public API
 */
#[Autoconfigure(public: true)]
final class ProcessingInstruction
{
    protected const PROCESSING_CACHE_IDENTIFIER = 'deepl-processing-cache';
    private FrontendInterface $runtimeCache;

    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        FrontendInterface $runtimeCache
    ) {
        $this->runtimeCache = $runtimeCache;
    }

    /**
     * @param int|string|null $id
     * @todo harden deeplMode being a pure boolean
     * @param string|bool $deeplMode
     */
    public function setProcessingInstruction(?string $table = null, $id = null, $deeplMode = false): void
    {
        $processingInformation = [
            'tableName' => $table,
            'id' => $id,
            'deeplMode' => $deeplMode,
        ];
        // if processing instructions are already set, detect the current DeepL mode.
        // this is needed for sub instances of DataHandler, mostly
        // when translating inline elements via command and the DataMapProcessor
        // manually triggers an inline translation
        // which leads to loss of deepl mode information from original request
        if ($this->runtimeCache->has(self::PROCESSING_CACHE_IDENTIFIER)) {
            $processingInformation['deeplMode'] = $this->isDeeplMode();
        }
        $this->runtimeCache->set(self::PROCESSING_CACHE_IDENTIFIER, $processingInformation);
    }

    /**
     * @return array{
     *     tableName: ?string,
     *     id: int|string|null,
     *     deeplMode: bool|string
     * }
     */
    public function getProcessingInstruction(): array
    {
        if (!$this->runtimeCache->has(self::PROCESSING_CACHE_IDENTIFIER)) {
            return [
                'tableName' => null,
                'id' => null,
                'deeplMode' => false,
            ];
        }
        return $this->runtimeCache->get(self::PROCESSING_CACHE_IDENTIFIER);
    }

    public function isDeeplMode(): bool
    {
        $processingInstructions = $this->getProcessingInstruction();

        return $processingInstructions['deeplMode'] === 'deepl' || $processingInstructions['deeplMode'] === true;
    }

    public function getProcessingTable(): ?string
    {
        $processingInstructions = $this->getProcessingInstruction();

        return $processingInstructions['tableName'];
    }

    /**
     * @return int|string|null
     */
    public function getProcessingId()
    {
        $processingInstructions = $this->getProcessingInstruction();

        return $processingInstructions['id'];
    }
}
