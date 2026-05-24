import { p as publicAssetsURL } from '../routes/renderer.mjs';
import 'node:async_hooks';
import 'vue-bundle-renderer/runtime';
import '../runtime.mjs';
import 'node:http';
import 'node:https';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'devalue';
import 'vue/server-renderer';
import '@unhead/ssr';
import 'unhead';
import 'vue';
import '@unhead/shared';

const Main = ".main__programm{margin-top:85px}@media (max-width:520px){.main__programm{margin-top:70px}}.main__links{display:flex;font-size:15px;font-weight:600;gap:25px;padding-bottom:20px}.main__line{background-image:linear-gradient(90deg,#c8c8c8 0,#fbfcfd 101%);height:1px;width:100%}.main__social{margin-top:85px}";

const Warning = "";

const Validation = "";

const Fines_vue_vue_type_style_index_0_lang = ".main-page{margin-top:60px;grid-gap:20px 30px;display:grid;grid-template-columns:repeat(2,1fr)}.main-page h1{font-size:35px}@media (max-width:1120px){.main-page h1{text-align:center}}@media (max-width:800px){.main-page h1{text-align:left}}.main-page__title{display:none}@media (max-width:1120px){.main-page__title{display:block}}.main-page__checkbox.form-item__checkbox{width:-moz-max-content;width:max-content}.main-page__checkbox.form-item__checkbox input{pointer-events:none}.main-page__checkbox.form-item__checkbox.main-page__checkbox_long{align-items:flex-start;width:100%}.main-page__checkbox.form-item__checkbox.main-page__checkbox_long label{margin-top:2px}.main-page__text{font-size:16px}.main-page__text.auth__subtext{font-size:16px;margin-top:16px;position:relative;z-index:2}.main-page__link{color:#1253a2;cursor:pointer;font-size:16px;text-align:center;text-decoration:underline}.main-page__link:hover{text-decoration:none}.main-page__input input{font-size:16px}.main-page__input input::-moz-placeholder{color:#bcbcbc;font-size:13px}.main-page__input input::placeholder{color:#bcbcbc;font-size:13px}.main-page__input input:-ms-input-placeholder{color:#bcbcbc;font-size:13px}.main-page__input input::-ms-input-placeholder{color:#bcbcbc;font-size:13px}.main-page__input.auth__input_substr input{padding:10px 90px 10px 15px}.main-page__input.auth__input_substr .form-item__substring{top:26px}.main-page__button{align-items:center;display:flex;flex-direction:row-reverse;font-size:16px;gap:10px;height:45px;justify-content:center;padding:15px 20px;width:200px}.main-page__button:before{content:url(" + publicAssetsURL("icons/arrow-right.svg") + ");height:19px;transition:all .4s}.main-page__button:hover{filter:brightness(.95)}.main-page__button:hover:before{transform:translateX(5px)}.main-page__button.button_loading:before{content:none}.main-page__form{grid-gap:15px 30px;display:flex;flex-direction:column;gap:15px}.main-page__form-subtitle{color:#8f8f8f;font-size:16px;font-weight:400;margin-top:10px}.main-page_gray{color:#616161}.main-page__container{display:flex;flex-direction:column;gap:15px;margin:auto 0}.main-page__actions{display:flex;gap:20px;grid-column:1/3}@media (max-width:1120px){.main-page__actions{justify-content:center}}.main-page__actions button{font-size:16px;height:45px;padding:10px 20px;width:-moz-max-content;width:max-content}.main-page__actions button.button_blue{align-items:center;display:flex;gap:10px;white-space:nowrap}.main-page__actions button.button_blue:after{content:url(" + publicAssetsURL("icons/arrow-right.svg") + ");height:16px;transition:all .4s}.main-page__actions button.button_blue:not([disabled]):hover{filter:brightness(.95)}.main-page__actions button.button_blue:not([disabled]):hover:after{transform:translateX(5px)}.main-page__actions button.main-page__button{align-items:center;background:#fff;border:1px solid #2f8cff;display:flex;gap:5px;width:100%}.main-page__actions button.main-page__button span{align-items:center;display:flex;gap:5px;white-space:nowrap}.main-page__actions button.main-page__button .button-text{color:#1253a2}.main-page__actions button.main-page__button .main-page__icon{height:24px;width:32px}.main-page__actions button.main-page__button .main-page__icon img{-o-object-fit:contain;object-fit:contain}.main-page__fansy-box{width:-moz-max-content;width:max-content}.input_line{grid-column:1/3}.main-page__politics{color:#8f8f8f;font-size:13px;grid-column:1/3}.main-page__image{grid-column:2/3;height:335px;width:560px}.main-page__image img{-o-object-fit:contain;object-fit:contain}@media (max-width:1120px){.main-page{grid-template-columns:1fr;margin-top:20px}.main-page h1{font-size:32px}.main-page h1 span{margin:0 auto}}@media (max-width:1120px) and (max-width:700px){.main-page h1 span{margin:unset}}@media (max-width:1120px){.main-page h1 .menu_mobile{top:10px!important}.main-page .main-page__form{grid-column:1/2}.main-page .main-page__image{grid-column:1/2;grid-row:2/3;justify-self:center}}@media (max-width:600px){.main-page__actions{flex-direction:column;gap:15px}.main-page__actions>*{height:45px}}@media (max-width:620px){.main-page{grid-template-columns:1fr}.main-page .main-page__image{height:230px;width:100%}}@media (max-width:460px){.main-page__form{display:flex;flex-direction:column}}";

const Tariffs_vue_vue_type_style_index_0_lang = ".main__tariffs{margin-top:85px}.main__tariffs h2{margin-bottom:20px;text-align:center}.main__social{margin-top:100px}@media (max-width:520px){.main__social,.main__tariffs{margin-top:70px}}";

const PlusesFines = '.pluses-fines{margin:85px 0}.pluses-fines h2{margin:0 auto 35px;text-align:center}.pluses-fines__list{display:grid;gap:35px;grid-template-columns:repeat(2,1fr);margin-bottom:25px}.pluses-fines__item{background:#fbfcfd;border:1px solid #eeeff1;border-radius:10px;font-size:25px;overflow:hidden;padding:30px 25px;position:relative;transition:all .2s}.pluses-fines__item:hover{background-color:#fff;box-shadow:0 0 20px 0 rgba(0,0,0,.07)}.pluses-fines__item:before{background-color:#1253a2;content:"";height:100%;left:0;position:absolute;top:0;width:10px}.pluses__actions{display:flex;justify-content:center;width:100%}.pluses__actions button{font-size:16px;padding:15px 20px}@media (max-width:770px){.pluses-fines__list{gap:15px;grid-template-columns:repeat(1,1fr)}}';

const indexStyles_6a7297a3 = [Main, Warning, Validation, Fines_vue_vue_type_style_index_0_lang, Tariffs_vue_vue_type_style_index_0_lang, PlusesFines];

export { indexStyles_6a7297a3 as default };
//# sourceMappingURL=index-styles.6a7297a3.mjs.map
