<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Override\Core12;

/**
 * @todo Check if this is needed for TYPO3 v13/v14 and move to corresponding folder or remove it.
 */
class DatabaseRecordListCore extends \TYPO3\CMS\Backend\RecordList\DatabaseRecordList
{
    use DatabaseRecordList;
}
