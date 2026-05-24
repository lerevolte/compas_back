import { u as useDayjs } from './dayjs-ce9ed7b6.mjs';
import { useSSRContext, ref, shallowRef, watch, mergeProps, unref, toRaw, withCtx, openBlock, createBlock, Fragment, createTextVNode, toDisplayString, createVNode } from 'vue';
import { isNaN } from 'lodash-es';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderStyle, ssrRenderAttr, ssrInterpolate } from 'vue/server-renderer';
import { A as AppAutocomplete } from './Input-3345b1b6.mjs';
import { B as ButtonText } from './ButtonText-edbdf3ac.mjs';
import { w as useUserStore } from './server.mjs';

const _sfc_main$3 = {
  __name: "AppCopy",
  __ssrInlineRender: true,
  props: {
    text: {
      default: "",
      type: String
    },
    type: {
      default: "",
      type: String
    },
    buttonTitle: {
      default: null,
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    const copyState = ref(false);
    const buttonTextRef = ref(null);
    const copyAddress = () => {
      if (copyState.value)
        return;
      const range = document.createRange();
      range.selectNode(buttonTextRef.value);
      window.getSelection().removeAllRanges();
      window.getSelection().addRange(range);
      document.execCommand("copy");
      window.getSelection().removeAllRanges();
      copyState.value = true;
      setTimeout(() => {
        copyState.value = false;
      }, 2e3);
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(ButtonText, mergeProps({
        class: ["copy button-text__copy", copyState.value ? "button-text__copy_disabled" : ""],
        onClick: () => copyAddress()
      }, _attrs), {
        default: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (!copyState.value) {
              _push2(`<!--[-->${ssrInterpolate(props.buttonTitle)}<!--]-->`);
            } else {
              _push2(`<!--[--> \u0421\u043A\u043E\u043F\u0438\u0440\u043E\u0432\u0430\u043D\u043E <!--]-->`);
            }
            _push2(`<div class="button-text__mirror"${_scopeId}>${ssrInterpolate(props.text)}</div>`);
          } else {
            return [
              !copyState.value ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                createTextVNode(toDisplayString(props.buttonTitle), 1)
              ], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                createTextVNode(" \u0421\u043A\u043E\u043F\u0438\u0440\u043E\u0432\u0430\u043D\u043E ")
              ], 64)),
              createVNode("div", {
                class: "button-text__mirror",
                ref_key: "buttonTextRef",
                ref: buttonTextRef
              }, toDisplayString(props.text), 513)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppCopy/AppCopy.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const AppCopy = _sfc_main$3;
const _sfc_main$2 = {
  __name: "Lasso",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "ibg icon__lasso" }, _attrs))}><svg width="15px" height="16px" viewBox="0 0 15 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>lasso</title><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="null01" transform="translate(-1207.000000, -415.000000)" fill="#A6B7D4" fill-rule="nonzero"><g id="lasso" transform="translate(1207.000000, 415.000000)"><path d="M6.82040145,0.00849830344 C5.54081087,0.117171666 4.64234237,0.31308984 3.69489432,0.68655886 C2.66173207,1.09523193 1.83367166,1.62635385 1.18469271,2.29829197 C0.589285134,2.9166587 0.251020161,3.50747445 0.0673468724,4.24675944 C0.0122448859,4.48094288 0,4.5987999 0,4.99522809 C0.00306122147,5.6105336 0.0765305369,5.94267613 0.336734362,6.50287966 C0.600158364,7.02664346 0.954069415,7.49973393 1.3821415,7.90032727 L1.50459035,8.00287818 L1.73724319,7.84675589 C1.86275327,7.75798047 1.98673274,7.68298054 2.00663068,7.67379688 C2.03112045,7.66767443 1.93316136,7.55287863 1.75714113,7.38298084 C0.326020087,5.99777812 0.232652832,4.29114715 1.50765158,2.83706695 C1.90714098,2.38553678 2.53009955,1.92482295 3.19897644,1.58196614 C5.99846348,0.155436934 10.0178473,0.331457169 12.4836612,1.9891086 C13.4938643,2.66716915 14.1214147,3.50135201 14.3203941,4.42890211 C14.375496,4.68604472 14.3846797,5.24930947 14.3357002,5.50032963 C14.2055983,6.16308408 13.8596802,6.77073654 13.2795788,7.35236862 C12.9964158,7.62940916 12.9765178,7.65849077 13.0285586,7.6921642 C13.0591708,7.70900092 13.1877421,7.77940902 13.310191,7.84981711 L13.5367214,7.97532719 L13.7770272,7.73196008 C15.1178423,6.36818592 15.3734542,4.64165701 14.458149,3.12482176 C14.0647821,2.47125098 13.3423338,1.78094554 12.5571305,1.30339499 C11.4948866,0.660538478 10.1938675,0.235028693 8.77039952,0.0620696793 C8.41223661,0.0207431893 7.11733993,-0.0175220791 6.82040145,0.00849830344 Z" id="Path"></path><path d="M8.94488915,6.26716561 C8.83639913,6.32308648 8.76500819,6.43162392 8.75662403,6.55338982 C8.75662403,6.61461425 9.23111336,7.84369467 10.0178473,9.81359069 C11.3739684,13.2054241 11.3418256,13.1380772 11.5469274,13.1380772 C11.6085546,13.1341188 11.6685986,13.1168888 11.7229476,13.0875671 C11.8147843,13.0324651 11.8056006,13.0554242 12.3887633,11.6885888 L12.7377426,10.8697121 L13.5566193,10.5207328 C14.96172,9.91920283 14.9035567,9.94828443 14.96172,9.83961107 C15.0275362,9.71869282 15.0030464,9.56410114 14.9096792,9.47838694 C14.8071283,9.38501968 9.18672565,6.27481866 9.09029717,6.25645133 C9.04437885,6.25032889 8.97703197,6.25645133 8.94488915,6.26869622 L8.94488915,6.26716561 Z M2.52091588,8.15746987 C2.02194125,8.28079012 1.64518252,8.69070363 1.56428417,9.19828517 C1.51094395,9.58822538 1.64090019,9.98091924 1.91632464,10.2620596 C2.09142004,10.4293205 2.30810654,10.5466483 2.54387505,10.6018552 C2.67703818,10.6248144 2.88979307,10.8467529 3.14081323,11.2186913 C3.45152721,11.6855276 3.57091485,12.0482824 3.57397607,12.505935 C3.57397607,13.1227711 3.33060896,13.6447094 2.72601772,14.3258311 C2.63379725,14.4202051 2.55499747,14.5268165 2.49183428,14.6426676 C2.46428329,14.7482797 2.54387505,14.9151163 2.64948719,14.9686876 C2.84081353,15.0681773 2.95254811,15.014606 3.24795598,14.6778716 C3.96428181,13.8589949 4.28724068,13.0140977 4.18468976,12.2288944 C4.10815922,11.6671603 3.89693494,11.1773649 3.5066292,10.661549 C3.42397622,10.5498145 3.38877217,10.4824676 3.41020072,10.4732839 C3.49744553,10.4426717 3.73622081,10.2268556 3.8204044,10.0998149 C3.97394866,9.88702761 4.05156882,9.62883276 4.04081235,9.36665235 C4.04081235,9.16767296 4.02550624,9.06512204 3.97958792,8.94114257 C3.85254723,8.61971431 3.56632302,8.33042888 3.24489476,8.20644941 C3.01181089,8.12806051 2.76243405,8.11118935 2.52091588,8.15746987 Z M3.02907865,8.81257126 C3.17405122,8.87573382 3.29217437,8.98789662 3.36275179,9.12940769 C3.40867011,9.22124433 3.41938439,9.28399937 3.40867011,9.42787678 C3.40495668,9.62542196 3.3004298,9.80733339 3.13162957,9.91001916 C3.03979292,9.97124359 2.99081338,9.98348848 2.79948704,9.98348848 C2.60969131,9.98348848 2.55918115,9.97124359 2.46887512,9.91001916 C2.40765069,9.86869267 2.32193649,9.78297847 2.27754878,9.72175404 C2.20714068,9.61920312 2.19795702,9.57787663 2.19795702,9.37889724 C2.19795702,9.1737954 2.20714068,9.14165257 2.28673244,9.02685677 C2.45395851,8.79109052 2.76193383,8.70219043 3.02907865,8.81257126 L3.02907865,8.81257126 Z M4.66530153,9.30542792 L4.67448519,9.63144801 L4.86734214,9.68042755 C6.22888505,10.0156648 7.64183691,10.0883902 9.03060335,9.89471306 C9.13360028,9.88212501 9.23580383,9.86372837 9.3367255,9.83961107 C9.34131733,9.8304274 9.29846023,9.69879488 9.23570519,9.53961136 L9.12397061,9.25797899 L8.96325648,9.27634632 C8.35101218,9.34981563 8.13672668,9.36052991 7.52295177,9.36052991 C6.57244251,9.36359113 5.7734637,9.26869326 4.97907673,9.06512204 L4.65305664,8.98246906 L4.66377092,9.30389731 L4.66530153,9.30542792 Z" id="Shape"></path></g></g></g></svg></figure>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Lasso/Lasso.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const IconLasso = _sfc_main$2;
