
{{-- Greeting --}}
<h3>Найдены новые штрафы!</h3>

{{-- Intro Lines --}}
<div>На вашем портале {{ $account }} найдены новые штрафы.</div>
<ul>
    @foreach($fines as $fine)
    <li><b>УИН: </b>{{ $fine['number_doc'] }}</li>
    @endforeach
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