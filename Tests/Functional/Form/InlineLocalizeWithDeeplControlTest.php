<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Controller\SimpleDataHandlerController;
use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent;
use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementEnabledControlsEvent;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Event\Listener\InlineLocalizeWithDeeplControlEventListener;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * The "Translate with DeepL" control offered for inline children inside a translated parent record,
 * rendered through the real FormEngine of the translated parent (uid 2, language 2).
 *
 * Records uid 3 and 4 of `tx_testinlinerelations_child_declared` are children added in the default
 * language after the parent (uid 1) and its first child (uid 1) have already been translated. Record
 * uid 5 is listed in the comma separated inline field `children_list` of the parent.
 */
final class InlineLocalizeWithDeeplControlTest extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    private const CONTROL_CLASS = 't3js-deepltranslate-inline-localize';

    private const CORE_LOCALIZE_CLASS = 't3js-synchronizelocalize-button';

    /**
     * `R_URI` of the core `EditDocumentController` for the translated parent, opened with the "edit column"
     * link of the page module, which adds the page title. A title with a space or an umlaut made an
     * absolute redirect fail the validation of `tce_db`.
     */
    private const EDIT_FORM_URI = '/typo3/record/edit?edit%5Btx_testinlinerelations_parent%5D%5B2%5D=edit&recTitle=%C3%9Cber%20uns&returnUrl=%2Ftypo3%2Fmodule%2Fweb%2Flayout%3Fid%3D1';

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF-8',
            'iso' => 'en',
            'hrefLang' => 'en-US',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => '',
            ],
        ],
        'DE' => [
            'id' => 2,
            'title' => 'Deutsch',
            'locale' => 'de_DE',
            'iso' => 'de',
            'hrefLang' => 'de-DE',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'DE',
            ],
        ],
        'DE_WITHOUT_DEEPL' => [
            'id' => 2,
            'title' => 'Deutsch',
            'locale' => 'de_DE',
            'iso' => 'de',
            'hrefLang' => 'de-DE',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => '',
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = __DIR__ . '/../Fixtures/Extensions/test_inline_relations';

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineLocalizeWithDeeplControl.csv');
    }

    public static function controlVisibilityDataProvider(): \Generator
    {
        yield 'admin, target language with DeepL target language' => [
            'backendUserUid' => 1,
            'targetLanguagePreset' => 'DE',
            'expectedControlChildUids' => [3, 4],
        ];
        yield 'editor with DeepL translate permission' => [
            'backendUserUid' => 2,
            'targetLanguagePreset' => 'DE',
            'expectedControlChildUids' => [3, 4],
        ];
        yield 'editor without DeepL translate permission' => [
            'backendUserUid' => 3,
            'targetLanguagePreset' => 'DE',
            'expectedControlChildUids' => [],
        ];
        yield 'target language without DeepL target language' => [
            'backendUserUid' => 1,
            'targetLanguagePreset' => 'DE_WITHOUT_DEEPL',
            'expectedControlChildUids' => [],
        ];
    }

    /**
     * Core's own localize button is asserted as well, so a case expecting no DeepL control cannot pass
     * just because the inline field or the not yet localized child was not rendered at all.
     *
     * @param non-empty-string $targetLanguagePreset
     * @param int[] $expectedControlChildUids
     */
    #[Test]
    #[DataProvider('controlVisibilityDataProvider')]
    public function controlIsOnlyOfferedForNotLocalizedChildWhenDeeplTranslationIsPossible(
        int $backendUserUid,
        string $targetLanguagePreset,
        array $expectedControlChildUids,
    ): void {
        $this->writeSite($targetLanguagePreset);
        $this->setUpBackendUserWithLanguageService($backendUserUid);

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 2);

        $this->assertNotNull($this->findCoreLocalizeButton($html, 3), 'Core localize button of the not yet localized child is missing.');
        $this->assertSame($expectedControlChildUids, array_keys($this->findControls($html)));
    }

    public static function disabledLocalizeControlDataProvider(): \Generator
    {
        yield 'false' => [false];
        yield 'integer 0' => [0];
        yield 'string "0"' => ['0'];
        yield 'empty string' => [''];
    }

    /**
     * Core casts `appearance.enabledControls` to boolean, every falsy value removes its localize control.
     */
    #[Test]
    #[DataProvider('disabledLocalizeControlDataProvider')]
    public function controlIsNotOfferedWhenLocalizeControlIsDisabled(mixed $enabled): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);
        $GLOBALS['TCA']['tx_testinlinerelations_parent']['columns']['children_declared']['config']['appearance']['enabledControls']['localize'] = $enabled;

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 2);

        $this->assertStringContainsString('data-object-id="data-1-tx_testinlinerelations_parent-2-children_declared-tx_testinlinerelations_child_declared-3"', $html, 'The not yet localized child is not rendered.');
        $this->assertNull($this->findCoreLocalizeButton($html, 3), 'Core still renders its localize button.');
        $this->assertSame([], $this->findControls($html));
    }

    /**
     * A listener of `ModifyInlineElementEnabledControlsEvent` removes the localize control of core for the
     * element, the DeepL control has to follow it. The listener of the extension is called directly with an
     * event dispatcher that adds such a listener.
     */
    #[Test]
    public function controlIsNotOfferedWhenLocalizeControlIsDisabledByListener(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);
        $eventDispatcher = $this->get(EventDispatcherInterface::class);
        $disablingEventDispatcher = new class ($eventDispatcher) implements EventDispatcherInterface {
            public function __construct(private readonly EventDispatcherInterface $eventDispatcher) {}

            public function dispatch(object $event): object
            {
                $event = $this->eventDispatcher->dispatch($event);
                if ($event instanceof ModifyInlineElementEnabledControlsEvent) {
                    $event->disableControl('localize');
                }

                return $event;
            }
        };

        $this->assertTrue($this->dispatchControlsEventToListener($eventDispatcher, 'children_declared')->hasControl('deepltranslate'), 'Control is not offered without the disabling listener.');
        $this->assertFalse($this->dispatchControlsEventToListener($disablingEventDispatcher, 'children_declared')->hasControl('deepltranslate'));
    }

    /**
     * The child is rendered in a field its inline relation does not belong to. The translation would be
     * attached through the other field, not to the one shown.
     */
    #[Test]
    public function controlIsNotOfferedWhenChildIsShownInFieldItDoesNotBelongTo(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $this->assertFalse($this->dispatchControlsEventToListener($this->get(EventDispatcherInterface::class), 'children_declared_ambiguous')->hasControl('deepltranslate'));
    }

    /**
     * A child of a comma separated inline field is listed in the translated parent and gets the localize
     * button of core, but its translation could not be attached to the translated parent by DeepL.
     */
    #[Test]
    public function controlIsNotOfferedForChildOfInlineFieldWithoutPointerField(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 2);

        $this->assertNotNull($this->findCoreLocalizeButton($html, 5), 'Core localize button of the child in the comma separated field is missing.');
        $this->assertArrayNotHasKey(5, $this->findControls($html));
    }

    /**
     * A translation of the child exists, but it is not attached to the translated parent. Translating the
     * child again would not create anything.
     */
    #[Test]
    public function controlIsNotOfferedForChildWithDetachedTranslation(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('tx_testinlinerelations_child_declared')->insert(
            'tx_testinlinerelations_child_declared',
            ['uid' => 6, 'pid' => 1, 'sorting' => 1280, 'sys_language_uid' => 2, 'l10n_parent' => 4, 'l10n_source' => 4, 'parentid' => 0, 'title' => 'Protonenstrahl']
        );
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 2);

        $this->assertNotNull($this->findCoreLocalizeButton($html, 4), 'Core localize button of the child with a detached translation is missing.');
        $this->assertSame([3], array_keys($this->findControls($html)));
    }

    #[Test]
    public function controlIsNotOfferedForReadOnlyChildTable(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);
        $GLOBALS['TCA']['tx_testinlinerelations_child_declared']['ctrl']['readOnly'] = true;

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 2);

        $this->assertNotNull($this->findCoreLocalizeButton($html, 3), 'Core localize button of the not yet localized child is missing.');
        $this->assertSame([], $this->findControls($html));
    }

    #[Test]
    public function controlIsNotOfferedInDefaultLanguageParent(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 1);

        $this->assertStringContainsString('data-object-id="data-1-tx_testinlinerelations_parent-1-children_declared-tx_testinlinerelations_child_declared-3"', $html, 'Child is not rendered in the default language parent.');
        $this->assertSame([], $this->findControls($html));
    }

    #[Test]
    #[Group('not-core-14')]
    public function controlDispatchesDeeplTranslateCommandBehindConfirmation(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2))[3] ?? null;

        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');
        $this->assertSame('button', $control->tagName, 'A link would run the command without confirmation when opened in a new tab.');
        $this->assertSame('button', $control->getAttribute('type'));
        $this->assertFalse($control->hasAttribute('href'));
        $this->assertSame(
            [
                'tx_testinlinerelations_child_declared' => [
                    3 => [
                        'deepltranslate' => '2',
                    ],
                ],
            ],
            $this->getQueryParameters($control->getAttribute('data-uri'))['cmd'] ?? null
        );
        $this->assertContains('t3js-modal-trigger', explode(' ', $control->getAttribute('class')));
        $this->assertSame('warning', $control->getAttribute('data-severity'));
        $this->assertSame('Translate with DeepL', $control->getAttribute('title'));
        $this->assertSame('Translate with DeepL', $control->getAttribute('data-title'));
        $this->assertStringContainsString('Unsaved changes', $control->getAttribute('data-content'));
        $this->assertSame('Translate with DeepL', $control->getAttribute('data-button-ok-text'));
        $this->assertSame('Cancel', $control->getAttribute('data-button-close-text'));
    }

    /**
     * The edit form hands the URL of the document currently open down to its inline children as
     * `returnUrl`. Core's AJAX controller for inline children hands the same value down
     * (`originalReturnUrl`), that path is not run here.
     */
    #[Test]
    #[Group('not-core-14')]
    public function controlReturnsEditorToDocumentCurrentlyOpen(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2))[3] ?? null;

        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');
        $this->assertSame(
            $this->describeUrl(self::EDIT_FORM_URI),
            $this->describeUrl((string)($this->getQueryParameters($control->getAttribute('data-uri'))['redirect'] ?? ''))
        );
    }

    #[Test]
    #[Group('not-core-14')]
    public function controlIsNotOfferedWithoutDocumentToReturnTo(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 2, '');

        $this->assertNotNull($this->findCoreLocalizeButton($html, 3), 'Core localize button of the not yet localized child is missing.');
        $this->assertSame([], $this->findControls($html));
    }

    /**
     * Follows the link of the rendered control through the core DataHandler route (`tce_db`) exactly as
     * the backend does after the confirmation, and checks the resulting translation of the child.
     */
    #[Test]
    #[Group('not-core-14')]
    public function followingControlCreatesDeeplTranslatedChildAttachedToTranslatedParent(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);
        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2))[3] ?? null;
        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');

        $response = $this->followControl($control);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame($this->describeUrl(self::EDIT_FORM_URI), $this->describeUrl($response->getHeaderLine('location')), 'Editor is not returned to the edit form.');
        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 2);
        $this->assertIsArray($translatedChild, 'New child has not been localized at all.');
        $this->assertSame(2, (int)$translatedChild['parentid'], 'New child translation is not attached to the translated parent.');
        $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], 'New child translation has not been translated by DeepL.');
    }

    /**
     * Two children were added after the parent had been translated. Following the control of one of them
     * must not translate the other one as well.
     */
    #[Test]
    #[Group('not-core-14')]
    public function followingControlTranslatesOnlyTheRequestedOfTwoNewChildren(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);
        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2))[3] ?? null;
        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');

        $this->followControl($control);

        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 2);
        $this->assertIsArray($translatedChild, 'Requested child has not been localized.');
        $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], 'Requested child has not been translated by DeepL.');
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 4, 2), 'Sibling child has been localized as well.');
    }

    #[Test]
    #[Group('not-core-13')]
    public function controlOpensCoreLocalizationWizardForChild(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2))[3] ?? null;

        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');
        $this->assertSame('typo3-backend-localization-button', $control->tagName);
        $this->assertSame('tx_testinlinerelations_child_declared', $control->getAttribute('record-type'));
        $this->assertSame('3', $control->getAttribute('record-uid'));
        $this->assertSame('2', $control->getAttribute('target-language'));
        $this->assertSame('Translate with DeepL', $control->getAttribute('title'));
        $this->assertStringContainsString('actions-localize-deepl-14', (string)$control->ownerDocument?->saveHTML($control));
    }

    /**
     * @param non-empty-string $targetLanguagePreset
     */
    private function writeSite(string $targetLanguagePreset): void
    {
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration($targetLanguagePreset, '/de/', ['EN'], 'strict'),
            ],
        );
    }

    /**
     * FormEngine renders labels through `$GLOBALS['LANG']`, which the backend request middleware
     * provides in a real backend request.
     */
    private function setUpBackendUserWithLanguageService(int $backendUserUid): void
    {
        $this->setUpBackendUser($backendUserUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    private function createBackendRequest(string $pathAndQuery, string $routeIdentifier): ServerRequest
    {
        $request = (new ServerRequest('https://localhost' . $pathAndQuery, 'GET', 'php://input', [], $this->getServerParameters($pathAndQuery)))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route((string)parse_url($pathAndQuery, PHP_URL_PATH), [
                '_identifier' => $routeIdentifier,
                'packageName' => 'typo3/cms-backend',
            ]));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $request;
    }

    /**
     * @return array<string, string>
     */
    private function getServerParameters(string $pathAndQuery): array
    {
        return [
            'REQUEST_URI' => $pathAndQuery,
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/typo3/index.php',
            'SCRIPT_FILENAME' => Environment::getPublicPath() . '/typo3/index.php',
        ];
    }

    /**
     * Compiles and renders the edit form like the core `EditDocumentController`, which passes its own URL as
     * `returnUrl`.
     */
    private function renderEditForm(string $table, int $uid, string $returnUrl = self::EDIT_FORM_URI): string
    {
        $formData = $this->get(FormDataCompiler::class)->compile(
            [
                'request' => $this->createBackendRequest(self::EDIT_FORM_URI, 'record_edit'),
                'tableName' => $table,
                'vanillaUid' => $uid,
                'command' => 'edit',
                'returnUrl' => $returnUrl,
            ],
            GeneralUtility::makeInstance(TcaDatabaseRecord::class)
        );
        $formData['renderType'] = 'fullRecordContainer';

        return (string)$this->get(NodeFactory::class)->create($formData)->render()['html'];
    }

    /**
     * @return array<int, \DOMElement> The controls indexed by the uid of the child they are rendered for
     */
    private function findControls(string $html): array
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);
        $elements = (new \DOMXPath($document))->query(
            sprintf('//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', self::CONTROL_CLASS)
        );
        $controls = [];
        foreach ($elements ?: [] as $element) {
            if ($element instanceof \DOMElement) {
                $controls[$this->resolveChildUid($element)] = $element;
            }
        }

        return $controls;
    }

    /**
     * Calls the listener of the extension for child 3 shown in the given field of the translated parent,
     * with the dependencies of the container and the given event dispatcher.
     */
    private function dispatchControlsEventToListener(EventDispatcherInterface $eventDispatcher, string $parentField): ModifyInlineElementControlsEvent
    {
        $containerListener = $this->get(InlineLocalizeWithDeeplControlEventListener::class);
        $dependency = static fn(string $property): mixed => (new \ReflectionProperty($containerListener, $property))->getValue($containerListener);
        $listener = new InlineLocalizeWithDeeplControlEventListener(
            $dependency('deeplTranslateAvailabilityService'),
            $dependency('controlRenderer'),
            $dependency('inlineRelationResolver'),
            $dependency('recordLocalizationResolver'),
            $eventDispatcher,
        );
        $fieldConfiguration = $GLOBALS['TCA']['tx_testinlinerelations_parent']['columns'][$parentField]['config'];
        $fieldConfiguration['appearance']['enabledControls']['localize'] = true;
        $fieldConfiguration['inline']['parentSysLanguageUid'] = 2;
        $event = new ModifyInlineElementControlsEvent(
            [],
            [
                'isInlineDefaultLanguageRecordInLocalizedParentContext' => true,
                'inlineParentUid' => 2,
                'inlineParentTableName' => 'tx_testinlinerelations_parent',
                'inlineParentFieldName' => $parentField,
                'inlineParentConfig' => $fieldConfiguration,
                'returnUrl' => self::EDIT_FORM_URI,
            ],
            (array)BackendUtility::getRecord('tx_testinlinerelations_child_declared', 3),
        );
        $listener($event);

        return $event;
    }

    private function findCoreLocalizeButton(string $html, int $childUid): ?\DOMElement
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);
        $elements = (new \DOMXPath($document))->query(
            sprintf('//*[contains(concat(" ", normalize-space(@class), " "), " %s ") and @data-type="%d"]', self::CORE_LOCALIZE_CLASS, $childUid)
        );
        $element = $elements !== false ? $elements->item(0) : null;

        return $element instanceof \DOMElement ? $element : null;
    }

    /**
     * Follows the URL of the rendered control through the core DataHandler route (`tce_db`) exactly as the
     * modal trigger of core does after the confirmation.
     */
    private function followControl(\DOMElement $control): ResponseInterface
    {
        // TYPO3 v13 validates the redirect against `$_SERVER` instead of the request.
        $serverBackup = $_SERVER;
        $_SERVER = array_replace($_SERVER, $this->getServerParameters('/typo3/record/commit'));
        try {
            // Not a container service, the backend route dispatcher instantiates it the same way.
            return GeneralUtility::makeInstance(SimpleDataHandlerController::class)->mainAction(
                $this->createBackendRequest('/typo3/record/commit', 'tce_db')
                    ->withQueryParams($this->getQueryParameters($control->getAttribute('data-uri')))
            );
        } finally {
            $_SERVER = $serverBackup;
        }
    }

    private function resolveChildUid(\DOMElement $control): int
    {
        if ($control->hasAttribute('record-uid')) {
            return (int)$control->getAttribute('record-uid');
        }
        $command = $this->getQueryParameters($control->getAttribute('data-uri'))['cmd'] ?? [];
        $recordCommands = is_array($command) ? (array)reset($command) : [];

        return (int)array_key_first($recordCommands);
    }

    /**
     * Path and decoded query parameters of a URL, independent of the host and of how the query is encoded.
     *
     * @return array{0: string, 1: array<array-key, mixed>}
     */
    private function describeUrl(string $url): array
    {
        return [(string)parse_url($url, PHP_URL_PATH), $this->getQueryParameters($url)];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getQueryParameters(string $url): array
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $queryParameters);

        return $queryParameters;
    }

    /**
     * @return array<string, mixed>|false
     */
    private function fetchTranslation(string $table, int $defaultLanguageUid, int $languageId): array|false
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($defaultLanguageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();
    }
}
