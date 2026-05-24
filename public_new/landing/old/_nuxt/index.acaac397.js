import{e as u,c as f,A as t,p as e,G as _,l as i,j as d,i as a,B as b,_ as x,h as v,k as c,N as g,F as k,u as y,r as T,o as h,a as p,d as w}from"./entry.6b86936e.js";import{A as C}from"./Breadcrambs.aeff7a79.js";import{_ as A}from"./program.926202e5.js";import{C as q}from"./CompositeBlock.6f698b26.js";import{A as m}from"./AppSection.b9b21682.js";import{T as B}from"./TariffsSlider.649107de.js";import{A as H}from"./AppTable.df999529.js";import{C as S}from"./Social.e7dc3f07.js";import"./Slider.e9300f8e.js";import"./Validate.a77d12e9.js";import"./lodash.e91eec81.js";import"./Input.803f24f2.js";import"./api.1dca5ca8.js";import"./FansyBox.f33efee2.js";import"./Select.929fd1f4.js";import"./ButtonText.185aad6f.js";import"./Save.9b7b5ae6.js";import"./Checkbox.9d121df3.js";import"./_plugin-vue_export-helper.c27b6911.js";import"./Field.39ad8e9f.js";import"./dayjs.45c38214.js";const L={class:"programm__desc"},M=a("figure",{class:"ibg programm__image"},[a("img",{src:A,alt:""})],-1),N={__name:"Programm",props:{title:{default:null,type:String},desc:{default:null,type:String}},setup(n){const l=n;return(s,r)=>{const o=x;return u(),f(q,{class:"programm"},{content:t(()=>[e(_,{class:"programm__title"},{default:t(()=>[i(d(l.title),1)]),_:1}),a("div",L,d(l.desc),1),e(o,{class:"programm__link",to:"/auth/registration"},{default:t(()=>[e(b,{class:"button_blue"},{default:t(()=>[i(" Попробовать бесплатно ")]),_:1})]),_:1})]),image:t(()=>[M]),_:1})}}},P=N,$=a("div",{class:"tarification__desc"},[a("p",{class:"tarification__text"}," Помимо очевидных различий тарифных планов по кол-ву пользователей или объему хранилища есть пожалуй самое важное различие в проценте скидки на комиссию за оплату штрафов. Если у вас много штрафов или большой автопарк рекомендуем перейти на профессиональный тариф и платить комиссию всего 1,8%, если у вас мало штрафов или отслеживаете штрафы по одной машине, рекомендуем остаться на бесплатном тарифе. ")],-1),R={__name:"Tariffs",setup(n){let l={tableKeys:[{id:1,title:"",key:"module",width:"200px",enabled:!0,sort_order:null,type:"text",is_plural:0,external_link:"",is_external_link:0,is_link:0,required:0,fixed:!0,index:0,fixTarget:"0px",read_only:0,unit:null,mask:null,can_edit:0,color:"",is_hidden:0,visible_always:0,options:[]},{id:2,isHTMLTitle:!0,alternativeTitle:`
                    <div class="table-cell__title">
                        Бесплатный
                    </div> <!--  
                    <div class="table-cell__subtitle">
                        6 инструментов
                    </div>
                    <div class="table-cell__desc">
                        Минимальный набор инструментов для старта сквозной аналитики
                    </div> -->
                `,title:"Бесплатный",key:"free",width:"300px",enabled:!0,sort_order:null,type:"text",is_plural:0,external_link:"",is_external_link:0,is_link:0,required:0,fixed:!1,index:0,fixTarget:"0px",read_only:0,unit:null,mask:null,can_edit:0,color:"",is_hidden:0,visible_always:0,options:[]},{id:3,isHTMLTitle:!0,alternativeTitle:`
                    <div class="table-cell__title">
                        Базовый тариф
                    </div> <!--
                    <div class="table-cell__subtitle">
                        7 инструментов
                    </div>
                    <div class="table-cell__desc">
                        Оптимальный набор инструментов для сквозной аналитики, роста трафика, заявок и продаж
                    </div> -->
                `,title:"Базовый тариф",key:"base",width:"300px",enabled:!0,sort_order:null,type:"text",is_plural:0,external_link:"",is_external_link:0,is_link:0,required:0,fixed:!1,index:0,fixTarget:"0px",read_only:0,unit:null,mask:null,can_edit:0,color:"",is_hidden:0,visible_always:0,options:[]},{id:2,isHTMLTitle:!0,alternativeTitle:`
                    <div class="table-cell__title">
                        Бизнес
                    </div> 
                    <!--
                    <div class="table-cell__subtitle">
                        11 инструментов
                    </div>
                    <div class="table-cell__desc">
                        Максимальный набор инструментов для сквозной аналитики, роста трафика, заявок и продаж
                    </div>
                    -->
                `,title:"Бизнес",key:"business",width:"300px",enabled:!0,sort_order:null,type:"text",is_plural:0,external_link:"",is_external_link:0,is_link:0,required:0,fixed:!1,index:0,fixTarget:"0px",read_only:0,unit:null,mask:null,can_edit:0,color:"",is_hidden:0,visible_always:0,options:[]},{id:2,isHTMLTitle:!0,alternativeTitle:`
                    <div class="table-cell__title">
                        Профессиональный
                    </div> <!--
                    <div class="table-cell__subtitle">
                        14 инструментов
                    </div>
                    <div class="table-cell__desc">
                        Максимальный набор инструментов для сквозной аналитики, роста трафика, заявок и продаж
                    </div> -->
                `,title:"Профессиональный",key:"prof",width:"300px",enabled:!0,sort_order:null,type:"text",is_plural:0,external_link:"",is_external_link:0,is_link:0,required:0,fixed:!1,index:0,fixTarget:"0px",read_only:0,unit:null,mask:null,can_edit:0,color:"",is_hidden:0,visible_always:0,options:[]}],tableData:[{module:"Кол-во пользователей",free:"5",base:"10",business:"40",prof:"100"},{module:"Магазин модулей",free:"0%",base:"-10%",business:"-20%",prof:"-40%"},{module:"Обьем хранилища",free:"2.5 гб.",base:"5 гб.",business:"50 гб.",prof:"100 гб."},{module:"Мягкое удаление",free:!1,base:!0,business:!0,prof:!0},{module:'<div class="table__cell-group"> Штрафы ГИБДД <span class="table-cell__desc">Комиссия за оплату</span> </div>',free:"2.7%",base:"2.43%",business:"2.16%",prof:"1.62%"},{module:"Автопарк",free:!0,base:!0,business:!0,prof:!0},{module:"Сотрудники",free:!0,base:!0,business:!0,prof:!0},{module:"Компании",free:!0,base:!0,business:!0,prof:!0}],socketRows:{header:[],body:[]},sortItem:{key:null,order:null},tableFooter:{pages:1,activePage:1,count:25},loaderState:""},s=[{title:"Главная страница",link:"/"},{title:"Тарифы",link:"/tariffs"}];return(r,o)=>(u(),v(k,null,[e(C,{breadcrumbs:c(s)},null,8,["breadcrumbs"]),e(g,null,{default:t(()=>[i(" Тарифы ")]),_:1}),e(P,{class:"tariffs__programm",title:"Программа удобна, интуитивно понятна и проста",desc:"Если у Вас возникнут Вопросы заботливая техподдержка максимально быстро решит их подключившись на прямую на ваш портал."}),e(m,{class:"tarification section_without-background"},{default:t(()=>[e(_,null,{default:t(()=>[i(" Как происходит тарификация? ")]),_:1}),$,e(B)]),_:1}),e(m,{class:"tariffs-equal section_without-background"},{default:t(()=>[e(_,null,{default:t(()=>[i(" Сравнение тарифов ")]),_:1}),e(H,{class:"tariffs-equal__table section_without-background",isTrash:!1,actionType:"view",slug:"equal",isPermanentEdit:!1,table:c(l),activeCategory:null,categories:[],isCanSort:!1,pageTableOnly:!1,isHaveCategories:!1,categoryType:"default"},null,8,["table"])]),_:1}),e(S)],64))}},F=R,re={__name:"index",setup(n){const l=w(),s=y(),r=T(null);return h(()=>{r.value=`${l.public.baseURL}${s.path.replace("/landing","")}`,p({link:[{rel:"canonical",href:r.value}]})}),p({title:"Тарифы на услуги отслеживания штрафов и управления автопарком | Compas.pro",meta:[{name:"description",content:"Сравните тарифы на услуги отслеживания штрафов, управления автопарком и сотрудниками. Узнайте, какой план подходит именно вам — от бесплатного до профессионального тарифа с минимальной комиссией за оплату штрафов."}]}),(o,V)=>(u(),f(F))}};export{re as default};
