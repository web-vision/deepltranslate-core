<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Domain\Repository\PageRepository;
use WebVision\Deepltranslate\Core\Exception\InvalidArgumentException;
use WebVision\Deepltranslate\Core\Exception\LanguageIsoCodeNotFoundException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\LanguageService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;

abstract class AbstractTranslateHook
{
    protected DeeplService $deeplService;

    protected PageRepository $pageRepository;

    protected LanguageService $languageService;
    protected ProcessingInstruction $processingInstruction;

    public function __construct(
        PageRepository $pageRepository,
        DeeplService $deeplService,
        LanguageService $languageService,
        ProcessingInstruction $processingInstruction
    ) {
        $this->deeplService = $deeplService;
        $this->pageRepository = $pageRepository;
        $this->languageService = $languageService;
        $this->processingInstruction = $processingInstruction;
    }

    /**
     * These logics were outsourced to test them and later to resolve them in a service
     *
     * @deprecated Please use this function @see DeeplService::translateContent()
     */
    public function translateContent(
        string $content,
        string $sourceLanguageIsocode,
        string $targetLanguageIsocode
    ): string {
        return $this->deeplService->translateRequest(
            $content,
            $targetLanguageIsocode,
            $sourceLanguageIsocode
        );
    }

    /**
     * @internal
     *
     * @throws LanguageRecordNotFoundException
     * @throws LanguageIsoCodeNotFoundException
     */
    protected function createTranslateContext(string $content, int $targetLanguageUid, Site $site): TranslateContext
    {
        $context = new TranslateContext($content);

        $sourceLanguageRecord = $this->languageService->getSourceLanguage($site);

        $context->setSourceLanguageCode($sourceLanguageRecord['languageCode']);

        try {
            $targetLanguageRecord = $this->languageService->getTargetLanguage($site, $targetLanguageUid);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    'The target language is not DeepL supported. Possibly wrong Site configuration. Message: %s',
                    $e->getMessage(),
                ),
                1746962367,
                $e,
            );
        }

        $context->setTargetLanguageCode($targetLanguageRecord['languageCode']);
        if (
            $targetLanguageRecord['formality'] !== ''
            && $this->deeplService->hasLanguageFormalitySupport($targetLanguageRecord['languageCode'])
        ) {
            $context->setFormality($targetLanguageRecord['formality']);
        }

        return $context;
    }

    protected function findCurrentParentPage(string $tableName, int $currentRecordId): int
    {
        if ($tableName === 'pages') {
            $pageId = $currentRecordId;
        } else {
            /** @var array{pid: int|string} $currentPageRecord */
            $currentPageRecord = BackendUtility::getRecord($tableName, $currentRecordId);
            $pageId = (int)$currentPageRecord['pid'];
        }

        return $pageId;
    }

    protected function flashMessages(string $message, string $title, ContextualFeedbackSeverity $severity): void
    {
        if (Environment::isCli() || Environment::getContext()->isTesting()) {
            return;
        }

        $flashMessage = new FlashMessage($message, $title, $severity);
        GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->addMessage($flashMessage);
    }

    /**
     * @param string $id
     * @param mixed $value
     * @param int $pasteUpdate
     */
    public function processCmdmap(
        string $command,
        string $table,
        $id,
        $value,
        bool &$commandIsProcessed,
        DataHandler $dataHandler,
        $pasteUpdate
    ): void {
        if ($command !== 'deepltranslate' || $commandIsProcessed !== false) {
            return;
        }
        $this->processingInstruction->setProcessingInstruction($table, $id, true);

        // Following lines are copied from `DataHandler::process_cmdmap()` from 'localize' command switch. Property
        // is protected and the reason we need to use PHP powerfull reflection API to set the wanted value.
        $dataHandlerPropertyReflection = (new \ReflectionProperty($dataHandler, 'useTransOrigPointerField'));
        $backupUseTransOrigPointerField = $dataHandlerPropertyReflection->getValue($dataHandler);
        $dataHandlerPropertyReflection->setValue($dataHandler, true);
        $dataHandler->localize($table, (int)$id, $value);
        $dataHandlerPropertyReflection->setValue($dataHandler, $backupUseTransOrigPointerField);

        $commandIsProcessed = true;
    }
}
