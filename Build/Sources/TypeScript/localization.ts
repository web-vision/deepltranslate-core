/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import DocumentService from '@typo3-core/document-service';
import $ from 'jquery';
import { AjaxResponse } from '@typo3-core/ajax/ajax-response';
import { SeverityEnum } from '@typo3-backend/enum/severity';
import AjaxRequest from '@typo3-core/ajax/ajax-request';
import Icons from '@typo3-backend/icons';
import Wizard from '@typo3-backend/wizard';

type LanguageRecord = {
  uid: number;
  title: string;
  flagIcon: string;
};

type SummaryColumns = {
  columns: { [key: number]: string };
  columnList: Array<number>;
};

type SummaryColPosRecord = {
  uid: number;
  title: string;
  icon: string;
};

type SummaryRecord = {
  columns: SummaryColumns;
  records: Array<Array<SummaryColPosRecord>>;
};

type AjaxControllerResponse = {
  status: boolean;
  message: string
};

class Localization {
  private triggerButton: string = '.t3js-localize';
  private localizationMode: string = null;
  private sourceLanguage: number = null;
  private records: Array<any> = [];

  constructor() {
    DocumentService.ready().then((): void => {
      this.initialize();
    });
  }

  private initialize(): void {
    const me = this;
    Icons.getIcon('actions-localize', Icons.sizes.large).then((localizeIconMarkup: string): void => {
      Icons.getIcon('actions-edit-copy', Icons.sizes.large).then((copyIconMarkup: string): void => {
        Icons.getIcon('actions-localize-deepl', Icons.sizes.large).then((deeplIconMarkup: string): void => {
          $(me.triggerButton).removeClass('disabled');

          $(document).on('click', me.triggerButton, (e: JQueryEventObject): void => {
            e.preventDefault();

            const $triggerButton = $(e.currentTarget);
            const actions: Array<string> = [];
            const availableLocalizationModes: Array<string> = [];
            let slideStep1: string = '';

            if ($triggerButton.data('allowTranslate')) {
              actions.push(
                '<div class="row">' +
                  '<div class="col-sm-3">' +
                  '<label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-translate">' +
                  localizeIconMarkup +
                  '<input type="radio" name="mode" id="mode_translate" value="localize" style="display: none">' +
                  '<br>' +
                  TYPO3.lang['localize.wizard.button.translate'] +
                  '</label>' +
                  '</div>' +
                  '<div class="col-sm-9">' +
                  '<p class="t3js-helptext t3js-helptext-translate text-body-secondary">' +
                  TYPO3.lang['localize.educate.translate'] +
                  '</p>' +
                  '</div>' +
                  '</div>',
              );
              availableLocalizationModes.push('localize');
            }

            if ($triggerButton.data('allowCopy')) {
              actions.push(
                '<div class="row">' +
                  '<div class="col-sm-3">' +
                  '<label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-copy">' +
                  copyIconMarkup +
                  '<input type="radio" name="mode" id="mode_copy" value="copyFromLanguage" style="display: none">' +
                  '<br>' +
                  TYPO3.lang['localize.wizard.button.copy'] +
                  '</label>' +
                  '</div>' +
                  '<div class="col-sm-9">' +
                  '<p class="t3js-helptext t3js-helptext-copy text-body-secondary">' +
                  TYPO3.lang['localize.educate.copy'] +
                  '</p>' +
                  '</div>' +
                  '</div>',
              );
              availableLocalizationModes.push('copyFromLanguage');
            }

            actions.push(`
            <div class="row" id="deeplTranslate">
                <div class="col-sm-3">
                  <label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-copy">
                    ${deeplIconMarkup}
                    <input type="radio" name="mode" id="mode_deepltranslate" value="localizedeepl" style="display: none">
                    <br>
                    ${TYPO3.lang['localize.educate.deepltranslateHeader']}
                  </label>
                </div>
                <div class="col-sm-9" id="deeplText">
                  <div class='alert alert-danger' id='alertClose' hidden>  <a href='#'' class='close'  data-bs-dismiss='alert' aria-label='close'>&times;</a>
                    ${TYPO3.lang['localize.educate.deeplSettingsFailure']}
                  </div>
                  <p class="t3js-helptext t3js-helptext-copy text-body-secondary">
                    ${TYPO3.lang['localize.educate.deepltranslate']}
                  </p>
                </div>
              </div>
              `,
            );
            availableLocalizationModes.push('localizedeepl');

            actions.push(`
            <div class="row" id="deeplTranslateAuto">
                <div class="col-sm-3">
                  <label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-copy">
                    ${deeplIconMarkup}
                    <input type="radio" name="mode" id="mode_deepltranslateauto" value="localizedeeplauto" style="display: none">
                    <br>
                    ${TYPO3.lang['localize.educate.deepltranslateHeaderAutodetect']}
                  </label>
                </div>
                <div class="col-sm-9" id="deeplTextAuto" >
                  <div class='alert alert-danger' id='alertClose' hidden>  <a href='#'' class='close'  data-bs-dismiss='alert' aria-label='close'>&times;</a>
                    ${TYPO3.lang['localize.educate.deeplSettingsFailure']}
                  </div>
                  <p class="t3js-helptext t3js-helptext-copy text-body-secondary">
                   ${TYPO3.lang['localize.educate.deepltranslateAuto']}
                  </p>
                </div>
              </div>
              `,
            );
            availableLocalizationModes.push('localizedeeplauto');

            if ($triggerButton.data('allowTranslate') === 0 && $triggerButton.data('allowCopy') === 0) {
              actions.push(
                '<div class="row">' +
                  '<div class="col-sm-12">' +
                  '<div class="alert alert-warning">' +
                  '<div class="media">' +
                  '<div class="media-left">' +
                  '<span class="icon-emphasized"><typo3-backend-icon identifier="actions-exclamation" size="small"></typo3-backend-icon></span>' +
                  '</div>' +
                  '<div class="media-body">' +
                  '<p class="alert-message">' +
                  TYPO3.lang['localize.educate.noTranslate'] +
                  '</p>' +
                  '</div>' +
                  '</div>' +
                  '</div>' +
                  '</div>' +
                  '</div>',
              );
            }

            slideStep1 += '<div data-bs-toggle="buttons">' + actions.join('') + '</div>';
            Wizard.addSlide(
              'localize-choose-action',
              TYPO3.lang['localize.wizard.header_page']
                .replace('{0}', $triggerButton.data('page'))
                .replace('{1}', $triggerButton.data('languageName')),
              slideStep1,
              SeverityEnum.info,
              (): void => {
                if (availableLocalizationModes.length === 1) {
                  // In case only one mode is available, select the mode and continue
                  this.localizationMode = availableLocalizationModes[0];
                  Wizard.unlockNextStep().trigger('click');
                }
              },
            );
            Wizard.addSlide(
              'localize-choose-language',
              TYPO3.lang['localize.view.chooseLanguage'],
              '',
              SeverityEnum.info,
              ($slide: JQuery): void => {
                Icons.getIcon('spinner-circle-dark', Icons.sizes.large).then((markup: string): void => {
                  $slide.html('<div class="text-center">' + markup + '</div>');

                  this.loadAvailableLanguages(
                    parseInt($triggerButton.data('pageId'), 10),
                    parseInt($triggerButton.data('languageId'), 10),
                  ).then(async (response: AjaxResponse): Promise<void> => {
                    const result: Array<LanguageRecord> = await response.resolve();
                    if (result.length === 1) {
                      // We only have one result, auto select the record and continue
                      this.sourceLanguage = result[0].uid;
                      Wizard.unlockNextStep().trigger('click');
                      return;
                    }

                    Wizard.getComponent().on('click', '.t3js-language-option', (optionEvt: JQueryEventObject): void => {
                      const $me = $(optionEvt.currentTarget);
                      const $radio = $me.prev();

                      this.sourceLanguage = $radio.val() as number;
                      Wizard.unlockNextStep();
                    });

                    const $languageButtons = $('<div />', { class: 'row' });

                    for (const languageObject of result) {
                      const id: string = 'language' + languageObject.uid;
                      const $input: JQuery = $('<input />', {
                        type: 'radio',
                        name: 'language',
                        id: id,
                        value: languageObject.uid,
                        style: 'display: none;',
                        class: 'btn-check',
                      });
                      const $label: JQuery = $('<label />', {
                        class: 'btn btn-default d-block t3js-language-option option',
                        for: id,
                      })
                        .text(' ' + languageObject.title)
                        .prepend(languageObject.flagIcon);

                      $languageButtons.append($('<div />', { class: 'col-sm-4' }).append($input).append($label));
                    }
                    $slide.empty().append($languageButtons);
                  });
                });
              },
            );
            Wizard.addSlide(
              'localize-summary',
              TYPO3.lang['localize.view.summary'],
              '',
              SeverityEnum.info,
              ($slide: JQuery): void => {
                Icons.getIcon('spinner-circle-dark', Icons.sizes.large).then((markup: string): void => {
                  $slide.html('<div class="text-center">' + markup + '</div>');
                });
                this.getSummary(
                  parseInt($triggerButton.data('pageId'), 10),
                  parseInt($triggerButton.data('languageId'), 10),
                ).then(async (response: AjaxResponse): Promise<void> => {
                  const result: SummaryRecord = await response.resolve();
                  $slide.empty();
                  this.records = [];

                  const columns = result.columns.columns;
                  const columnList = result.columns.columnList;

                  columnList.forEach((colPos: number): void => {
                    if (typeof result.records[colPos] === 'undefined') {
                      return;
                    }

                    const column = columns[colPos];
                    const $row = $('<div />', { class: 'row' });

                    result.records[colPos].forEach((record: SummaryColPosRecord): void => {
                      const label = ' (' + record.uid + ') ' + record.title;
                      this.records.push(record.uid);

                      $row.append(
                        $('<div />', { class: 'col-sm-6' }).append(
                          $('<div />', { class: 'input-group' }).append(
                            $('<span />', { class: 'input-group-addon' }).append(
                              $('<input />', {
                                type: 'checkbox',
                                class: 't3js-localization-toggle-record',
                                id: 'record-uid-' + record.uid,
                                checked: 'checked',
                                'data-uid': record.uid,
                                'aria-label': label,
                              }),
                            ),
                            $('<label />', {
                              class: 'form-control',
                              for: 'record-uid-' + record.uid,
                            })
                              .text(label)
                              .prepend(record.icon),
                          ),
                        ),
                      );
                    });

                    $slide.append(
                      $('<fieldset />', {
                        class: 'localization-fieldset',
                      }).append(
                        $('<label />')
                          .text(column)
                          .prepend(
                            $('<input />', {
                              class: 't3js-localization-toggle-column',
                              type: 'checkbox',
                              checked: 'checked',
                            }),
                          ),
                        $row,
                      ),
                    );
                  });

                  Wizard.unlockNextStep();

                  Wizard.getComponent()
                    .on('change', '.t3js-localization-toggle-record', (cmpEvt: JQueryEventObject): void => {
                      const $me = $(cmpEvt.currentTarget);
                      const uid = $me.data('uid');
                      const $parent = $me.closest('fieldset');
                      const $columnCheckbox = $parent.find('.t3js-localization-toggle-column');

                      if ($me.is(':checked')) {
                        this.records.push(uid);
                      } else {
                        const index = this.records.indexOf(uid);
                        if (index > -1) {
                          this.records.splice(index, 1);
                        }
                      }

                      const $allChildren = $parent.find('.t3js-localization-toggle-record');
                      const $checkedChildren = $parent.find('.t3js-localization-toggle-record:checked');

                      $columnCheckbox.prop('checked', $checkedChildren.length > 0);
                      $columnCheckbox.prop(
                        'indeterminate',
                        $checkedChildren.length > 0 && $checkedChildren.length < $allChildren.length,
                      );

                      if (this.records.length > 0) {
                        Wizard.unlockNextStep();
                      } else {
                        Wizard.lockNextStep();
                      }
                    })
                    .on('change', '.t3js-localization-toggle-column', (toggleEvt: JQueryEventObject): void => {
                      const $me = $(toggleEvt.currentTarget);
                      const $children = $me.closest('fieldset').find('.t3js-localization-toggle-record');

                      $children.prop('checked', $me.is(':checked'));
                      $children.trigger('change');
                    });
                });
              },
            );

            Wizard.addFinalProcessingSlide((): void => {
              this.localizeRecords(
                parseInt($triggerButton.data('pageId'), 10),
                parseInt($triggerButton.data('languageId'), 10),
                this.records,
              ).then((): void => {
                Wizard.dismiss();
                document.location.reload();
              });
            }).then((): void => {
              Wizard.show();

              Wizard.getComponent().on('click', '.t3js-localization-option', (optionEvt: JQueryEventObject): void => {
                const $me = $(optionEvt.currentTarget);
                const $radio = $me.find('input[type="radio"]');

                if ($me.data('helptext')) {
                  const $container = $(optionEvt.delegateTarget);
                  $container.find('.t3js-localization-option').removeClass('active');
                  $container.find('.t3js-helptext').addClass('text-body-secondary');
                  $me.addClass('active');
                  $container.find($me.data('helptext')).removeClass('text-body-secondary');
                }
                this.loadAvailableLanguages(
                  parseInt($triggerButton.data('pageId'), 10),
                  parseInt($triggerButton.data('languageId'), 10),
                ).then(async (response: AjaxResponse): Promise<void> => {
                  const result: Array<LanguageRecord> = await response.resolve();

                  if (result.length === 1) {
                    this.sourceLanguage = result[0].uid;
                  } else {
                    // This seems pretty ugly solution to find the right language uid but its done the same way in the core... line 211-213
                    // If we have more then 1 language we need to find the first radio button and check its value to get the source language
                    this.sourceLanguage = $radio.prev().val() as number;

                  }

                  if ($radio.length > 0) {
                    if (
                      $radio.val() == 'localizedeepl' ||
                      $radio.val() == 'localizedeeplauto'
                    ) {
                      this.deeplSettings().then(async (response) => {
                        const result: AjaxControllerResponse = await response.resolve();

                        if (result.status === false) {
                          Wizard.lockNextStep()

                          let divDeepl: HTMLElement = $radio.val() == 'localizedeepl'
                            ? window.parent.document.querySelector('#deeplText .alert')
                            : window.parent.document.querySelector('#deeplTextAuto .alert');

                          divDeepl.hidden = false;
                        }
                      })
                    }

                  }
                });

                this.localizationMode = $radio.val().toString();
                Wizard.unlockNextStep()
              });
            });
          });
        });
      });
    });
  }