const _sfc_main$1 = {
  __name: "LassoRemove",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "ibg icon__lasso" }, _attrs))}><svg width="15" height="16" viewBox="0 0 15 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.82138 0.00849829C5.54179 0.117172 4.64332 0.31309 3.69587 0.686559C2.66271 1.09523 1.83465 1.62635 1.18567 2.29829C0.590262 2.91666 0.251997 3.50747 0.0683234 4.24676C0.0132214 4.48094 0.000976562 4.5988 0.000976562 4.99523C0.00403778 5.61053 0.0775071 5.94268 0.337711 6.50288C0.601135 7.02664 0.955046 7.49973 1.38312 7.90033L1.50557 8.00288L1.73822 7.84676C1.86373 7.75798 1.98771 7.68298 2.00761 7.6738C2.0321 7.66767 1.93414 7.55288 1.75812 7.38298C0.326997 5.99778 0.233629 4.29115 1.50863 2.83707C1.90812 2.38554 2.53108 1.92482 3.19995 1.58197C5.99944 0.155437 10.0188 0.331457 12.4846 1.98911C13.4948 2.66717 14.1224 3.50135 14.3214 4.4289C14.3765 4.68604 14.3857 5.24931 14.3367 5.50033C14.2066 6.16308 13.8607 6.77074 13.2806 7.35237C12.9974 7.62941 12.9775 7.65849 13.0295 7.69216C13.0601 7.709 13.1887 7.77941 13.3112 7.84982L13.5377 7.97533L13.778 7.73196C15.1188 6.36819 15.3744 4.64166 14.4591 3.12482C14.0658 2.47125 13.3433 1.78095 12.5581 1.3034C11.4959 0.660539 10.1948 0.235029 8.77138 0.0620698C8.41321 0.0207433 7.11832 -0.0175221 6.82138 0.00849829Z" fill="#1253A2"></path><path d="M8.94608 6.26702C8.83759 6.32294 8.7662 6.43148 8.75781 6.55325C8.75781 6.61447 9.2323 7.84355 10.019 9.81345C11.3752 13.2053 11.343 13.1379 11.5481 13.1379C11.6097 13.134 11.6698 13.1167 11.7241 13.0874C11.816 13.0323 11.8068 13.0553 12.39 11.6884L12.7389 10.8696L13.5578 10.5206C14.9629 9.91906 14.9047 9.94814 14.9629 9.83947C15.0287 9.71855 15.0042 9.56396 14.9109 9.47824C14.8083 9.38488 9.18791 6.27468 9.09148 6.25631C9.04557 6.25019 8.97822 6.25631 8.94608 6.26855V6.26702ZM2.5221 8.15733C2.02313 8.28065 1.64637 8.69056 1.56547 9.19814C1.51213 9.58808 1.64209 9.98078 1.91751 10.2619C2.09261 10.4292 2.30929 10.5465 2.54506 10.6017C2.67823 10.6247 2.89098 10.8466 3.142 11.2185C3.45271 11.6854 3.5721 12.0481 3.57516 12.5058C3.57516 13.1226 3.3318 13.6446 2.72721 14.3257C2.63498 14.4201 2.55619 14.5267 2.49302 14.6425C2.46547 14.7481 2.54506 14.915 2.65067 14.9685C2.842 15.068 2.95374 15.0145 3.24914 14.6777C3.96547 13.8589 4.28843 13.014 4.18588 12.2288C4.10935 11.667 3.89812 11.1772 3.50782 10.6614C3.42516 10.5497 3.38996 10.4823 3.41139 10.4731C3.49863 10.4425 3.73741 10.2267 3.82159 10.0997C3.97514 9.88688 4.05276 9.62869 4.042 9.36651C4.042 9.16753 4.02669 9.06498 3.98078 8.941C3.85373 8.61957 3.56751 8.33029 3.24608 8.20631C3.013 8.12792 2.76362 8.11105 2.5221 8.15733ZM3.03027 8.81243C3.17524 8.87559 3.29336 8.98775 3.36394 9.12926C3.40986 9.2211 3.42057 9.28386 3.40986 9.42773C3.40614 9.62528 3.30162 9.80719 3.13282 9.90988C3.04098 9.9711 2.992 9.98334 2.80067 9.98334C2.61088 9.98334 2.56037 9.9711 2.47006 9.90988C2.40884 9.86855 2.32312 9.78284 2.27874 9.72161C2.20833 9.61906 2.19914 9.57773 2.19914 9.37875C2.19914 9.17365 2.20833 9.14151 2.28792 9.02671C2.45515 8.79095 2.76312 8.70205 3.03027 8.81243ZM4.66649 9.30528L4.67567 9.6313L4.86853 9.68028C6.23007 10.0155 7.64302 10.0882 9.03179 9.89457C9.13479 9.88198 9.23699 9.86359 9.33791 9.83947C9.3425 9.83028 9.29965 9.69865 9.23689 9.53947L9.12516 9.25784L8.96444 9.2762C8.3522 9.34967 8.13791 9.36039 7.52414 9.36039C6.57363 9.36345 5.77465 9.26855 4.98026 9.06498L4.65424 8.98233L4.66496 9.30375L4.66649 9.30528Z" fill="#1253A2"></path><mask id="path-3-outside-1_1271_28" maskUnits="userSpaceOnUse" x="2.90039" y="0.399902" width="10" height="10" fill="black"><rect fill="white" x="2.90039" y="0.399902" width="10" height="10"></rect><path d="M3.98178 1.59807L4.03494 1.53445C4.1944 1.37499 4.44194 1.35727 4.62097 1.4813L4.68459 1.53445L7.57539 4.42535L10.4662 1.53445C10.6456 1.35505 10.9364 1.35505 11.1158 1.53445C11.2952 1.71385 11.2952 2.00471 11.1158 2.1841L8.22495 5.0749L11.1158 7.9657C11.2753 8.12516 11.293 8.3727 11.169 8.55173L11.1158 8.61535C10.9564 8.77482 10.7088 8.79254 10.5298 8.66851L10.4662 8.61535L7.57539 5.72446L4.68459 8.61535C4.5052 8.79475 4.21434 8.79475 4.03494 8.61535C3.85554 8.43596 3.85554 8.1451 4.03494 7.9657L6.92583 5.0749L4.03494 2.1841C3.87547 2.02464 3.85776 1.77711 3.98178 1.59807Z"></path></mask><path d="M3.98178 1.59807L4.03494 1.53445C4.1944 1.37499 4.44194 1.35727 4.62097 1.4813L4.68459 1.53445L7.57539 4.42535L10.4662 1.53445C10.6456 1.35505 10.9364 1.35505 11.1158 1.53445C11.2952 1.71385 11.2952 2.00471 11.1158 2.1841L8.22495 5.0749L11.1158 7.9657C11.2753 8.12516 11.293 8.3727 11.169 8.55173L11.1158 8.61535C10.9564 8.77482 10.7088 8.79254 10.5298 8.66851L10.4662 8.61535L7.57539 5.72446L4.68459 8.61535C4.5052 8.79475 4.21434 8.79475 4.03494 8.61535C3.85554 8.43596 3.85554 8.1451 4.03494 7.9657L6.92583 5.0749L4.03494 2.1841C3.87547 2.02464 3.85776 1.77711 3.98178 1.59807Z" fill="#AD0B0A"></path><path d="M3.98178 1.59807L3.21438 0.956912L3.18545 0.991531L3.15976 1.02861L3.98178 1.59807ZM4.03494 1.53445L3.32783 0.827343L3.29621 0.858969L3.26753 0.893291L4.03494 1.53445ZM4.62097 1.4813L5.26213 0.713888L5.22751 0.684964L5.19043 0.659276L4.62097 1.4813ZM4.68459 1.53445L5.39171 0.827355L5.36008 0.795724L5.32575 0.767043L4.68459 1.53445ZM7.57539 4.42535L6.86827 5.13244L7.57539 5.83958L8.28251 5.13244L7.57539 4.42535ZM10.4662 1.53445L9.75908 0.827343L9.75907 0.827356L10.4662 1.53445ZM11.1158 1.53445L11.8229 0.827344L11.8229 0.827343L11.1158 1.53445ZM11.1158 2.1841L11.8229 2.89122L11.8229 2.89121L11.1158 2.1841ZM8.22495 5.0749L7.51785 4.36778L6.81071 5.0749L7.51785 5.78202L8.22495 5.0749ZM11.1158 7.9657L11.8229 7.25859L11.8229 7.25858L11.1158 7.9657ZM11.169 8.55173L11.9364 9.19288L11.9653 9.15827L11.991 9.12119L11.169 8.55173ZM11.1158 8.61535L11.8229 9.32246L11.8546 9.29083L11.8833 9.25651L11.1158 8.61535ZM10.5298 8.66851L9.88866 9.43592L9.92327 9.46484L9.96035 9.49053L10.5298 8.66851ZM10.4662 8.61535L9.75907 9.32245L9.7907 9.35409L9.82504 9.38277L10.4662 8.61535ZM7.57539 5.72446L8.28251 5.01736L7.57539 4.31022L6.86827 5.01736L7.57539 5.72446ZM4.68459 8.61535L5.3917 9.32246L5.39171 9.32245L4.68459 8.61535ZM4.03494 8.61535L3.32783 9.32246L3.32783 9.32246L4.03494 8.61535ZM4.03494 7.9657L3.32784 7.25858L3.32783 7.25859L4.03494 7.9657ZM6.92583 5.0749L7.63293 5.78202L8.34007 5.0749L7.63293 4.36778L6.92583 5.0749ZM4.03494 2.1841L3.32783 2.89121L3.32784 2.89122L4.03494 2.1841ZM4.74919 2.23923L4.80235 2.17561L3.26753 0.893291L3.21438 0.956912L4.74919 2.23923ZM4.74205 2.24156C4.55363 2.42998 4.26275 2.44965 4.05151 2.30332L5.19043 0.659276C4.62113 0.264887 3.83518 0.319996 3.32783 0.827343L4.74205 2.24156ZM3.97981 2.2487L4.04343 2.30186L5.32575 0.767043L5.26213 0.713888L3.97981 2.2487ZM3.97747 2.24155L6.86827 5.13244L8.28251 3.71825L5.39171 0.827355L3.97747 2.24155ZM8.28251 5.13244L11.1733 2.24154L9.75907 0.827356L6.86827 3.71825L8.28251 5.13244ZM11.1733 2.24156C10.9622 2.45268 10.6199 2.45268 10.4087 2.24156L11.8229 0.827343C11.253 0.257422 10.329 0.257422 9.75908 0.827343L11.1733 2.24156ZM10.4087 2.24156C10.1976 2.03043 10.1976 1.68812 10.4087 1.477L11.8229 2.89121C12.3929 2.32129 12.3929 1.39726 11.8229 0.827344L10.4087 2.24156ZM10.4087 1.47699L7.51785 4.36778L8.93204 5.78202L11.8229 2.89122L10.4087 1.47699ZM7.51785 5.78202L10.4087 8.67282L11.8229 7.25858L8.93204 4.36778L7.51785 5.78202ZM10.4087 8.67281C10.2203 8.48438 10.2006 8.1935 10.347 7.98227L11.991 9.12119C12.3854 8.55189 12.3303 7.76594 11.8229 7.25859L10.4087 8.67281ZM10.4016 7.91058L10.3484 7.9742L11.8833 9.25651L11.9364 9.19288L10.4016 7.91058ZM10.4087 7.90825C10.5972 7.71983 10.888 7.70016 11.0993 7.84649L9.96035 9.49053C10.5297 9.88492 11.3156 9.82981 11.8229 9.32246L10.4087 7.90825ZM11.171 7.90109L11.1073 7.84794L9.82504 9.38277L9.88866 9.43592L11.171 7.90109ZM11.1733 7.90826L8.28251 5.01736L6.86827 6.43155L9.75907 9.32245L11.1733 7.90826ZM6.86827 5.01736L3.97747 7.90826L5.39171 9.32245L8.28251 6.43155L6.86827 5.01736ZM3.97749 7.90825C4.18861 7.69712 4.53092 7.69712 4.74205 7.90825L3.32783 9.32246C3.89775 9.89238 4.82178 9.89238 5.3917 9.32246L3.97749 7.90825ZM4.74205 7.90825C4.95317 8.11938 4.95317 8.46168 4.74205 8.67281L3.32783 7.25859C2.75791 7.82851 2.75791 8.75254 3.32783 9.32246L4.74205 7.90825ZM4.74203 8.67282L7.63293 5.78202L6.21874 4.36778L3.32784 7.25858L4.74203 8.67282ZM7.63293 4.36778L4.74203 1.47699L3.32784 2.89122L6.21874 5.78202L7.63293 4.36778ZM4.74205 1.477C4.93046 1.66542 4.95014 1.9563 4.8038 2.16753L3.15976 1.02861C2.76538 1.59792 2.82048 2.38386 3.32783 2.89121L4.74205 1.477Z" fill="white" mask="url(#path-3-outside-1_1271_28)"></path></svg></figure>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Lasso/LassoRemove/LassoRemove.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const IconLassoRemove = _sfc_main$1;
