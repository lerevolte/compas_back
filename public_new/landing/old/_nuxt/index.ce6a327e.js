import{b as _,c as f,z as t,l as e,I as n,j as i,h as d,g as s,C as m,X as b,f as x,i as u,S as v,F as k,d as g}from"./entry.df2183a2.js";import{_ as y}from"./Breadcrambs.5af7ef89.js";import{_ as h}from"./program.42cae45e.js";import{_ as T}from"./CompositeBlock.7ecfee4b.js";import{_ as c}from"./AppSection.5a308b5b.js";import{_ as w}from"./TariffsSlider.3c38d5ed.js";import{_ as $}from"./AppTable.147bbdf6.js";import{_ as q}from"./Social.4d71110d.js";import"./ButtonText.668cc4f7.js";import"./Slider.9702bf92.js";import"./Restore.dd6f8071.js";import"./Validate.b3b706fd.js";import"./Input.b81690c3.js";import"./Field.c697c9d1.js";import"./dayjs.227ac313.js";import"./LassoRemove.85dddc8f.js";const C={class:"programm__desc"},H=s("figure",{class:"ibg programm__image"},[s("img",{src:h,alt:""})],-1),S={__name:"Programm",props:{title:{default:null,type:String},desc:{default:null,type:String}},setup(a){const l=a;return(r,p)=>{const o=b;return _(),f(T,{class:"programm"},{content:t(()=>[e(n,{class:"programm__title"},{default:t(()=>[i(d(l.title),1)]),_:1}),s("div",C,d(l.desc),1),e(o,{class:"programm__link",to:"/auth/registration"},{default:t(()=>[e(m,{class:"button_blue"},{default:t(()=>[i(" Попробовать бесплатно ")]),_:1})]),_:1})]),image:t(()=>[H]),_:1})}}},L=s("div",{class:"tarification__desc"},[s("p",{class:"tarification__text"},"Помимо очевидных различий тарифных планов по кол-ву пользователей или объему хранилища есть пожалуй самое важное различие в проценте скидки на комиссию за оплату штрафов. Если у вас много штрафов или большой автопарк рекомендуем перейти на профессиональный тариф и платить комиссию всего 1,8%, если у вас мало штрафов или отслеживаете штрафы по одной машине, рекомендуем остаться на бесплатном тарифе.")],-1),B={__name:"Tariffs",setup(a){let l={tableKeys:[{id:1,title:"",key:"module",width:"200px",enabled:!0,sort_order:null,type:"text",is_plural:0,external_link:"",is_external_link:0,is_link:0,required:0,fixed:!0,index:0,fixTarget:"0px",read_only:0,unit:null,mask:null,can_edit:0,color:"",is_hidden:0,visible_always:0,options:[]},{id:2,isHTMLTitle:!0,alternativeTitle:`
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
                `,title:"Профессиональный",key:"prof",width:"300px",enabled:!0,sort_order:null,type:"text",is_plural:0,external_link:"",is_external_link:0,is_link:0,required:0,fixed:!1,index:0,fixTarget:"0px",read_only:0,unit:null,mask:null,can_edit:0,color:"",is_hidden:0,visible_always:0,options:[]}],tableData:[{module:"Кол-во пользователей",free:"5",base:"10",business:"40",prof:"100"},{module:"Магазин модулей",free:"0%",base:"-10%",business:"-20%",prof:"-40%"},{module:"Обьем хранилища",free:"2.5 гб.",base:"5 гб.",business:"50 гб.",prof:"100 гб."},{module:"Мягкое удаление",free:!1,base:!0,business:!0,prof:!0},{module:'<div class="table__cell-group"> Штрафы ГИБДД <span class="table-cell__desc">Комиссия за оплату</span> </div>',free:"3%",base:"2.7%",business:"2.4%",prof:"1.8%"},{module:"Автопарк",free:!0,base:!0,business:!0,prof:!0},{module:"Сотрудники",free:!0,base:!0,business:!0,prof:!0},{module:"Компании",free:!0,base:!0,business:!0,prof:!0}],socketRows:{header:[],body:[]},sortItem:{key:null,order:null},tableFooter:{pages:1,activePage:1,count:25},loaderState:""},r=[{title:"Главная страница",link:"/"},{title:"Тарифы",link:"/tariffs"}];return(p,o)=>(_(),x(k,null,[e(y,{breadcrumbs:u(r)},null,8,["breadcrumbs"]),e(v,null,{default:t(()=>[i(" Тарифы ")]),_:1}),e(S,{class:"tariffs__programm",title:"Программа удобна, интуитивно понятна и проста",desc:"Если у Вас возникнут Вопросы заботливая техподдержка максимально быстро решит их подключившись на прямую на ваш портал."}),e(c,{class:"tarification section_without-background"},{default:t(()=>[e(n,null,{default:t(()=>[i(" Как происходит тарификация? ")]),_:1}),L,e(w)]),_:1}),e(c,{class:"tariffs-equal section_without-background"},{default:t(()=>[e(n,null,{default:t(()=>[i(" Сравнение тарифов ")]),_:1}),e($,{class:"tariffs-equal__table section_without-background",isTrash:!1,actionType:"view",slug:"equal",isPermanentEdit:!1,table:u(l),activeCategory:null,categories:[],isCanSort:!1,pageTableOnly:!1,isHaveCategories:!1,categoryType:"default"},null,8,["table"])]),_:1}),e(q)],64))}},J={__name:"index",setup(a){return g({title:"Тарифы на услуги отслеживания штрафов и управления автопарком | Compas.pro",meta:[{name:"description",content:"Сравните тарифы на услуги отслеживания штрафов, управления автопарком и сотрудниками. Узнайте, какой план подходит именно вам — от бесплатного до профессионального тарифа с минимальной комиссией за оплату штрафов."}],link:[{rel:"canonical",href:"https://compas.pro/tariffs"}]}),(l,r)=>(_(),f(B))}};export{J as default};