  /**
   * Load available languages from page
   *
   * @param {number} pageId
   * @param {number} languageId
   * @returns {Promise<AjaxResponse>}
   */
  private loadAvailableLanguages(pageId: number, languageId: number): Promise<AjaxResponse> {
    return new AjaxRequest(TYPO3.settings.ajaxUrls.page_languages)
      .withQueryArguments({
        pageId: pageId,
        languageId: languageId,
      })
      .get();
  }

  /**
   * Get summary for record processing
   *
   * @param {number} pageId
   * @param {number} languageId
   * @returns {Promise<AjaxResponse>}
   */
  private getSummary(pageId: number, languageId: number): Promise<AjaxResponse> {
    return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localize_summary)
      .withQueryArguments({
        pageId: pageId,
        destLanguageId: languageId,
        languageId: this.sourceLanguage,
      })
      .get();
  }

  /**
   * Localize records
   *
   * @param {number} pageId
   * @param {number} languageId
   * @param {Array<number>} uidList
   * @returns {Promise<AjaxResponse>}
   */
  private localizeRecords(pageId: number, languageId: number, uidList: Array<number>): Promise<AjaxResponse> {
    return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localize)
      .withQueryArguments({
        pageId: pageId,
        srcLanguageId: this.sourceLanguage,
        destLanguageId: languageId,
        action: this.localizationMode,
        uidList: uidList,
      })
      .get();
  }

  /**
   * Returns status of deepl configuration, is not set Deepl Button are disabled
   */
  private deeplSettings(): Promise<AjaxResponse> {
    return new AjaxRequest(TYPO3.settings.ajaxUrls.deepl_check_configuration).get();
  }
}

export default new Localization();
