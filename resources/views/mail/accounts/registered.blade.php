
{{-- Greeting --}}
<h3>Спасибо за регистрацию!</h3>

{{-- Intro Lines --}}
<div>Вы зарегистрировались на сайте compas.pro. Ниже представлены ваши данные для входа, пожалуйста сохраните их!</div>
<ul>
    <li><b>Портал: </b>{{ $account }}</li>
    <li><b>Email: </b>{{ $email }}</li>
    <li><b>Пароль: </b>{{ $password }}</li>
</ul>


@lang('Regards'),<br>
{{ config('app.name') }}

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
@lang(
    "If you're having trouble clicking the button, copy and paste the URL below\ninto your web browser:"
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset