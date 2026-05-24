@if(!isset(request()->ajax))
@php
cache()->flush();

$s = get_settings();
$side_items = isset($s['settings']['sidebar_items']) ? $s['settings']['sidebar_items'] : \App\Models\SidebarItem::orderBy('sort')->get();

$browse_admin = \Auth::user()->isAdmin();
$route = \Route::current();
if($route)
    $route_name = $route->getName();

$nodes = \App\Models\SidebarItem::orderBy('sort')->get();


/*
$traverse = function ($categories, $prefix = '-') use (&$traverse) {
    foreach ($categories as $category) {
        echo PHP_EOL.$prefix.' '.$category->name;

        $traverse($category->children, $prefix.'-');
    }
};

$traverse($nodes);*/
@endphp
<aside class="sidebar">
  <div class="sidebar__header">
    <a href="/">
      <img class="sidebar__logo" src="{{ asset('img/logo.svg') }}" alt="Logo">
    </a>
  </div>
  <nav class="sidebar-nav">
    <ul class="js-sort-side " >
      @foreach($nodes as $item)
        @if($item->parent_id)
        @continue
        @endif
          @if(isset($s['pages']['perms']['read_'.$item->code]) && $s['pages']['perms']['read_'.$item->code] != 'disabled' || $item->code == 'settings' && (isset($s['pages']['perms']['read_'.$item->code]) && $s['pages']['perms']['read_users'] != 'disabled' || isset($s['pages']['perms']['read_'.$item->code]) && $s['pages']['perms']['read_roles'] != 'disabled') || $browse_admin)
            @if($item->hasChildren() && count($item->children))
            <li class="sidebar-nav__item parent" data-id="{{ $item->id }}" data-dropdown data-noclose>
              @if($browse_admin)
              <!-- <button class="sidebar-nav__drag btn-clear js-edit-position-menu " type="button" draggable="true">
                  <svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg>
              </button> -->
              <span class="btn sidebar-nav__drag btn-clear edit-position-menu  js-edit-position-menu me-2 btn-drag btn-drag-field btn-xs text-muted ui-sortable-handle">
                  <svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg>
              </span>
              @endif
              <button class="sidebar-nav__link sidebar-nav__link_sub" type="button" data-dropdown="btn">
                <a href="javascript:;" class="sidebar-nav__link js-first-sublink">{{ $item->name }}</a>
                <svg class="sidebar-nav__dropdown" width="12" height="7" viewBox="0 0 12 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M11.7691 1.20358L6.15244 6.91013C6.15244 6.91013 6.0401 7 5.86037 7C5.68063 7 5.5683 6.91013 5.5683 6.91013L0.0864106 1.20358C0.0864106 1.20358 -0.138257 0.709308 0.221211 0.304906C0.580679 -0.0994959 1.11988 0.0802383 1.11988 0.0802383L5.8379 4.93306L10.6009 0.0802383C10.6009 0.0802383 11.0951 -0.144429 11.5445 0.259973C11.9938 0.664374 11.7691 1.20358 11.7691 1.20358Z" />
                </svg>
              </button>
              <ul class="sidebar-nav__submenu" data-dropdown="menu">
                @foreach($item->children as $child)
                <li class="sidebar-nav__item">
                  <a class="sidebar-nav__link" href="{{ $child->link }}">{{ $child->name }}</a>
                </li>
                @endforeach
              </ul>
            </li>
            @else
            <li class="sidebar-nav__item {{ ($item->code == 'orders' || $item->code == 'addr' || $item->code == 'infostore' || $item->code == 'analytics') ? 'navbar-item-managers':'' }}" data-id="{{ $item->id }}" >
                @if($browse_admin)
                
                <span class="btn sidebar-nav__drag btn-clear edit-position-menu js-edit-position-menu me-2 btn-drag btn-drag-field btn-xs text-muted ui-sortable-handle">
                    <svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg>
                </span>
                @endif
                @if(Request::path() == 'infostore/orders' && $item->code == 'orders')
                <a class="sidebar-nav__link" href="{{ $item->link }}">
                @elseif(Request::path() == 'infostore/orders' && $item->code == 'infostore')
                <a class="sidebar-nav__link active " href="{{ $item->link }}">
                @else
                <a class="sidebar-nav__link" @if($item->code == 'analytics') href="#" @else href="{{ $item->link }}" @endif>
                @endif
                    {{ $item->name }}
                    
                </a>

            </li>
            @endif
          @endif
      @endforeach
    </ul><!-- /.sidebar-nav__list -->
  </nav><!-- /.sidebar-nav -->
  <div class="sidebar-profile">
    <div class="dropdown" data-dropdown>
      <a class="sidebar-profile__inner" href="#" data-dropdown="btn">
        <div class="sidebar-profile__left">
          
            @php
            $name = $last_name = '';
            if(Auth::user()->name)
                $name = mb_substr(Auth::user()->name,0,1);
            if(Auth::user()->last_name)
                $last_name = mb_substr(Auth::user()->last_name,0,1);
            @endphp
            @if(Auth::user()->avatar)
                @php
                $value = json_decode(Auth::user()->avatar,true);
                if(is_array($value))
                    $value = \App\Models\File::whereIn('id', $value)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$value).")"))->first();
                @endphp
                @if(isset($value->path))
                <div class="sidebar-profile__img" style="background-image:url({{ \Thumbnail::src('https://compas.pro'.Storage::disk()->url($value->path))->heighten(200)->url() }})">
                </div>
                @else
                <div class="sidebar-profile__img" style="background: {{ Auth::user()->getColor() }}">
                  <span class="sidebar-profile__shortname" >{{ ucfirst($name).ucfirst($last_name) }}</span>
                </div>
                @endif
            @else
            <div class="sidebar-profile__img"  style="background: {{ Auth::user()->getColor() }}">
              <span class="sidebar-profile__shortname">{{ ucfirst($name).ucfirst($last_name) }}</span>
            </div>
            @endif
          
        </div>
        <div class="sidebar-profile__name">{{ Auth::user()->name.' '.Auth::user()->last_name }}</div>

      </a>
      <div class="dropdown__menu dropdown__menu_top dropdown__menu_sm-lh" data-dropdown="menu">
        <ul class="dropdown__list">
          <li class="dropdown__item">
            <a class="dropdown__link" href="/profile">Личные настройки</a>
          </li>
          <li class="dropdown__item">
            <a class="dropdown__link dropdown__link_red" href="/logout">Выйти</a>
          </li>
        </ul>
      </div>

    </div>

  </div>
</aside><!-- /.sidebar -->
@endif