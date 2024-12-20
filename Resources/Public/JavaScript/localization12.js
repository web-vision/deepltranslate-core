import $ from 'jquery';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response.js';
import { InputTransformer } from '@typo3/core/ajax/input-transformer.js';
import AjaxRequest$1 from '@typo3/core/ajax/ajax-request.js';
import ClientStorage from '@typo3/backend/storage/client.js';
import { Sizes, States, MarkupIdentifiers } from '@typo3/backend/enum/icon-types.js';
import 'lit';
import { SeverityEnum as SeverityEnum$1 } from '@typo3/backend/enum/severity.js';
import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';
import Icons$2 from '@typo3/backend/icons.js';

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
class DocumentService{constructor(e=window,t=document){this.windowRef=e,this.documentRef=t;}ready(){return new Promise(((e,t)=>{if("complete"===this.documentRef.readyState)e(this.documentRef);else {const n=setTimeout((()=>{o(),t(this.documentRef);}),3e4),o=()=>{this.windowRef.removeEventListener("load",d),this.documentRef.removeEventListener("DOMContentLoaded",d);},d=()=>{o(),clearTimeout(n),e(this.documentRef);};this.windowRef.addEventListener("load",d),this.documentRef.addEventListener("DOMContentLoaded",d);}}))}}const documentService=new DocumentService;

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
var SeverityEnum;!function(n){n[n.notice=-2]="notice",n[n.info=-1]="info",n[n.ok=0]="ok",n[n.warning=1]="warning",n[n.error=2]="error";}(SeverityEnum||(SeverityEnum={}));

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
class AjaxRequest{constructor(e){this.queryArguments="",this.url=e,this.abortController=new AbortController;}withQueryArguments(e){const t=this.clone();return t.queryArguments=(""!==t.queryArguments?"&":"")+InputTransformer.toSearchParams(e),t}async get(e={}){const t=await this.send({method:"GET",...e});return new AjaxResponse(t)}async post(e,t={}){const n={body:"string"==typeof e||e instanceof FormData?e:InputTransformer.byHeader(e,t?.headers),cache:"no-cache",method:"POST"},r=await this.send({...n,...t});return new AjaxResponse(r)}async put(e,t={}){const n={body:"string"==typeof e||e instanceof FormData?e:InputTransformer.byHeader(e,t?.headers),cache:"no-cache",method:"PUT"},r=await this.send({...n,...t});return new AjaxResponse(r)}async delete(e={},t={}){const n={cache:"no-cache",method:"DELETE"};"string"==typeof e&&e.length>0||e instanceof FormData?n.body=e:"object"==typeof e&&Object.keys(e).length>0&&(n.body=InputTransformer.byHeader(e,t?.headers));const r=await this.send({...n,...t});return new AjaxResponse(r)}abort(){this.abortController.abort();}clone(){return Object.assign(Object.create(this),this)}async send(e={}){const t=await fetch(this.composeRequestUrl(),this.getMergedOptions(e));if(!t.ok)throw new AjaxResponse(t);return t}composeRequestUrl(){let e=this.url;if("?"===e.charAt(0)&&(e=window.location.origin+window.location.pathname+e),e=new URL(e,window.location.origin).toString(),""!==this.queryArguments){e+=(this.url.includes("?")?"&":"?")+this.queryArguments;}return e}getMergedOptions(e){return {...AjaxRequest.defaultOptions,...e,signal:this.abortController.signal}}}AjaxRequest.defaultOptions={credentials:"same-origin"};

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
class Icons{constructor(){this.sizes=Sizes,this.states=States,this.markupIdentifiers=MarkupIdentifiers,this.promiseCache={};}getIcon(e,i,t,n,o){const s=[e,i=i||Sizes.default,t,n=n||States.default,o=o||MarkupIdentifiers.default],r=s.join("_");return this.getIconRegistryCache().then((e=>(ClientStorage.isset("icon_registry_cache_identifier")&&ClientStorage.get("icon_registry_cache_identifier")===e||(ClientStorage.unsetByPrefix("icon_"),ClientStorage.set("icon_registry_cache_identifier",e)),this.fetchFromLocal(r).then(null,(()=>this.fetchFromRemote(s,r))))))}getIconRegistryCache(){const e="icon_registry_cache_identifier";return this.isPromiseCached(e)||this.putInPromiseCache(e,new AjaxRequest$1(TYPO3.settings.ajaxUrls.icons_cache).get().then((async e=>await e.resolve()))),this.getFromPromiseCache(e)}fetchFromRemote(e,i){if(!this.isPromiseCached(i)){const t={icon:JSON.stringify(e)};this.putInPromiseCache(i,new AjaxRequest$1(TYPO3.settings.ajaxUrls.icons).withQueryArguments(t).get().then((async e=>{const t=await e.resolve();return t.includes("t3js-icon")&&t.includes('<span class="icon-markup">')&&ClientStorage.set("icon_"+i,t),t})));}return this.getFromPromiseCache(i)}fetchFromLocal(e){return ClientStorage.isset("icon_"+e)?Promise.resolve(ClientStorage.get("icon_"+e)):Promise.reject()}isPromiseCached(e){return void 0!==this.promiseCache[e]}getFromPromiseCache(e){return this.promiseCache[e]}putInPromiseCache(e,i){this.promiseCache[e]=i;}}let iconsObject;iconsObject||(iconsObject=new Icons,TYPO3.Icons=iconsObject);var Icons$1 = iconsObject;

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
class Wizard{constructor(){this.setup={slides:[],settings:{},forceSelection:!0,$carousel:null},this.originalSetup=$.extend(!0,{},this.setup);}set(e,t){return this.setup.settings[e]=t,this}addSlide(e,t,s="",i=SeverityEnum$1.info,a){const r={identifier:e,title:t,content:s,severity:i,callback:a};return this.setup.slides.push(r),this}addFinalProcessingSlide(e){return e||(e=()=>{this.dismiss();}),Icons$2.getIcon("spinner-circle-dark",Icons$2.sizes.large,null,null).then((t=>{const s=$("<div />",{class:"text-center"}).append(t);this.addSlide("final-processing-slide",top.TYPO3.lang["wizard.processing.title"],s[0].outerHTML,Severity.info,e);}))}show(){const e=this.generateSlides(),t=this.setup.slides[0],s=Modal.advanced({title:t.title,content:e,severity:t.severity,staticBackdrop:!0,buttons:[{text:top.TYPO3.lang["wizard.button.cancel"],active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>{this.getComponent().trigger("wizard-dismiss");}},{text:top.TYPO3.lang["wizard.button.next"],btnClass:"btn-"+Severity.getCssClass(t.severity),name:"next"}],callback:()=>{this.addProgressBar(),this.initializeEvents(s);}});this.setup.forceSelection&&this.lockNextStep(),this.getComponent().on("wizard-visible",(()=>{this.runSlideCallback(t,this.setup.$carousel.find(".carousel-item").first());})).on("wizard-dismissed",(()=>{this.setup=$.extend(!0,{},this.originalSetup);}));}getComponent(){return null===this.setup.$carousel&&this.generateSlides(),this.setup.$carousel}dismiss(){Modal.dismiss();}lockNextStep(){const e=this.setup.$carousel.closest(".modal").find('button[name="next"]');return e.prop("disabled",!0),e}unlockNextStep(){const e=this.setup.$carousel.closest(".modal").find('button[name="next"]');return e.prop("disabled",!1),e}setForceSelection(e){this.setup.forceSelection=e;}initializeEvents(e){const t=this.setup.$carousel.closest(".modal"),s=t.find(".modal-title"),i=t.find(".modal-footer"),a=i.find('button[name="next"]');a.on("click",(()=>{this.setup.$carousel.carousel("next");})),this.setup.$carousel.on("slide.bs.carousel",(()=>{const e=this.setup.$carousel.data("currentSlide")+1,r=this.setup.$carousel.data("currentIndex")+1;s.text(this.setup.slides[r].title),this.setup.$carousel.data("currentSlide",e),this.setup.$carousel.data("currentIndex",r),e>=this.setup.$carousel.data("realSlideCount")?(t.find(".modal-header .close").remove(),i.slideUp()):i.find(".progress-bar").width(this.setup.$carousel.data("initialStep")*e+"%").text(top.TYPO3.lang["wizard.progress"].replace("{0}",e).replace("{1}",this.setup.$carousel.data("slideCount"))),a.removeClass("btn-"+Severity.getCssClass(this.setup.slides[r-1].severity)).addClass("btn-"+Severity.getCssClass(this.setup.slides[r].severity)),t.removeClass("modal-severity-"+Severity.getCssClass(this.setup.slides[r-1].severity)).addClass("modal-severity-"+Severity.getCssClass(this.setup.slides[r].severity));})).on("slid.bs.carousel",(e=>{const t=this.setup.$carousel.data("currentIndex"),s=this.setup.slides[t];this.runSlideCallback(s,$(e.relatedTarget)),this.setup.forceSelection&&this.lockNextStep();}));const r=this.getComponent();r.on("wizard-dismiss",this.dismiss),e.addEventListener("typo3-modal-hidden",(()=>{r.trigger("wizard-dismissed");})),e.addEventListener("typo3-modal-shown",(()=>{r.trigger("wizard-visible");}));}runSlideCallback(e,t){"function"==typeof e.callback&&e.callback(t,this.setup.settings,e.identifier);}addProgressBar(){const e=this.setup.$carousel.find(".carousel-item").length,t=Math.max(1,e),s=Math.round(100/t),i=this.setup.$carousel.closest(".modal").find(".modal-footer");this.setup.$carousel.data("initialStep",s).data("slideCount",t).data("realSlideCount",e).data("currentIndex",0).data("currentSlide",1),t>1&&i.prepend($("<div />",{class:"progress"}).append($("<div />",{role:"progressbar",class:"progress-bar","aria-valuemin":0,"aria-valuenow":s,"aria-valuemax":100}).width(s+"%").text(top.TYPO3.lang["wizard.progress"].replace("{0}","1").replace("{1}",t))));}generateSlides(){if(null!==this.setup.$carousel)return this.setup.$carousel;let e='<div class="carousel slide" data-bs-ride="false"><div class="carousel-inner" role="listbox">';for(const t of Object.values(this.setup.slides)){let s=t.content;"object"==typeof s&&(s=s.html()),e+='<div class="carousel-item" data-bs-slide="'+t.identifier+'">'+s+"</div>";}return e+="</div></div>",this.setup.$carousel=$(e),this.setup.$carousel.find(".carousel-item").first().addClass("active"),this.setup.$carousel}}let wizardObject;try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Wizard&&(wizardObject=window.opener.TYPO3.Wizard),parent&&parent.window.TYPO3&&parent.window.TYPO3.Wizard&&(wizardObject=parent.window.TYPO3.Wizard),top&&top.TYPO3&&top.TYPO3.Wizard&&(wizardObject=top.TYPO3.Wizard);}catch{}wizardObject||(wizardObject=new Wizard,"undefined"!=typeof TYPO3&&(TYPO3.Wizard=wizardObject));var Wizard$1 = wizardObject;

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
class Localization {
    constructor() {
        this.triggerButton = '.t3js-localize';
        this.localizationMode = null;
        this.sourceLanguage = null;
        this.records = [];
        documentService.ready().then(() => {
            this.initialize();
        });
    }
    initialize() {
        const me = this;
        Icons$1.getIcon('actions-localize', Icons$1.sizes.large).then((localizeIconMarkup) => {
            Icons$1.getIcon('actions-edit-copy', Icons$1.sizes.large).then((copyIconMarkup) => {
                Icons$1.getIcon('actions-localize-deepl', Icons$1.sizes.large).then((deeplIconMarkup) => {
                    $(me.triggerButton).removeClass('disabled');
                    $(document).on('click', me.triggerButton, (e) => {
                        e.preventDefault();
                        const $triggerButton = $(e.currentTarget);
                        const actions = [];
                        const availableLocalizationModes = [];
                        let slideStep1 = '';
                        if ($triggerButton.data('allowTranslate')) {
                            actions.push('<div class="row">' +
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
                                '</div>');
                            availableLocalizationModes.push('localize');
                        }
                        if ($triggerButton.data('allowCopy')) {
                            actions.push('<div class="row">' +
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
                                '</div>');
                            availableLocalizationModes.push('copyFromLanguage');
                        }
                        if ($triggerButton.data('allowDeeplTranslate')) {
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
                                  <div class='alert alert-danger' hidden>
                                    ${TYPO3.lang['localize.educate.deeplSettingsFailure']}
                                  </div>
                                  <p class="t3js-helptext t3js-helptext-copy text-body-secondary">
                                    ${TYPO3.lang['localize.educate.deepltranslate']}
                                  </p>
                                </div>
                              </div>
                              `);
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
                                <div class='alert alert-danger' hidden>
                                  ${TYPO3.lang['localize.educate.deeplSettingsFailure']}
                                </div>
                                <p class="t3js-helptext t3js-helptext-copy text-body-secondary">
                                 ${TYPO3.lang['localize.educate.deepltranslateAuto']}
                                </p>
                              </div>
                            </div>
                            `);
                          availableLocalizationModes.push('localizedeeplauto');

                        }
                        if ($triggerButton.data('allowTranslate') === 0 && $triggerButton.data('allowCopy') === 0) {
                            actions.push('<div class="row">' +
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
                                '</div>');
                        }
                        slideStep1 += '<div data-bs-toggle="buttons">' + actions.join('') + '</div>';
                        Wizard$1.addSlide('localize-choose-action', TYPO3.lang['localize.wizard.header_page']
                            .replace('{0}', $triggerButton.data('page'))
                            .replace('{1}', $triggerButton.data('languageName')), slideStep1, SeverityEnum.info, () => {
                            if (availableLocalizationModes.length === 1) {
                                // In case only one mode is available, select the mode and continue
                                this.localizationMode = availableLocalizationModes[0];
                                Wizard$1.unlockNextStep().trigger('click');
                            }
                        });
                        Wizard$1.addSlide('localize-choose-language', TYPO3.lang['localize.view.chooseLanguage'], '', SeverityEnum.info, ($slide) => {
                            Icons$1.getIcon('spinner-circle-dark', Icons$1.sizes.large).then((markup) => {
                                $slide.html('<div class="text-center">' + markup + '</div>');
                                this.loadAvailableLanguages(parseInt($triggerButton.data('pageId'), 10), parseInt($triggerButton.data('languageId'), 10)).then(async (response) => {
                                    const result = await response.resolve();
                                    if (result.length === 1) {
                                        // We only have one result, auto select the record and continue
                                        this.sourceLanguage = result[0].uid;
                                        Wizard$1.unlockNextStep().trigger('click');
                                        return;
                                    }
                                    Wizard$1.getComponent().on('click', '.t3js-language-option', (optionEvt) => {
                                        const $me = $(optionEvt.currentTarget);
                                        const $radio = $me.prev();
                                        this.sourceLanguage = $radio.val();
                                        Wizard$1.unlockNextStep();
                                    });
                                    const $languageButtons = $('<div />', { class: 'row' });
                                    for (const languageObject of result) {
                                        const id = 'language' + languageObject.uid;
                                        const $input = $('<input />', {
                                            type: 'radio',
                                            name: 'language',
                                            id: id,
                                            value: languageObject.uid,
                                            style: 'display: none;',
                                            class: 'btn-check',
                                        });
                                        const $label = $('<label />', {
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
                        });
                        Wizard$1.addSlide('localize-summary', TYPO3.lang['localize.view.summary'], '', SeverityEnum.info, ($slide) => {
                            Icons$1.getIcon('spinner-circle-dark', Icons$1.sizes.large).then((markup) => {
                                $slide.html('<div class="text-center">' + markup + '</div>');
                            });
                            this.getSummary(parseInt($triggerButton.data('pageId'), 10), parseInt($triggerButton.data('languageId'), 10)).then(async (response) => {
                                const result = await response.resolve();
                                $slide.empty();
                                this.records = [];
                                const columns = result.columns.columns;
                                const columnList = result.columns.columnList;
                                columnList.forEach((colPos) => {
                                    if (typeof result.records[colPos] === 'undefined') {
                                        return;
                                    }
                                    const column = columns[colPos];
                                    const $row = $('<div />', { class: 'row' });
                                    result.records[colPos].forEach((record) => {
                                        const label = ' (' + record.uid + ') ' + record.title;
                                        this.records.push(record.uid);
                                        $row.append($('<div />', { class: 'col-sm-6' }).append($('<div />', { class: 'input-group' }).append($('<span />', { class: 'input-group-addon' }).append($('<input />', {
                                            type: 'checkbox',
                                            class: 't3js-localization-toggle-record',
                                            id: 'record-uid-' + record.uid,
                                            checked: 'checked',
                                            'data-uid': record.uid,
                                            'aria-label': label,
                                        })), $('<label />', {
                                            class: 'form-control',
                                            for: 'record-uid-' + record.uid,
                                        })
                                            .text(label)
                                            .prepend(record.icon))));
                                    });
                                    $slide.append($('<fieldset />', {
                                        class: 'localization-fieldset',
                                    }).append($('<label />')
                                        .text(column)
                                        .prepend($('<input />', {
                                        class: 't3js-localization-toggle-column',
                                        type: 'checkbox',
                                        checked: 'checked',
                                    })), $row));
                                });
                                Wizard$1.unlockNextStep();
                                Wizard$1.getComponent()
                                    .on('change', '.t3js-localization-toggle-record', (cmpEvt) => {
                                    const $me = $(cmpEvt.currentTarget);
                                    const uid = $me.data('uid');
                                    const $parent = $me.closest('fieldset');
                                    const $columnCheckbox = $parent.find('.t3js-localization-toggle-column');
                                    if ($me.is(':checked')) {
                                        this.records.push(uid);
                                    }
                                    else {
                                        const index = this.records.indexOf(uid);
                                        if (index > -1) {
                                            this.records.splice(index, 1);
                                        }
                                    }
                                    const $allChildren = $parent.find('.t3js-localization-toggle-record');
                                    const $checkedChildren = $parent.find('.t3js-localization-toggle-record:checked');
                                    $columnCheckbox.prop('checked', $checkedChildren.length > 0);
                                    $columnCheckbox.prop('indeterminate', $checkedChildren.length > 0 && $checkedChildren.length < $allChildren.length);
                                    if (this.records.length > 0) {
                                        Wizard$1.unlockNextStep();
                                    }
                                    else {
                                        Wizard$1.lockNextStep();
                                    }
                                })
                                    .on('change', '.t3js-localization-toggle-column', (toggleEvt) => {
                                    const $me = $(toggleEvt.currentTarget);
                                    const $children = $me.closest('fieldset').find('.t3js-localization-toggle-record');
                                    $children.prop('checked', $me.is(':checked'));
                                    $children.trigger('change');
                                });
                            });
                        });
                        Wizard$1.addFinalProcessingSlide(() => {
                            this.localizeRecords(parseInt($triggerButton.data('pageId'), 10), parseInt($triggerButton.data('languageId'), 10), this.records).then(() => {
                                Wizard$1.dismiss();
                                document.location.reload();
                            });
                        }).then(() => {
                            Wizard$1.show();
                            Wizard$1.getComponent().on('click', '.t3js-localization-option', (optionEvt) => {
                                const $me = $(optionEvt.currentTarget);
                                const $radio = $me.find('input[type="radio"]');
                                if ($me.data('helptext')) {
                                    const $container = $(optionEvt.delegateTarget);
                                    $container.find('.t3js-localization-option').removeClass('active');
                                    $container.find('.t3js-helptext').addClass('text-body-secondary');
                                    $me.addClass('active');
                                    $container.find($me.data('helptext')).removeClass('text-body-secondary');
                                }
                                this.loadAvailableLanguages(parseInt($triggerButton.data('pageId'), 10), parseInt($triggerButton.data('languageId'), 10)).then(async (response) => {
                                    const result = await response.resolve();
                                    if (result.length === 1) {
                                        this.sourceLanguage = result[0].uid;
                                    }
                                    else {
                                        // This seems pretty ugly solution to find the right language uid but its done the same way in the core... line 211-213
                                        // If we have more then 1 language we need to find the first radio button and check its value to get the source language
                                        this.sourceLanguage = $radio.prev().val();
                                    }
                                    if ($radio.length > 0) {
                                        if ($radio.val() == 'localizedeepl' ||
                                            $radio.val() == 'localizedeeplauto') {
                                            this.deeplSettings().then(async (response) => {
                                                const result = await response.resolve();
                                                if (result.status === false) {
                                                    Wizard$1.lockNextStep();
                                                    window.parent.document.querySelector('#deeplText .alert').hidden = true;
                                                    window.parent.document.querySelector('#deeplTextAuto .alert').hidden = true;
                                                    let divDeepl = $radio.val() == 'localizedeepl'
                                                        ? window.parent.document.querySelector('#deeplText .alert')
                                                        : window.parent.document.querySelector('#deeplTextAuto .alert');
                                                    divDeepl.hidden = false;
                                                } else {
                                                  window.parent.document.querySelector('#deeplText .alert').hidden = true;
                                                  window.parent.document.querySelector('#deeplTextAuto .alert').hidden = true;
                                                  this.localizationMode = $radio.val().toString();
                                                  Wizard$1.unlockNextStep();
                                                }
                                            });
                                        } else {
                                          window.parent.document.querySelector('#deeplText .alert').hidden = true;
                                          window.parent.document.querySelector('#deeplTextAuto .alert').hidden = true;
                                          this.localizationMode = $radio.val().toString();
                                          Wizard$1.unlockNextStep();
                                        }
                                    }
                                });
                                this.localizationMode = $radio.val().toString();
                                Wizard$1.unlockNextStep();
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
    loadAvailableLanguages(pageId, languageId) {
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
    getSummary(pageId, languageId) {
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
    localizeRecords(pageId, languageId, uidList) {
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
    deeplSettings() {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.deepl_check_configuration).get();
    }
}
var Localization$1 = new Localization();

export { Localization$1 as default };
