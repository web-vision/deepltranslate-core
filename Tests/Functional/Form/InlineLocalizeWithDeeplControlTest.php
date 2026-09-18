<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Controller\SimpleDataHandlerController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * The "Localize with DeepL" control offered for inline children inside a translated parent record,
 * rendered through the real FormEngine of the translated parent.
 *
 * Record uid 3 of `tx_testinlinerelations_child_declared` is a child added in the default language
 * after the parent (uid 1) and its first child (uid 1) have already been translated.
 */
final class InlineLocalizeWithDeeplControlTest extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    private const CONTROL_CLASS = 't3js-deepltranslate-inline-localize';

    private const CORE_LOCALIZE_CLASS = 't3js-synchronizelocalize-button';

    private const EDIT_FORM_PATH = '/typo3/record/edit?edit%5Btx_testinlinerelations_parent%5D%5B2%5D=edit&returnUrl=/typo3/module/web/list';

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
            'id' => 1,
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
            'id' => 1,
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
            'expectedControlChildUids' => [3],
        ];
        yield 'editor with DeepL translate permission' => [
            'backendUserUid' => 2,
            'targetLanguagePreset' => 'DE',
            'expectedControlChildUids' => [3],
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

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 2, 'record_edit');

        $this->assertStringContainsString(self::CORE_LOCALIZE_CLASS, $html, 'Core localize button of the not yet localized child is missing.');
        $this->assertSame($expectedControlChildUids, array_keys($this->findControls($html)));
    }

    #[Test]
    public function controlIsNotOfferedInDefaultLanguageParent(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $html = $this->renderEditForm('tx_testinlinerelations_parent', 1, 'record_edit');

        $this->assertStringContainsString('data-object-id="data-1-tx_testinlinerelations_parent-1-children_declared-tx_testinlinerelations_child_declared-3"', $html, 'Child is not rendered in the default language parent.');
        $this->assertSame([], $this->findControls($html));
    }

    #[Test]
    #[Group('not-core-14')]
    public function controlLinksDeeplTranslateCommandBehindConfirmation(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2, 'record_edit'))[3] ?? null;

        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');
        $this->assertSame(
            [
                'tx_testinlinerelations_child_declared' => [
                    3 => [
                        'deepltranslate' => '1',
                    ],
                ],
            ],
            $this->getQueryParameters($control->getAttribute('href'))['cmd'] ?? null
        );
        $this->assertContains('t3js-modal-trigger', explode(' ', $control->getAttribute('class')));
        $this->assertSame('warning', $control->getAttribute('data-severity'));
        $this->assertSame('Localize with DeepL', $control->getAttribute('data-title'));
        $this->assertStringContainsString('Unsaved changes', $control->getAttribute('data-content'));
        $this->assertSame('Localize with DeepL', $control->getAttribute('data-button-ok-text'));
        $this->assertSame('Cancel', $control->getAttribute('data-button-close-text'));
    }

    #[Test]
    #[Group('not-core-14')]
    public function controlReturnsEditorToDocumentCurrentlyOpen(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2, 'record_edit'))[3] ?? null;

        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');
        $this->assertSame(
            'https://localhost' . self::EDIT_FORM_PATH,
            $this->getQueryParameters($control->getAttribute('href'))['redirect'] ?? null
        );
    }

    /**
     * Controls can be rendered in the AJAX response of an expanded inline record, which knows nothing about
     * the document open in the browser.
     */
    #[Test]
    #[Group('not-core-14')]
    public function controlRenderedInAjaxResponseReturnsEditorToEditFormOfTopMostRecord(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2, 'ajax_record_inline_details'))[3] ?? null;

        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');
        $redirect = (string)($this->getQueryParameters($control->getAttribute('href'))['redirect'] ?? '');
        $this->assertStringEndsWith('/record/edit', (string)parse_url($redirect, PHP_URL_PATH));
        $redirectParameters = $this->getQueryParameters($redirect);
        unset($redirectParameters['token']);
        $this->assertSame(
            [
                'edit' => [
                    'tx_testinlinerelations_parent' => [
                        2 => 'edit',
                    ],
                ],
                'returnUrl' => '/typo3/module/web/list',
            ],
            $redirectParameters
        );
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
        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2, 'record_edit'))[3] ?? null;
        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');

        // TYPO3 v13 validates the redirect against `$_SERVER` instead of the request.
        $serverBackup = $_SERVER;
        $_SERVER = array_replace($_SERVER, $this->getServerParameters('/typo3/record/commit'));
        try {
            // Not a container service, the backend route dispatcher instantiates it the same way.
            $response = GeneralUtility::makeInstance(SimpleDataHandlerController::class)->mainAction(
                $this->createBackendRequest('/typo3/record/commit', 'tce_db')
                    ->withQueryParams($this->getQueryParameters($control->getAttribute('href')))
            );
        } finally {
            $_SERVER = $serverBackup;
        }

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://localhost' . self::EDIT_FORM_PATH, $response->getHeaderLine('location'), 'Editor is not returned to the edit form.');
        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
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
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/inlineRelationsSecondNewChild.csv');
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);
        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2, 'record_edit'))[3] ?? null;
        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');

        $serverBackup = $_SERVER;
        $_SERVER = array_replace($_SERVER, $this->getServerParameters('/typo3/record/commit'));
        try {
            GeneralUtility::makeInstance(SimpleDataHandlerController::class)->mainAction(
                $this->createBackendRequest('/typo3/record/commit', 'tce_db')
                    ->withQueryParams($this->getQueryParameters($control->getAttribute('href')))
            );
        } finally {
            $_SERVER = $serverBackup;
        }

        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'Requested child has not been localized.');
        $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], 'Requested child has not been translated by DeepL.');
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 4, 1), 'Sibling child has been localized as well.');
    }

    #[Test]
    #[Group('not-core-13')]
    public function controlOpensCoreLocalizationWizardForChild(): void
    {
        $this->writeSite('DE');
        $this->setUpBackendUserWithLanguageService(1);

        $control = $this->findControls($this->renderEditForm('tx_testinlinerelations_parent', 2, 'record_edit'))[3] ?? null;

        $this->assertInstanceOf(\DOMElement::class, $control, 'No DeepL control rendered for the new child.');
        $this->assertSame('typo3-backend-localization-button', $control->tagName);
        $this->assertSame('tx_testinlinerelations_child_declared', $control->getAttribute('record-type'));
        $this->assertSame('3', $control->getAttribute('record-uid'));
        $this->assertSame('1', $control->getAttribute('target-language'));
        $this->assertSame('Localize with DeepL', $control->getAttribute('title'));
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

    private function renderEditForm(string $table, int $uid, string $routeIdentifier): string
    {
        $pathAndQuery = $routeIdentifier === 'record_edit'
            ? self::EDIT_FORM_PATH
            : '/typo3/ajax/record/inline/details';
        $formData = $this->get(FormDataCompiler::class)->compile(
            [
                'request' => $this->createBackendRequest($pathAndQuery, $routeIdentifier),
                'tableName' => $table,
                'vanillaUid' => $uid,
                'command' => 'edit',
                'returnUrl' => '/typo3/module/web/list',
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

    private function resolveChildUid(\DOMElement $control): int
    {
        if ($control->hasAttribute('record-uid')) {
            return (int)$control->getAttribute('record-uid');
        }
        $command = $this->getQueryParameters($control->getAttribute('href'))['cmd'] ?? [];
        $recordCommands = is_array($command) ? (array)reset($command) : [];

        return (int)array_key_first($recordCommands);
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
