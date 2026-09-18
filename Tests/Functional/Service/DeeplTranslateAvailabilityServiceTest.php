<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use WebVision\Deepltranslate\Core\Event\DisallowTableFromDeeplTranslateEvent;
use WebVision\Deepltranslate\Core\Service\DeeplTranslateAvailabilityService;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;
use WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility;

/**
 * The rules deciding whether a DeepL translate action is offered for a record, here for the inline child
 * table of the `test_inline_relations` fixture extension on page 1.
 */
final class DeeplTranslateAvailabilityServiceTest extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    private const TABLE = 'tx_testinlinerelations_child_declared';

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
        'FR_WITHOUT_DEEPL' => [
            'id' => 3,
            'title' => 'Français',
            'locale' => 'fr_FR',
            'iso' => 'fr',
            'hrefLang' => 'fr-FR',
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/deeplTranslateAvailability.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('FR_WITHOUT_DEEPL', '/fr/', ['EN'], 'strict'),
            ],
        );
    }

    public static function availabilityDataProvider(): \Generator
    {
        yield 'admin' => [
            'backendUserUid' => 1,
            'targetLanguageId' => 2,
            'expected' => true,
        ];
        yield 'editor with DeepL translate permission' => [
            'backendUserUid' => 2,
            'targetLanguageId' => 2,
            'expected' => true,
        ];
        yield 'editor without DeepL translate permission' => [
            'backendUserUid' => 3,
            'targetLanguageId' => 2,
            'expected' => false,
        ];
        yield 'editor without access to the target language' => [
            'backendUserUid' => 4,
            'targetLanguageId' => 2,
            'expected' => false,
        ];
        yield 'editor without permission to modify the table' => [
            'backendUserUid' => 5,
            'targetLanguageId' => 2,
            'expected' => false,
        ];
        yield 'target language without DeepL target language' => [
            'backendUserUid' => 1,
            'targetLanguageId' => 3,
            'expected' => false,
        ];
        yield 'admin, read-only table' => [
            'backendUserUid' => 1,
            'targetLanguageId' => 2,
            'expected' => false,
            'readOnlyTable' => true,
        ];
        yield 'admin, no DeepL API key' => [
            'backendUserUid' => 1,
            'targetLanguageId' => 2,
            'expected' => false,
            'apiKeySet' => false,
        ];
        yield 'admin, table excluded by DisallowTableFromDeeplTranslateEvent' => [
            'backendUserUid' => 1,
            'targetLanguageId' => 2,
            'expected' => false,
            'tableAllowedByEvent' => false,
        ];
    }

    #[Test]
    #[DataProvider('availabilityDataProvider')]
    public function isAvailableForRecordAppliesTheRulesOfTheDeeplButtons(
        int $backendUserUid,
        int $targetLanguageId,
        bool $expected,
        bool $readOnlyTable = false,
        bool $apiKeySet = true,
        bool $tableAllowedByEvent = true,
    ): void {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        if ($readOnlyTable) {
            $GLOBALS['TCA'][self::TABLE]['ctrl']['readOnly'] = true;
        }
        $eventDispatcher = $this->get(EventDispatcherInterface::class);
        if (!$tableAllowedByEvent) {
            $eventDispatcher = new class ($eventDispatcher) implements EventDispatcherInterface {
                public function __construct(private readonly EventDispatcherInterface $eventDispatcher) {}

                public function dispatch(object $event): object
                {
                    if ($event instanceof DisallowTableFromDeeplTranslateEvent) {
                        $event->disallowTranslateButtons();
                    }

                    return $this->eventDispatcher->dispatch($event);
                }
            };
        }
        $apiKeyProperty = new \ReflectionProperty(DeeplBackendUtility::class, 'apiKey');
        $apiKey = DeeplBackendUtility::getApiKey();
        if (!$apiKeySet) {
            $apiKeyProperty->setValue(null, '');
        }

        try {
            $isAvailable = (new DeeplTranslateAvailabilityService($eventDispatcher))
                ->isAvailableForRecord($backendUser, self::TABLE, 1, $targetLanguageId);
        } finally {
            $apiKeyProperty->setValue(null, $apiKey);
            unset($GLOBALS['TCA'][self::TABLE]['ctrl']['readOnly']);
        }

        $this->assertSame($expected, $isAvailable);
    }
}
