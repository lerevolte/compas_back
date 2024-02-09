@extends('layouts.main')

@section('h1')
	<h1 class="my-0 h1">{{ $module->get('display_name') }}</h1>
@endsection
@section('scripts')
<link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/owl.theme.default.min.css') }}">
<script src="{{ asset('js/owl.carousel.min.js') }}"></script>

@endsection
@section('content')
	<div class="box p-4">
		@if($module->isEnabled())
		<a href="{{ route('modules.uninstall', $module->get('alias')) }}" class="btn btn-gray">Удалить</a>
		@else
		<a href="{{ route('modules.install', $module->get('alias')) }}" class="btn btn-primary">Установить</a>
		@endif
    	<a href="" class="subscribe-text">Подписка</a>
	</div>
	<ul class="mt-4 top-nav navbar-nav flex-row">
        <li class="nav-item"><a data-toggle="tab" href="#main" class="nav-link active">Описание</a></li>
        <li class="nav-item"><a data-toggle="tab" href="#version" class="nav-link ">Версии</a></li>
        <li class="nav-item"><a data-toggle="tab" href="#support" class="nav-link ">Поддержка</a></li>
        <li class="nav-item"><a data-toggle="tab" href="#install" class="nav-link ">Установка</a></li>
    </ul>
    <div id="main" class="tab-content">
    	<div class="box p-3 active">
	    	<div class="panel panel-bold d-flex align-items-center p-0 pb-3">
			    Описание
			</div>
			<div class="module-content pt-3">
				<div style="flex: 1 1">
					{!! $module->get('full_description') !!}
				</div>
				<div class="module-content__img">
					<img class="w-100" src="https://via.placeholder.com/200"/>
				</div>
			</div>
		</div>
		<div class="box p-3 active mt-4">
	    	<div class="panel panel-bold d-flex align-items-center p-0 pb-3">
			    Фото модуля
			</div>
			<div class="module-content pt-3">
				<div class="owl-carousel">
					<div>
						<a href="https://via.placeholder.com/1000" data-fancybox>
				  			<img src="https://via.placeholder.com/463x256">
				  		</a>
					</div>
					<div>
				  		<a href="https://via.placeholder.com/1000" data-fancybox>
				  			<img src="https://via.placeholder.com/463x256">
				  		</a>
					</div>
					<div>
				  		<a href="https://via.placeholder.com/1000" data-fancybox>
				  			<img src="https://via.placeholder.com/463x256">
				  		</a>
					</div>
				</div>
			</div>
		</div>
    </div>
    <div id="version" class="tab-content" style="display: none;">
    	<div class="box p-3 active">
	    	<div class="panel panel-bold d-flex align-items-center p-0 pb-3">
			    Версия
			</div>
			<div class="module-content pt-3">
				<div>
					{!! $module->get('version') !!}
				</div>
			</div>
		</div>
    </div>
    <div id="support" class="tab-content" style="display: none;">
    	<div class="box p-3 active">
	    	<div class="panel panel-bold d-flex align-items-center p-0 pb-3">
			    Поддержка
			</div>
			<div class="module-content pt-3">
				<div>
					{!! $module->get('support') !!}
				</div>
			</div>
		</div>
    </div>
    <div id="install" class="tab-content" style="display: none;">
    	<div class="box p-3 active">
	    	<div class="panel panel-bold d-flex align-items-center p-0 pb-3">
			    Установка
			</div>
			<div class="module-content pt-3">
				<div>
					{!! $module->get('install') !!}
				</div>
			</div>
		</div>
    </div>
    <script type="text/javascript">
    	$(document).ready(function(){
    		$(".owl-carousel").owlCarousel({
    			margin: 15
    		});
    	});
		
	</script>
@endsection