@extends('layouts.base')
@section('title')
Корзина
@endsection
@section('h1')
Корзина
@endsection
@section('top_menu')
  @if($menu_items)
    <div class="header__top">
      <div class="header__left">
        <button class="header__burger btn-clear" id="burgerBtn" type="button" aria-controls="mobileMenu" aria-label="Open menu">
          <svg width="15" height="12" fill="none">
            <path fill="#A6B7D4" fill-rule="evenodd" d="M0 1a1 1 0 0 1 1-1h9a1 1 0 1 1 0 2H1a1 1 0 0 1-1-1Zm0 5a1 1 0 0 1 1-1h9a1 1 0 1 1 0 2H1a1 1 0 0 1-1-1Zm1 4a1 1 0 1 0 0 2h9a1 1 0 1 0 0-2H1Z" clip-rule="evenodd" />
          </svg>
        </button>
        <div class="header-breadcrumbs">
          @foreach($menu_items as $item)
          <a class="header-breadcrumbs__link" href="/trash/{{ $item->slug }}">{{ $item->display_name_singular }}</a>
          @endforeach
        </div>
      </div>
    </div>
  @endif
@endsection
@section('scripts')
<script type="text/javascript" src="{{ asset('js/main.js') }}?v={{ random_int(1,20000) }}"></script>
@endsection
@section('content')
<style type="text/css">
  .js-add-object {
    display: none;
  }
</style>
@endsection