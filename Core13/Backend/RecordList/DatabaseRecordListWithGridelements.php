<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core13\Backend\RecordList;

/**
 * @todo Remove when TYPO3 v13 support is dropped in `web-vision/deepltranslate-core:7.0`
 *       togheter with registration in `ext_localconf.php`.
 */
class DatabaseRecordListWithGridelements extends \GridElementsTeam\Gridelements\Xclass\DatabaseRecordList
{
    use DatabaseRecordList;
}