function getNearCoords(fromPoint, toCoords, n) {
  const sortedByLengthCoords = [...toCoords[0]].sort((a, b) => {
    const lengthA = window.ymaps.coordSystem.geo.getDistance(fromPoint, a);
    const lengthB = window.ymaps.coordSystem.geo.getDistance(fromPoint, b);
    return lengthA - lengthB;
  });
  return sortedByLengthCoords.slice(0, n);
}
async function getMinLengthRoute(fromPoint, toCoords) {
  const nearCoords = getNearCoords(fromPoint, toCoords, 24);
  let minRoute = await window.ymaps.route([nearCoords[0], fromPoint]);
  let minLength = minRoute.getLength();
  const promises = nearCoords.map((el) => window.ymaps.route([el, fromPoint]));
  const routes = await Promise.all(promises);
  routes.forEach((route) => {
    const routeLength = route.getLength();
    if (routeLength < minLength) {
      minLength = routeLength;
      minRoute = route;
    }
  });
  return minRoute;
}
const _sfc_main = {
  __name: "Field",
  __ssrInlineRender: true,
  props: {
    isCanSelect: {
      default: false,
      type: Boolean
    },
    item: {
      default: {
        id: 0,
        title: "\u0410\u0434\u0440\u0435\u0441",
        key: "address",
        required: false,
        focus: false,
        value: {
          text: null,
          coords: []
        },
        options: [],
        lockedOptions: []
      },
      type: Object
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isShowMap: {
      default: false,
      type: Boolean
    },
    isShowMap: {
      default: false,
      type: Boolean
    },
    isCountDistance: {
      default: false,
      type: Boolean
    },
    polygonCoords: {
      default: false
    },
    coords: {
      default: [55.755864, 37.617698],
      type: Array
    },
    mapZoom: {
      default: 15,
      type: Number
    },
    mapStyles: {
      default: {},
      type: Object
    },
    showInputLabel: {
      default: false,
      type: Boolean
    },
    placeholder: {
      default: null,
      type: String
    },
    isShowSubstring: {
      default: true,
      type: Boolean
    },
    isShowInputButton: {
      default: false,
      type: Boolean
    },
    autoCompleteStyles: {
      default: {},
      type: Object
    }
  },
  emits: ["changeValue", "selectAddress"],
  setup(__props, { expose: __expose, emit: __emit }) {
    useUserStore();
    const dayjs = useDayjs();
    let map = null;
    const autocompleteComponent = ref(null);
    ref(null);
    let polygon = shallowRef(null);
    let multiRoute = shallowRef(null);
    let positionClick = shallowRef(null);
    let isRouteLoading = ref(false);
    let lastRoute = shallowRef(null);
    let lastPoint = shallowRef(null);
    let routeLength = ref(null);
    let markers = ref([]);
    let isSelectActive = ref(false);
    let address = ref(null);
    let localOptions = ref([]);
    let value = shallowRef({
      text: null,
      coords: [55.755864, 37.617698]
    });
    let routeOptions = {
      options: {
        wayPointVisible: false,
        routeActiveStrokeWidth: 3,
        routeActiveStrokeColor: "#aa47d1"
      }
    };
    const props = __props;
    const emit = __emit;
    const renderRoute = async (positionRoute, data) => {
      var _a, _b, _c, _d, _e, _f, _g;
      isRouteLoading.value = true;
      if (positionRoute) {
        positionClick.value = positionRoute;
      } else if (positionRoute === null) {
        lastRoute.value && ((_a = map.geoObjects) == null ? void 0 : _a.remove(lastRoute.value));
        emit("changeValue", "0");
        return;
      }
      positionClick.value = [(+positionClick.value[0]).toFixed(6), (+positionClick.value[1]).toFixed(6)];
      multiRoute.value = await getMinLengthRoute(positionClick.value, props.polygonCoords);
      multiRoute.value.options.set(routeOptions.options);
      const myObjects = ymaps.geoQuery({
        type: "Point",
        coordinates: positionClick.value
      });
      const isInside = !!((_b = myObjects == null ? void 0 : myObjects.searchInside(polygon.value)) == null ? void 0 : _b.getLength());
      if (isInside) {
        renderPoint(positionClick.value);
        multiRoute.value.options.set("visible", false);
      } else {
        renderPoint(positionClick.value);
      }
      map == null ? void 0 : map.geoObjects.add(multiRoute.value);
      lastRoute.value && ((_c = map == null ? void 0 : map.geoObjects) == null ? void 0 : _c.remove(lastRoute.value));
      lastRoute.value = multiRoute.value;
      let between = spaceBetween((_e = (_d = multiRoute.value.getWayPoints().get(0)) == null ? void 0 : _d.geometry) == null ? void 0 : _e.getCoordinates(), (_g = (_f = multiRoute.value.getWayPoints().get(1)) == null ? void 0 : _f.geometry) == null ? void 0 : _g.getCoordinates());
      between = (await Promise.all([between]))[0];
      if (isInside) {
        emit("changeValue", "0");
      } else {
        emit("changeValue", between);
      }
      let doneAddress = null;
      if (data == null ? void 0 : data.search) {
        doneAddress = data.search;
      } else {
        doneAddress = positionClick.value.join(", ");
      }
      isInside ? between = "0" : 0;
      const historyItem = { address: doneAddress, distance: between, date: dayjs().format("DD.MM.YYYY"), time: dayjs().format("HH:mm") };
      emit("selectAddress", historyItem);
      isRouteLoading.value = false;
    };
    const removeRoute = () => {
      var _a;
      multiRoute.value && ((_a = map == null ? void 0 : map.geoObjects) == null ? void 0 : _a.remove(multiRoute.value));
      emit("changeValue", "0");
    };
    const spaceBetween = async (point1, point2) => {
      var _a, _b, _c, _d;
      let arrayPromises = [];
      let res = null;
      arrayPromises.push(ymaps.geocode(point1), ymaps.geocode(point2));
      res = await Promise.all(arrayPromises);
      const point1Coords = (_b = (_a = res[0].geoObjects.get(0)) == null ? void 0 : _a.geometry) == null ? void 0 : _b.getCoordinates();
      const point2Coords = (_d = (_c = res[1].geoObjects.get(0)) == null ? void 0 : _c.geometry) == null ? void 0 : _d.getCoordinates();
      arrayPromises = [];
      let lengthRoute = Math.round(ymaps.coordSystem.geo.getDistance(point1Coords, point2Coords));
      if (lengthRoute > 1e4) {
        lengthRoute = `${(lengthRoute / 1e3).toFixed(0)} \u043A\u043C`;
      } else if (lengthRoute > 1e3) {
        lengthRoute = `${roundAndFormat(lengthRoute / 1e3)} \u043A\u043C`;
      } else {
        lengthRoute = `${lengthRoute.toFixed(0)} \u043C`;
      }
      routeLength.value = lengthRoute;
      return lengthRoute;
    };
    function roundAndFormat(number) {
      let roundedNumber = number.toFixed(1);
      let floatNumber = parseFloat(roundedNumber);
      if (floatNumber % 1 === 0) {
        return floatNumber.toFixed(0);
      } else {
        return roundedNumber;
      }
    }
    const setMarkers = () => {
      map.geoObjects.removeAll();
      for (let marker of markers.value) {
        var myPlacemark = new ymaps.Placemark(marker);
        map.geoObjects.add(myPlacemark);
      }
    };
    const selectPoligon = (status) => {
      let copyMap = toRaw(map);
      let copyPolygon = toRaw(polygon.value);
      let polygonOptions = {
        strokeColor: "#0000ff",
        fillColor: "#8080ff",
        interactivityModel: "default#transparent",
        strokeWidth: 4,
        opacity: 0.7
      };
      let canvasOptions = {
        strokeStyle: "#0000ff",
        lineWidth: 4,
        opacity: 0.7
      };
      const drawLineOverMap = () => {
        var canvas = document.querySelector("#draw-canvas");
        var ctx2d = canvas.getContext("2d");
        var drawing = false;
        var coordinates = [];
        var rect = map.container.getParentElement().getBoundingClientRect();
        canvas.style.width = rect.width + "px";
        canvas.style.height = rect.height + "px";
        canvas.width = rect.width;
        canvas.height = rect.height;
        ctx2d.strokeStyle = canvasOptions.strokeStyle;
        ctx2d.lineWidth = canvasOptions.lineWidth;
        canvas.style.opacity = canvasOptions.opacity;
        ctx2d.clearRect(0, 0, canvas.width, canvas.height);
        canvas.style.display = "block";
        canvas.onmousedown = function(e) {
          drawing = true;
          coordinates.push([e.offsetX, e.offsetY]);
        };
        canvas.onmousemove = function(e) {
          if (drawing) {
            var last = coordinates[coordinates.length - 1];
            ctx2d.beginPath();
            ctx2d.moveTo(last[0], last[1]);
            ctx2d.lineTo(e.offsetX, e.offsetY);
            ctx2d.stroke();
            coordinates.push([e.offsetX, e.offsetY]);
          }
        };
        return new Promise(function(resolve) {
          canvas.onmouseup = function(e) {
            coordinates.push([e.offsetX, e.offsetY]);
            canvas.style.display = "none";
            drawing = false;
            coordinates = coordinates.map(function(x) {
              return [x[0] / canvas.width, x[1] / canvas.height];
            });
            resolve(coordinates);
          };
        });
      };
      const filterMarkers = (myPolygon) => {
        for (let marker of markers.value) {
          if (myPolygon.geometry.contains(marker))
            ;
        }
      };
      copyMap.geoObjects.remove(copyPolygon);
      if (status) {
        isSelectActive.value = true;
        drawLineOverMap().then(function(coordinates) {
          var bounds = copyMap.getBounds();
          coordinates = coordinates.map(function(x) {
            return [bounds[0][0] + (1 - x[1]) * (bounds[1][0] - bounds[0][0]), bounds[0][1] + x[0] * (bounds[1][1] - bounds[0][1])];
          });
          coordinates = coordinates.filter(function(_2, index) {
            return index % 3 === 0;
          });
          if (copyPolygon) {
            copyMap.geoObjects.remove(copyPolygon);
          }
          copyPolygon = new ymaps.Polygon([coordinates], {}, polygonOptions);
          polygon.value = copyPolygon;
          copyPolygon.options.setParent(copyMap.options);
          copyPolygon.geometry.setMap(copyMap);
          copyMap.geoObjects.add(copyPolygon);
          filterMarkers(copyPolygon);
          isSelectActive.value = false;
          map = copyMap;
        });
      } else {
        console.log(null);
      }
    };
    const resetRoute = () => {
      removeRoute();
      removePoint();
      map == null ? void 0 : map.panTo(props.coords, {
        flying: false
      });
      autocompleteComponent.value.reset();
    };
    __expose({
      resetRoute
    });
    const renderPoint = (position) => {
      var _a;
      (_a = map.geoObjects) == null ? void 0 : _a.remove(lastPoint.value);
      lastPoint.value = new ymaps.Placemark(position);
      map.geoObjects.add(lastPoint.value);
    };
    const removePoint = () => {
      var _a;
      (_a = map.geoObjects) == null ? void 0 : _a.remove(lastPoint.value);
    };
    const changeValue = (data, action = "show") => {
      var _a, _b, _c, _d;
      if (props.isCountDistance) {
        let position = ((_a = data == null ? void 0 : data.value) == null ? void 0 : _a.coords) ? (_b = data == null ? void 0 : data.value) == null ? void 0 : _b.coords : null;
        let search = data == null ? void 0 : data.search;
        let coords = search == null ? void 0 : search.split(",");
        if ((coords == null ? void 0 : coords.length) > 0 && !isNaN(+(coords == null ? void 0 : coords[0])) && !isNaN(+(coords == null ? void 0 : coords[1]))) {
          position = [+coords[0], +coords[1]];
        }
        if (position) {
          position = [+position[0], +position[1]];
          map == null ? void 0 : map.panTo(position, {
            flying: false
          });
          action == "show" && renderPoint(position);
          action == "calculate" && renderRoute(position, data);
        }
      }
      if ((data == null ? void 0 : data.value) && !props.isCountDistance) {
        markers.value.splice(
          markers.value.findIndex((p) => _.isEqual(p, value.value.coords)),
          1,
          data.value.coords
        );
        value.value = data.value;
      } else {
        markers.value = [];
        value.value = {
          text: null,
          coords: []
        };
      }
      if (props.isShowMap && ((_c = data == null ? void 0 : data.value) == null ? void 0 : _c.coords) && !props.isCountDistance) {
        map == null ? void 0 : map.panTo([(_d = data == null ? void 0 : data.value) == null ? void 0 : _d.coords], {
          flying: false
        });
        !props.isCountDistance && setMarkers();
      } else {
        !props.isCountDistance && (map == null ? void 0 : map.panTo(props.isCountDistance ? props.coords : [55.755864, 37.617698], {
          flying: false
        }));
        !props.isCountDistance && setMarkers();
      }
      if (!props.isCountDistance) {
        emit("changeValue", data);
      }
    };
    const searchOptions = async (data) => {
      const getOptions = async (search) => {
        let request = await fetch(`https://compas.pro/api/map/geocode?address=${search}`, {
          method: "GET"
          // headers: {
          // 	Authorization: `Bearer ${userStore.userToken}`,
          // },
        });
        return await request.json();
      };
      const setOptions = (gettingData2) => {
        localOptions.value = gettingData2.map(
          (option) => option = {
            label: {
              text: option.text
            },
            value: {
              text: option.text,
              coords: option.coords
            }
          }
        );
      };
      let gettingData = await getOptions(data.value);
      setOptions(gettingData);
    };
    watch(
      () => props.item.value,
      () => {
        value.value = props.item.value ? JSON.parse(JSON.stringify(props.item.value)) : {
          text: null,
          coords: [55.755864, 37.617698]
        };
        localOptions.value.push({
          label: {
            text: props.item.value ? props.item.value.text : null
          },
          value: props.item.value ? props.item.value : {
            text: null,
            coords: [55.755864, 37.617698]
          }
        });
      }
    );
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "map" }, _attrs))}>`);
      if (props.isCanSelect) {
        _push(`<div class="map__actions">`);
        _push(ssrRenderComponent(IconLasso, {
          style: !unref(isSelectActive) ? null : { display: "none" },
          onClick: () => selectPoligon(true)
        }, null, _parent));
        _push(ssrRenderComponent(IconLassoRemove, {
          style: unref(isSelectActive) ? null : { display: "none" },
          onClick: () => selectPoligon(false)
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(AppAutocomplete, {
        class: "map__autocompete",
        ref_key: "autocompleteComponent",
        ref: autocompleteComponent,
        style: props.autoCompleteStyles,
        item: {
          id: 0,
          required: props.item.required,
          title: props.item.title,
          value: props.isCountDistance ? unref(address) : unref(value),
          type: "address",
          placeholder: props.placeholder,
          focus: props.item.focus,
          key: props.item.key,
          options: unref(localOptions),
          lockedOptions: []
        },
        isReadOnly: props.isReadOnly,
        isCanCreate: false,
        isLink: false,
        isShowId: false,
        anotherTitle: null,
        "is-show-label": __props.showInputLabel,
        placeholder: props.placeholder,
        isShowSubstring: props.isShowSubstring,
        isLoading: unref(isRouteLoading),
        isShowButton: props.isShowInputButton,
        isCountDistance: props.isCountDistance,
        onChangeValue: (data) => changeValue(data),
        onSearchOptions: (data) => searchOptions(data),
        onClickButton: (data) => changeValue(data, "calculate")
      }, null, _parent));
      if (unref(value) && unref(value).text) {
        _push(ssrRenderComponent(AppCopy, {
          text: unref(value).text,
          buttonTitle: "\u0421\u043A\u043E\u043F\u0438\u0440\u043E\u0432\u0430\u0442\u044C \u0430\u0434\u0440\u0435\u0441"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (props.isShowMap) {
        _push(`<div class="map__container"><div id="map" class="yandex-container" style="${ssrRenderStyle(__props.mapStyles)}"></div><canvas id="draw-canvas" style="${ssrRenderStyle({ "position": "absolute", "left": "0", "top": "0", "display": "none" })}"></canvas><a class="map__link" target="_blank"${ssrRenderAttr("href", `https://maps.yandex.ru/?text=${unref(value).coords.join("+")}`)}> \u041E\u0442\u043A\u0440\u044B\u0442\u044C \u0432 \u042F\u043D\u0434\u0435\u043A\u0441 \u043A\u0430\u0440\u0442\u0430\u0445 </a></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Map/Field/Field.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AppMap = _sfc_main;

export { AppMap as A };
//# sourceMappingURL=Field-d36cf7e6.mjs.map
