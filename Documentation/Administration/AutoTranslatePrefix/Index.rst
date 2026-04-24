..  _administration-autotranslateprefix:

Auto-translate-prefix
=====================

To enable the tagging of automatically translated pages and content, the page
activation of translated pages has been extended to provide a means of control.

This information is passed to the Page Context Fluid template, where it can be used to create a page-specific look.

..  figure:: /Images/Editor/AutoTranslatePrefix/page-translation-prefix.png
    :alt: Page Frontend


----


To make this easier, you can also use the extension partial.

..  code-block:: html

    <f:if condition="{page.tx_wvdeepltranslate_content_not_checked}" >
        <div style="background: #006494; border: #0000cc 1px solid; color: #fff; padding: 10px; text-align: center">
            <f:translate key="LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:preview.flag" />
            <f:if condition="{page.tx_wvdeepltranslate_translated_time} > 0" >
                <f:format.date format="{dateFormat}">{page.tx_wvdeepltranslate_translated_time}</f:format.date>
            </f:if>
        </div>
    </f:if>
