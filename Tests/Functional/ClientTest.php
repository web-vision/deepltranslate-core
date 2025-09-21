<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional;

use DeepL\Language;
use DeepL\TextResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\AbstractClient;
use WebVision\Deepltranslate\Core\TranslatorInterface;

#[CoversClass(AbstractClient::class)]
final class ClientTest extends AbstractDeepLTestCase
{
    #[Test]
    public function checkResponseFromTranslateContent(): void
    {
        $translateContent = self::EXAMPLE_TEXT['en'];
        $client = $this->get(TranslatorInterface::class);
        $response = $client->translate(
            $translateContent,
            'EN',
            'DE'
        );

        $this->assertInstanceOf(TextResult::class, $response);
        $this->assertSame(self::EXAMPLE_TEXT['de'], $response->text);
    }

    #[Test]
    public function checkResponseFromSupportedTargetLanguage(): void
    {
        $client = $this->get(TranslatorInterface::class);
        $response = $client->getSupportedLanguageByType();

        $this->assertIsArray($response);
        $this->assertContainsOnlyInstancesOf(Language::class, $response);
    }
}
