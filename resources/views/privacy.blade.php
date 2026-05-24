<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}a{background-color:transparent}[hidden]{display:none}html{font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;line-height:1.5}*,:after,:before{box-sizing:border-box;border:0 solid #e2e8f0}a{color:inherit;text-decoration:inherit}svg,video{display:block;vertical-align:middle}video{max-width:100%;height:auto}.bg-white{--bg-opacity:1;background-color:#fff;background-color:rgba(255,255,255,var(--bg-opacity))}.bg-gray-100{--bg-opacity:1;background-color:#f7fafc;background-color:rgba(247,250,252,var(--bg-opacity))}.border-gray-200{--border-opacity:1;border-color:#edf2f7;border-color:rgba(237,242,247,var(--border-opacity))}.border-t{border-top-width:1px}.flex{display:flex}.grid{display:grid}.hidden{display:none}.items-center{align-items:center}.justify-center{justify-content:center}.font-semibold{font-weight:600}.h-5{height:1.25rem}.h-8{height:2rem}.h-16{height:4rem}.text-sm{font-size:.875rem}.text-lg{font-size:1.125rem}.leading-7{line-height:1.75rem}.mx-auto{margin-left:auto;margin-right:auto}.ml-1{margin-left:.25rem}.mt-2{margin-top:.5rem}.mr-2{margin-right:.5rem}.ml-2{margin-left:.5rem}.mt-4{margin-top:1rem}.ml-4{margin-left:1rem}.mt-8{margin-top:2rem}.ml-12{margin-left:3rem}.-mt-px{margin-top:-1px}.max-w-6xl{max-width:72rem}.min-h-screen{min-height:100vh}.overflow-hidden{overflow:hidden}.p-6{padding:1.5rem}.py-4{padding-top:1rem;padding-bottom:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.pt-8{padding-top:2rem}.fixed{position:fixed}.relative{position:relative}.top-0{top:0}.right-0{right:0}.shadow{box-shadow:0 1px 3px 0 rgba(0,0,0,.1),0 1px 2px 0 rgba(0,0,0,.06)}.text-center{text-align:center}.text-gray-200{--text-opacity:1;color:#edf2f7;color:rgba(237,242,247,var(--text-opacity))}.text-gray-300{--text-opacity:1;color:#e2e8f0;color:rgba(226,232,240,var(--text-opacity))}.text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}.text-gray-500{--text-opacity:1;color:#a0aec0;color:rgba(160,174,192,var(--text-opacity))}.text-gray-600{--text-opacity:1;color:#718096;color:rgba(113,128,150,var(--text-opacity))}.text-gray-700{--text-opacity:1;color:#4a5568;color:rgba(74,85,104,var(--text-opacity))}.text-gray-900{--text-opacity:1;color:#1a202c;color:rgba(26,32,44,var(--text-opacity))}.underline{text-decoration:underline}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.w-5{width:1.25rem}.w-8{width:2rem}.w-auto{width:auto}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}@media (min-width:640px){.sm\:rounded-lg{border-radius:.5rem}.sm\:block{display:block}.sm\:items-center{align-items:center}.sm\:justify-start{justify-content:flex-start}.sm\:justify-between{justify-content:space-between}.sm\:h-20{height:5rem}.sm\:ml-0{margin-left:0}.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:pt-0{padding-top:0}.sm\:text-left{text-align:left}.sm\:text-right{text-align:right}}@media (min-width:768px){.md\:border-t-0{border-top-width:0}.md\:border-l{border-left-width:1px}.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}@media (prefers-color-scheme:dark){.dark\:bg-gray-800{--bg-opacity:1;background-color:#2d3748;background-color:rgba(45,55,72,var(--bg-opacity))}.dark\:bg-gray-900{--bg-opacity:1;background-color:#1a202c;background-color:rgba(26,32,44,var(--bg-opacity))}.dark\:border-gray-700{--border-opacity:1;border-color:#4a5568;border-color:rgba(74,85,104,var(--border-opacity))}.dark\:text-white{--text-opacity:1;color:#fff;color:rgba(255,255,255,var(--text-opacity))}.dark\:text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}.dark\:text-gray-500{--tw-text-opacity:1;color:#6b7280;color:rgba(107,114,128,var(--tw-text-opacity))}}
        </style>

        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center py-4 sm:pt-0">
            <div class="max-w-12xl mx-auto sm:px-6 lg:px-8">


                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg" style="background-image: radial-gradient(circle at 29% 0, #000, #1c2734 21%)!important;color:#fff">
                    <div class="grid grid-cols-1">
                        <div class="p-6">
                        	<img src="/img/logo.svg" style="width: 400px;max-width: 100%;">
                            <h1>Политика конфиденциальности мобильного приложения</h1>

							<p>Данное приложение обслуживается и предоставляется компанией Compas Dynamics. Если вы установите и будете использовать данное приложение, мы будем собирать данные о вас. В настоящей Политике конфиденциальности приводятся сведения о том, как именно используются данные, собранные нашими приложениями и сайтами, а также о том, куда вы можете обратиться в случае каких-либо вопросов или сомнений.</p>

							<p>Если вы получили доступ к данному приложению через сайт третьего лица, таковое лицо также может собирать данные о вас. В этом случае вам следует ознакомиться с условиями использования и политикой конфиденциальности программного обеспечения и сайта данного лица перед установкой и использованием приложения, чтобы узнать о том, как будут использоваться собранные данные.</p>

							<p>
							<b>1. Какие виды данных мы собираем?</b>
							</p>
							<p>Если вы захотите зарегистрироваться у нас, мы можем попросить вас предоставить такие данные, как имя, имя пользователя, адрес электронной почты, почтовый адрес, возраст, пол, номер телефона и сведения о продукте.  На наших сайтах и в наших приложениях могут проводиться дополнительные опросы, в ходе которых вам будет предложено выразить свое мнение о различных продуктах и услугах. Если вы загрузите какие-либо материалы с наших сайтов или посредством наших приложений, мы автоматически получим сведения о вашем оборудовании. Если вы воспользуетесь инструментами сообщества, у нас будет возможность сохранять принимаемые и отправляемые вами материалы и сообщения; также мы оставляем за собой право осуществлять мониторинг ваших сообщений. Также мы можем сохранять сведения о ваших действиях и личных предпочтениях при посещении наших сайтов или использовании наших приложений (см. раздел «Файлы cookie» ниже).</p>

							<p>Перед тем как передавать нам персональные данные любых других лиц (к примеру, в рамках акций «пригласи друга»), заручитесь их согласием. Следите за тем, чтобы предоставляемые вами данные были точны и актуальны, и своевременно обновляйте их в случае изменений.</p>

							<p>
							<b>2. Что мы делаем с собранными данными?</b>
							</p>
							<p>Мы используем собранные данные в следующих целях:</p>
							<ul>
							<li>для обеспечения функционирования наших сайтов и приложений, а также предоставления вам информации и услуг, включая, без ограничения, загружаемые материалы, чаты, форумы, рекламные акции и конкурсы;</li>
							<li>для оказания вам услуг технической поддержки;</li>
							<li>для внутреннего анализа и исследований в целях улучшения наших сайтов, а также других продуктов и услуг;</li>
							<li>для отправки вам административных сообщений (к примеру, в случае, если вы забыли пароль);</li>
							<li>для обнаружения и предотвращения случаев мошенничества и злоупотребления нашими сайтами, приложениями или услугами.</li>
							</ul>
							<p>
							<b>3. Файлы cookie</b>
							</p>
							<p>Некоторые из наших сайтов и приложений используют файлы cookie. Это небольшие текстовые файлы, которые сайт отправляет на ваш компьютер с целью записи ваших действий в сети. Такие файлы могут использоваться нашими сайтами и приложениями для сохранения ваших предпочтений при пользовании нашими сайтами и приложениями, для улучшения некоторых аспектов работы наших сайтов и приложений, для записи ваших действий в сети, а также для предоставления вам персонализированной рекламы и других материалов. Вы можете отключить использование файлов cookie в меню параметров браузера, однако в этом случае некоторые функции наших сайтов или приложений могут быть недоступны.</p>

							<p>
							<b>4. Безопасность</b>
							</p>
							<p>Мы принимаем коммерчески оправданные меры по защите ваших персональных данных. В число этих мер входят процессы и процедуры, направленные на снижение рисков несанкционированного доступа к вашим данным или разглашения таковых. Однако мы не гарантируем полного исключения злоупотреблений вашими персональными данными со стороны нарушителей. Храните пароли для ваших учетных записей в безопасном месте и не разглашайте их третьим лицам. Если вам станет известно о несанкционированном использовании вашего пароля или ином нарушении безопасности, немедленно свяжитесь с нами.</p>

							<p>Форумы, чаты и другие области сообщества игроков, доступные с помощью наших сайтов и приложений, являются публичными. В таких областях не следует разглашать информацию, позволяющую установить вашу личность или личность любого другого лица. Мы не несем ответственности за безопасность и защиту данных, разглашенных вами в таких областях.</p>
							<p>
							<b>5. Как отказаться от подписки</b>
							</p>
							<p>Если вы больше не хотите получать от нас маркетинговые сообщения, перейдите по ссылке, которая приводится в любом из таких сообщений, либо посетите регистрационный раздел нашего сайта и измените параметры получения сообщений. Если вы не регистрировались ни на одном из наших сайтов, отправьте сообщение с темой «Unsubscribe» (без кавычек) по адресу my.cmps.pro@gmail.com.</p>

							<p><b>6. Контактная информация</b></p>
							<p>
							По общим вопросам обращайтесь в службу поддержки пользователей, также на почтовый адрес my.cmps.pro@gmail.com
						</p>

							<p>Последнее обновление: март 2023 г.</p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </body>
</html>
