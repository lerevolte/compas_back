
<!-- Vendor JS -->
<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('js/jquery-ui.min.js') }}"></script>
<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

<script src="{{ asset('libs/fancybox/jquery.fancybox.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<script type="text/javascript" src="{{ asset('js/daterangepicker.js') }}?v={{ random_int(1, 20000) }}"></script>

<script data-require="datatables@*" data-semver="1.10.12" src="{{ asset('datatables/jquery.dataTables.js') }}?v={{ random_int(1, 20000) }}"></script>
<script src="{{ asset('datatables/RowReorder-1.2.7/js/dataTables.rowReorder.js') }}"></script>
<script src="{{ asset('js/jquery-resizable.min.js') }}"></script>
<script src="{{ asset('js/js.cookie.js') }}"></script>
<script src="{{ asset('js/jquery.inputmask.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/resizer.cols.js') }}?v={{ random_int(1, 20000) }}"></script>
@php
$s = isset($model_settings) ? $model_settings : get_settings();
@endphp
@if(isset($s['account']['yandex_api_key']))
<script src="https://api-maps.yandex.ru/2.1/?apikey={{ $s['account']['yandex_api_key'] }}&lang=ru_RU" type="text/javascript">
</script>
<script src="{{ asset('js/paintOnMap.js') }}"></script>
@else
<script src="https://api-maps.yandex.ru/2.1/?apikey=ef7607ff-665a-4e98-a65b-c73d97c69005&lang=ru_RU" type="text/javascript">
</script>
<script src="{{ asset('js/paintOnMap.js') }}"></script>
@endif
<script src="{{ asset('js/coloris.min.js?v=') }}<?=random_int(1, 20000)?>"></script>
<script src="{{ asset('js/main.js?v=') }}<?=random_int(1, 20000)?>"></script>
<script src="{{ asset('js/common.js?v=16') }}"></script>

<!-- Fonts CSS -->
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet">

<link data-require="datatables@*" data-semver="1.10.12" rel="stylesheet" href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="//cdn.datatables.net/rowreorder/1.2.0/css/rowReorder.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.5.4/js/dataTables.colReorder.min.js" />
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script> 
<script src="{{ asset('js/buttons.html5.js') }}"></script>
<!-- Bootstrap CSS -->
<link rel="stylesheet" type="text/css" href="{{ asset('libs/bootstrap/css/bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<!-- Vendor CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" type="text/css" href="{{ asset('libs/fancybox/jquery.fancybox.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('libs/select2/css/select2.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('libs/datepicker/datepicker.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('css/coloris.min.css?v=') }}<?=random_int(1, 20000)?>"/>
<!-- Main CSS -->
<link rel="stylesheet" type="text/css" href="{{ asset('css/style.css?v=') }}<?=random_int(1, 20000)?>"/>
