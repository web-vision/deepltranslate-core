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
    private const COMMAND_DEPTH_CACHE_IDENTIFIER = 'deepl-processing-command-depth';
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
     * Sets the instruction for the `deepltranslate` command of a record. A command issued while another one is
     * still running, by a DataHandler hook of the running localization for example, gets the instruction of the
     * running command returned, to restore it with {@see self::endCommand()}.
     *
     * @return array{
     *     tableName: ?string,
     *     id: int|string|null,
     *     deeplMode: bool|string
     * }|null the instruction of the running command, null for a command not issued within another one
     */
    public function startCommand(string $table, int|string $id): ?array
    {
        $depth = $this->getCommandDepth();
        // A runtime cache flushed meanwhile holds no instruction to restore.
        $runningInstruction = $depth > 0 && $this->runtimeCache->has(self::PROCESSING_CACHE_IDENTIFIER)
            ? $this->getProcessingInstruction()
            : null;
        $this->runtimeCache->set(self::COMMAND_DEPTH_CACHE_IDENTIFIER, $depth + 1);
        $this->setProcessingInstruction($table, $id, true);
        return $runningInstruction;
    }

    /**
     * Ends a command started with {@see self::startCommand()} and restores the instruction of the command it was
     * issued in. The instruction of a command not issued within another one is kept for the rest of the request, as
     * before.
     *
     * @param array{
     *     tableName: ?string,
     *     id: int|string|null,
     *     deeplMode: bool|string
     * }|null $runningInstruction the return value of {@see self::startCommand()}
     */
    public function endCommand(?array $runningInstruction): void
    {
        $this->runtimeCache->set(self::COMMAND_DEPTH_CACHE_IDENTIFIER, max(0, $this->getCommandDepth() - 1));
        if ($runningInstruction !== null) {
            $this->runtimeCache->set(self::PROCESSING_CACHE_IDENTIFIER, $runningInstruction);
        }
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

    private function getCommandDepth(): int
    {
        $depth = $this->runtimeCache->get(self::COMMAND_DEPTH_CACHE_IDENTIFIER);
        return is_int($depth) ? $depth : 0;
    }
}
