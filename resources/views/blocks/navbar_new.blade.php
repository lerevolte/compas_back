@if(request()->user())
@php
cache()->flush();
$s = get_settings();
$side_items = isset($s['settings']['sidebar_items']) ? $s['settings']['sidebar_items'] : \App\Models\SidebarItem::orderBy('sort')->get();

$browse_admin = Auth::user()->isAdmin();
$route = \Route::current();
if($route)
    $route_name = $route->getName();

@endphp
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">              
        <a class="navbar-brand" href="/">
            <img src="{{ asset('images/logo.svg') }}" alt="авиксо" class="img-fluid" >
        </a>

        <button type="button" class="btn-close text-reset d-lg-none" data-dismiss="offcanvas" aria-label="Close"><i class="fa fa-times"></i></button>
    </div>
    <div class="offcanvas-body">
        <ul class="navbar-nav mx-n3 js-sort-side">
            @foreach($side_items as $item)
                @if(isset($s['pages']['perms']['read_'.$item->code]) && $s['pages']['perms']['read_'.$item->code] != 'disabled' || $item->code == 'settings' && (isset($s['pages']['perms']['read_'.$item->code]) && $s['pages']['perms']['read_users'] != 'disabled' || isset($s['pages']['perms']['read_'.$item->code]) && $s['pages']['perms']['read_roles'] != 'disabled') || $browse_admin)
                <li class="nav-item {{ ($item->code == 'orders' || $item->code == 'addr' || $item->code == 'infostore' || $item->code == 'analytics') ? 'navbar-item-managers':'' }}" data-id="{{ $item->id }}" >
                    @if($browse_admin)
                    <span class="btn edit-position-menu js-edit-position-menu me-2 btn-drag btn-drag-field btn-xs text-muted ui-sortable-handle">
                        <svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg>
                    </span>
                    @endif
                    @if(Request::path() == 'infostore/orders' && $item->code == 'orders')
                    <a class="nav-link" href="{{ $item->link }}">
                    @elseif(Request::path() == 'infostore/orders' && $item->code == 'infostore')
                    <a class="nav-link active " href="{{ $item->link }}">
                    @else
                    <a class="nav-link" @if($item->code == 'analytics') href="#" @else href="{{ $item->link }}" @endif>
                    @endif
                        {{ $item->name }}
                        
                    </a>

                </li>
                @endif
            @endforeach
        </ul>
    </div>

    <div class="offcanvas-footer">
        <div class="dropdown">
            <button class="btn btn-transparent px-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="d-flex align-items-center" width="30" height="30">
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
                        <div class="profile-avatar" style="background-image:url({{ \Thumbnail::src('https://compas.pro'.Storage::disk()->url($value->path))->heighten(200)->url() }})">
                        </div>
                        @else
                        
                        <div class="profile-avatar">
                        <span style="background: {{ Auth::user()->getColor() }}">{{ ucfirst($name).ucfirst($last_name) }}</span>
                        </div>
                        @endif
                    @else

                    <div class="profile-avatar">
                    <span style="background: {{ Auth::user()->getColor() }}">{{ ucfirst($name).ucfirst($last_name) }}</span>
                    </div>
                    @endif
                    
                </div>
                
                <div class="profile-name">
                    {{ Auth::user()->name.' '.Auth::user()->last_name }}
                </div>
            </button>
            <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions" href="javascript:;" aria-labelledby="dropdownMenuButton" style="margin-bottom: 7px;">
                <a class="dropdown-item"  href="/profile">Личные настройки</a>
                <a class="dropdown-item " href="/logout" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><span class="red">Выйти</span></a>
            </div>
        </div>
        
    </div>
</div>
<div id="changeDateModal" class="fancy-modal">  
        <form class="form js-change-date-form">
            <h5 class="section-title text-center mb-4">
                Перенос доставки
            </h5>
            
            <div class="mb-2">
                <div class="position-relative mb-3">
                    <label for="#" class="label mb-1">
                        Убрать с машины заложеную сумму на доставку?
                    </label>
                    <div class="switch">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="delete_delivery_price" id="changeDateModal1" value="1">
                          <label class="form-check-label" for="changeDateModal1">
                            Да
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="delete_delivery_price" id="changeDateModal2" checked value="0">
                          <label class="form-check-label" for="changeDateModal2">
                            Нет
                          </label>
                        </div>
                    </div>
                </div>

                <div class="position-relative mb-3">
                    <label for="#" class="label mb-1">
                        Комментарии водителя к статусу
                    </label>
                    <textarea name="date_change_comment" cols="3" class="form-control" placeholder="Укажите примечание"></textarea>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary rounded-1">Сохранить</button>
            </div>
        </form>
    </div>
    <div id="cancelModal" class="fancy-modal">  
        <form class="form js-cancel-form">
            <h5 class="section-title text-center mb-4">
                Отмена заказа
            </h5>
            
            <div class="mb-2">
                <div class="position-relative mb-3">
                    <label for="#" class="label mb-1">
                        Убрать с машины заложеную сумму на доставку?
                    </label>
                    <div class="switch">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="delete_delivery_price" id="cancelModal1" value="1">
                          <label class="form-check-label" for="cancelModal1">
                            Да
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="delete_delivery_price" id="cancelModal2" checked value="0">
                          <label class="form-check-label" for="cancelModal2">
                            Нет
                          </label>
                        </div>
                    </div>
                </div>

                <div class="position-relative mb-3">
                    <label for="#" class="label mb-1">
                        Комментарии водителя к статусу
                    </label>
                    <textarea name="cancel_comment" cols="3" class="form-control" placeholder="Укажите примечание"></textarea>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary rounded-1">Сохранить</button>
            </div>
        </form>
    </div>
    <div id="splitModal" class="fancy-modal">  
        <form class="form">
            <input type="hidden" name="id">
            <h5 class="section-title text-center mb-4">
                Укажите сколько товара оставить в первом заказе
            </h5>
            
            <table class="split-table">
                <tr>
                    <td>Таблетированная соль DieSalz, 25кг</td>
                    <td>10</td>
                </tr>

                <tr>
                    <td>Пескосоль 70/30</td>
                    <td>7</td>
                </tr>
            </table>

            <div class="text-center">
                <button type="submit" class="btn btn-primary js-split-submit">Разделить</button>
            </div>
        </form>
    </div>
@else
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">              
        <a class="navbar-brand" href="/">
            <img src="{{ asset('images/logo.svg') }}" alt="авиксо" class="img-fluid" >
        </a>

        <button type="button" class="btn-close text-reset d-lg-none" data-dismiss="offcanvas" aria-label="Close"><i class="fa fa-times"></i></button>
    </div>
    <div class="offcanvas-body">
        <ul class="navbar-nav mx-n3 js-sort-side">
        </ul>
    </div>

    <div class="offcanvas-footer">
        <div class="dropdown">
            <button class="btn btn-transparent px-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="d-flex align-items-center" width="30" height="30">
                    
                    
                </div>
                
                <div class="profile-name">
                     
                </div>
            </button>
            <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions" href="javascript:;" aria-labelledby="dropdownMenuButton" style="margin-bottom: 7px;">
                <a class="dropdown-item"  href="/profile">Личные настройки</a>
                <a class="dropdown-item " href="/logout" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><span class="red">Выйти</span></a>
            </div>
        </div>
        
    </div>
</div>
@endif