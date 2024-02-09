@extends('layouts.main')
@section('h1')
	<h1 class="my-0 h1">Магазин модулей</h1>
@endsection
@section('content')
	<ul class="top-nav navbar-nav flex-row">
        <li class="nav-item"><a href="{{ route('modules.list') }}" class="nav-link @if(request()->route()->getName() == 'modules.list') active @endif">Все приложения</a></li>
        <li class="nav-item"><a href="{{ route('modules.installed') }}" class="nav-link @if(request()->route()->getName() == 'modules.installed') active @endif">Установленные</a></li>
    </ul>
    <div class="box">
	    <div class="panel panel-bold d-flex align-items-center">
		    Информация
		</div>
	    <div class="t-body">
	    	<div class="row g-0">
	    		<div class="col-lg-3 border-end">
	    			<div class="c-body p-0">
	    				<ul class="c-drag-list list-unstyled mb-0">
	    					<li class="active">
	    						<a href="" class="btn btn-light w-100 active">Логистика</a>
	    					</li>
	    					<li>
	    						<a href="" class="btn btn-light nav-link w-100">CRM</a>
	    					</li>
	    				</ul>
	    			</div>
	    		</div>
	    		<div class="col-lg-9">
	    			<div class="c-body p-3">
		    			<div class="row">
							@if(isset($modules))
								@foreach($modules as $module)
			    				<div class="col-md-6 col-lg-6 col-xl-4">
			    					<div class="card card-module flex-row">
			    						<div class="card-img-wrap">
			    							<img class="card-img-left example-card-img-responsive" src="https://via.placeholder.com/150"/>
			    						</div>
									  	<div class="card-body">
										    <h4 class="card-title">{{ $module->get('display_name') }}</h4>
										    <p class="card-text">
										    	{{ $module->getDescription() }}
										    </p>
										    <div class="card-buttons">
										    	@if($module->isEnabled())
										    	<a href="{{ route('modules.uninstall', $module->get('alias')) }}" class="btn btn-gray">Удалить</a>
										    	@else
										    	<a href="{{ route('modules.show', $module->get('alias')) }}" class="btn btn-primary">Посмотреть</a>
										    	@endif
										    	<a href="" class="subscribe-text">Подписка</a>
										    </div>
									  	</div>
									</div>
			    				</div>
			    				@endforeach
		    				@endif
		    				
		    			</div>
		    		</div>
	    		</div>
	    	</div>
	    </div>
	</div>
@endsection