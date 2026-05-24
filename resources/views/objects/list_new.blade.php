@extends('layouts.base')
@section('title')
{{ $entity->display_name_plural }}
@endsection
@section('h1')
{{ $entity->display_name_plural }}
@endsection
@section('top_menu')
  @if($parent_menu)
    <div class="header__top">
      <div class="header__left">
        <button class="header__burger btn-clear" id="burgerBtn" type="button" aria-controls="mobileMenu" aria-label="Open menu">
          <svg width="15" height="12" fill="none">
            <path fill="#A6B7D4" fill-rule="evenodd" d="M0 1a1 1 0 0 1 1-1h9a1 1 0 1 1 0 2H1a1 1 0 0 1-1-1Zm0 5a1 1 0 0 1 1-1h9a1 1 0 1 1 0 2H1a1 1 0 0 1-1-1Zm1 4a1 1 0 1 0 0 2h9a1 1 0 1 0 0-2H1Z" clip-rule="evenodd" />
          </svg>
        </button>
        <div class="header-breadcrumbs">
          @foreach($parent_menu->children as $child)
          <a class="header-breadcrumbs__link @if($active_menu->id == $child->id) header-breadcrumbs__link_active @endif" href="{{ $child->link }}">{{ $child->name }}</a>
          @endforeach
        </div>
      </div>
      <div class="dropdown" data-dropdown>
        <button class="header__options table__settings btn-clear" type="button" data-dropdown="btn">
          <svg width="15" height="15" fill="none">
            <path fill-rule="evenodd" d="M14.25 5.979a.75.75 0 0 1 .75.75v1.542a.75.75 0 0 1-.75.75h-.952a5.954 5.954 0 0 1-.622 1.504l.672.673a.75.75 0 0 1 0 1.06l-1.09 1.09a.75.75 0 0 1-1.06 0l-.674-.672a5.957 5.957 0 0 1-1.503.622v.952a.75.75 0 0 1-.75.75H6.729a.75.75 0 0 1-.75-.75v-.952a5.953 5.953 0 0 1-1.504-.622l-.672.673a.75.75 0 0 1-1.061 0l-1.09-1.09a.75.75 0 0 1 0-1.061l.672-.673a5.953 5.953 0 0 1-.622-1.504H.75a.75.75 0 0 1-.75-.75V6.73a.75.75 0 0 1 .75-.75h.952c.14-.534.35-1.038.622-1.503l-.673-.673a.75.75 0 0 1 0-1.06l1.09-1.091a.75.75 0 0 1 1.061 0l.673.672a5.98 5.98 0 0 1 1.504-.622V.75a.75.75 0 0 1 .75-.75H8.27a.75.75 0 0 1 .75.75v.952c.534.14 1.038.35 1.503.622l.673-.672a.75.75 0 0 1 1.061 0l1.09 1.09a.75.75 0 0 1 0 1.06l-.672.673c.272.465.482.97.622 1.504h.952ZM7.5 10.252a2.752 2.752 0 1 0 0-5.504 2.752 2.752 0 0 0 0 5.504Z" clip-rule="evenodd" />
          </svg>
        </button>
        <div class="dropdown__menu dropdown__menu_right" data-dropdown="menu">
          <ul class="dropdown__list">
            <li class="dropdown__item">
              <button class="dropdown__link" type="button" data-dropdown="subBtn">
                Отображение столбцов
                <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
              </button>
              <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="headerDisplayMenu"></ul>
            </li>
            <li class="dropdown__item">
              <button class="dropdown__link" type="button" data-dropdown="subBtn">
                Порядок столбцов
                <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
              </button>
              <ul class="dropdown__submenu table__menu " data-dropdown="submenu" id="headerOrderMenu" data-dragName="headerOrderMenu" data-drag="area"></ul>
            </li>
          </ul>
        </div>
      </div>
    </div>
    
    
  @endif
@endsection
@section('scripts')
<script type="text/javascript" src="{{ asset('js/main.js') }}?v={{ random_int(1,20000) }}"></script>
@endsection
@section('search')
    <div class="header__search">
        <div class="filter__blocks"></div>
        <span class="filter__add" id="filterAdd" type="button">+</span>
        <input class="form-control form-control_search" type="text" placeholder="Фильтр + поиск" id="searchField">
        <div class="filter">
          <div class="filter__inner">
            <div class="filter__form">
              <div class="filter__list js-sort-form-filter" data-dragName="filterMenu"></div>
              <div class="filter__select">
                <div class="dropdown" data-dropdown>
                  <button class="filter__btn btn-clear" data-dropdown="btn" type="button">Выбрать поле</button>
                  <div class="dropdown__menu" data-dropdown="menu">
                    <ul class="dropdown__list" id="filterMenu">
                        
                    </ul>
                  </div>
                </div>
              </div>
              <div class="filter__actions">
                <div class="filter__actions-item js-search-panel">
                  <button class="btn btn_blue" type="button">Найти</button>
                  <button class="btn btn_grey" type="reset" id="filterReset">Сбросить</button>
                </div>
                <div class="filter__actions-item filter__actions-item_edit js-filter-save-panel">
                  <button id="filterSave" class="btn btn_blue js-save-filter" type="button" data-type="">Сохранить</button>
                  <button class="btn btn_grey js-reset-filter" type="reset">Отменить</button>
                </div>
              </div>
            </div>
            <div class="filter-saved">
                <ul class="filter-saved__list">
                    <li class="filter-saved__item first">Сохраненные</li>
                    @foreach($filters as $filter)
                    <li class="filter-saved__item">
                        <input class="filter-saved__input" type="text" value="{{ $filter->name }}" id="savedFilter-0" title="" disabled="">
                        <div class="filter-saved__menu">
                            <div class="dropdown" data-dropdown="">
                              <button class="filter__options btn-clear" data-dropdown="btn">
                                <svg width="3" height="13" fill="none">
                                  <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd"></path>
                                </svg>
                              </button>
                              <div class="dropdown__menu dropdown__menu_right" data-dropdown="menu">
                                <ul class="dropdown__list">
                                  <li class="dropdown__item" style="display: none;">
                                    <button class="dropdown__link js-filter-sort" type="button" data-filter="next" data-id="{{ $filter->id }}">Вверх</button>
                                  </li>
                                  <li class="dropdown__item" style="display: none;">
                                    <button class="dropdown__link js-filter-sort" type="button" data-filter="prev" data-id="{{ $filter->id }}">Вниз</button>
                                  </li>
                                  <li class="dropdown__item">
                                    <button class="dropdown__link js-filter-edit" type="button" data-filter="edit" data-id="{{ $filter->id }}">Редактировать</button>
                                  </li>
                                  <li class="dropdown__item">
                                    <button class="dropdown__link js-delete-filter" type="button" data-filter="delete" data-id="{{ $filter->id }}">Удалить</button>
                                  </li>
                                </ul>
                              </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
                <a href="javascript:;" class="filter__create btn-clear js-add-filter" data-type="">+ Создать фильтр</a>
            </div>
          </div><!-- /.filter__inner -->
        </div>
    </div>
@endsection
@section('content')
  @php
  $is_admin = $settings['is_admin'] ?? Auth::user()->isAdmin();
  
  @endphp
    {{ csrf_field() }}
  <input type="hidden" name="sort_index" value="">
  <input type="hidden" name="sort_asc" value="">
  <div class="table">
    <div class="table-top">
      <button class="table__change-btn btn-clear" type="button">Сохранить изменения</button>
      <div class="dropdown" data-dropdown>
        <button class="table__settings btn-clear" type="button" data-dropdown="btn">
          <svg width="15" height="15" fill="none">
            <path fill-rule="evenodd" d="M14.25 5.979a.75.75 0 0 1 .75.75v1.542a.75.75 0 0 1-.75.75h-.952a5.954 5.954 0 0 1-.622 1.504l.672.673a.75.75 0 0 1 0 1.06l-1.09 1.09a.75.75 0 0 1-1.06 0l-.674-.672a5.957 5.957 0 0 1-1.503.622v.952a.75.75 0 0 1-.75.75H6.729a.75.75 0 0 1-.75-.75v-.952a5.953 5.953 0 0 1-1.504-.622l-.672.673a.75.75 0 0 1-1.061 0l-1.09-1.09a.75.75 0 0 1 0-1.061l.672-.673a5.953 5.953 0 0 1-.622-1.504H.75a.75.75 0 0 1-.75-.75V6.73a.75.75 0 0 1 .75-.75h.952c.14-.534.35-1.038.622-1.503l-.673-.673a.75.75 0 0 1 0-1.06l1.09-1.091a.75.75 0 0 1 1.061 0l.673.672a5.98 5.98 0 0 1 1.504-.622V.75a.75.75 0 0 1 .75-.75H8.27a.75.75 0 0 1 .75.75v.952c.534.14 1.038.35 1.503.622l.673-.672a.75.75 0 0 1 1.061 0l1.09 1.09a.75.75 0 0 1 0 1.06l-.672.673c.272.465.482.97.622 1.504h.952ZM7.5 10.252a2.752 2.752 0 1 0 0-5.504 2.752 2.752 0 0 0 0 5.504Z" clip-rule="evenodd" />
          </svg>
        </button>
        <div class="dropdown__menu dropdown__menu_right" data-dropdown="menu">
          <ul class="dropdown__list">
            <li class="dropdown__item">
              <button class="dropdown__link" type="button" data-dropdown="subBtn">
                Отображение столбцов
                <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
              </button>
              <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="displayMenu"></ul>
            </li>
            <li class="dropdown__item">
              <button class="dropdown__link" type="button" data-dropdown="subBtn">
                Фиксирование столбцов
                <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
              </button>
              <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="fixMenu"></ul>
            </li>
            <li class="dropdown__item">
              <button class="dropdown__link" type="button" data-dropdown="subBtn">
                Порядок столбцов
                <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
              </button>
              <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="orderMenu" data-dragName="orderMenu" data-drag="area"></ul>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="table__wrapper">
      <table class="table__inner js-entity-table" data-model="{{ $model }}">
        <thead class="table-header">
          <tr class="table-header__row">
            
            
            @php
            $start_columns = array(
            );
            $start_columns['select'] = array(
                'name' => 'select',
                'display_name' => 'Выделение строки',
                'fix' => 0,
                'hidden' => 0,
                'width' => '',
                'write_perm' => 1

            );
            $start_columns['actions'] = array(
                'name' => 'actions',
                'display_name' => 'Действие',
                'fix' => 0,
                'hidden' => 0,
                'width' => '',
                'write_perm' => 1
            );
            foreach($model_fields as $field) {
                $start_columns[$field->field] = array(
                    'name' => $field->field,
                    'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                    'fix' => 0,
                    'hidden' => 0,
                    'width' => '',
                    'write_perm' => !$field->only_read && (isset($perms['write'][$field->field]) && $perms['write'][$field->field] != 'disabled' || $is_admin)
                );
            }
            @endphp
            @foreach($start_columns as $col)
              @if($col['name'] == 'select')
                <th class="table-header__item @if($col['fix']) sticky-start @endif " data-name="select" draggable="true"  @if($col['width'])
                style="width: {{ $col['width'] }};"
                @else
                style="width: 40px;"
                @endif>
                  <div class="table-header__inner">
                    <div class="form-checkbox">
                      <label class="form-checkbox__label" for="mainCheckbox">
                        <input class="form-checkbox__input" type="checkbox" id="mainCheckbox">
                        <span class="form-checkbox__switcher"></span>
                      </label>
                    </div>
                    <button class="table-header__filter-btn btn-clear" type="button">
                      <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                    </button>
                  </div>
                  <span class="table-header__label">Выделение</span>
                </th>
              @else
                <th class="table-header__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if(!$col['write_perm']) text-gray @endif" data-name="{{ $col['name'] }}" draggable="true" data-width="{{ $col['width'] }}"
                @if($col['width'])
                style="width: {{ $col['width'] }};"
                @else
                style="width: 130px;"
                @endif>
                  <div class="table-header__inner">
                    {!! $col['display_name'] !!}
                    <button class="table-header__filter-btn btn-clear" type="button">
                      <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                    </button>

                  </div>
                </th>
              @endif
            @endforeach
          </tr>
        </thead>
        <tbody class="table-body">
            @foreach($objects as $object)
            <tr id="item-{{ $object['id'] }}" class="table-body__row" data-id="{{ $object['id'] }}">
                
                
                @php
                $start_columns = array(
                );
                foreach($model_fields as $field) {
                    $start_columns['select'] = array(
                        'name' => 'select',
                        'fix' => 0,
                        'hidden' => 0,
                        'width' => '',

                    );
                    $start_columns['actions'] = array(
                        'name' => 'actions',
                        'display_name' => 'Действие',
                        'fix' => 0,
                        'hidden' => 0,
                        'width' => '',
                    );
                    $start_columns[$field->field] = array(
                        'name' => $field->field,
                        'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                        'fix' => 0,
                        'type' => $field->type,
                        'only_read' => $field->only_read,
                        'hidden' => 0,
                        'is_files' => ($field->type == 'file' || $field->type == 'image'),
                        'is_date' => ($field->type == 'date'),
                    );
                }
                
                @endphp
                @foreach($start_columns as $col)
                @if($col['name'] == 'select')
                <td class="table-body__item @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                  <div class="form-checkbox">
                    <label class="form-checkbox__label">
                      <input class="form-checkbox__input" type="checkbox" data-checkbox>
                      <span class="form-checkbox__switcher"></span>
                    </label>
                  </div>
                </td>
                @elseif($col['name'] == 'actions')
                <td class="table-body__item table-options @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                  <div class="table-options__inner">
                    <div class="dropdown" data-dropdown>
                      <button class="table-options__btn btn-clear" data-dropdown="btn">
                        <svg width="3" height="13" fill="none">
                          <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd" />
                        </svg>
                      </button>
                      <div class="dropdown__menu dropdown__menu_align dropdown__menu_sm-lh" data-dropdown="menu">
                        <ul class="dropdown__list">
                          <li class="dropdown__item">
                            <button class="dropdown__link js-edit-model" data-id="{{ $object['id'] }}" data-model="{{ $model }}" type="button">Редактировать</button>
                            <button class="dropdown__link dropdown__link_red js-delete-model" data-id="{{ $object['id'] }}" data-model="{{ $model }}" type="button" data-delete>Удалить</button>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </td>
                @else
                <td class="table-body__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if($col['is_date']) date-field @endif field-{{ $col['type'] }}" data-field="{{ $col['name'] }}">
                    @if($col['is_files'])
                    {!! $object[$col['name']] !!}
                    @else
                    <div class="table-body__inner @if(!$col['only_read']) table-edit__field @endif" data-f="{{ $col['name'] }}">
                      {!! $object[$col['name']] !!}
                    </div>
                    @endif
                </td>
                @endif
                @endforeach
            </tr>
            @endforeach
        </tbody>
      </table><!-- /.table__inner -->
    </div><!-- /.table__wrapper -->
    <div class="table-footer">
      <div class="table-footer__title">
        Отмечено: <span class="table-footer__counter" id="checkboxCounter">0</span>
      </div>
      <div class="pagination">
        <span class="pagination__title table-footer__title">Страница:</span>
        <ul class="pagination__list"></ul>
      </div>
      <div class="table-footer__show">
        <span class="table-footer__title sm-hidden">На странице:</span>
        <div class="dropdown" data-dropdown>
          <button class="select btn-clear" data-dropdown="btn" id="paginationSelect">
            <span class="select-current">25</span>
            <svg width="12" height="7" fill="none">
              <path fill-rule="evenodd" d="M11.77 1.2 6.15 6.91S6.04 7 5.86 7s-.3-.09-.3-.09L.1 1.21S-.14.7.22.3s.9-.23.9-.23l4.72 4.85L10.6.08s.5-.22.94.18c.45.4.23.94.23.94Z" clip-rule="evenodd" />
            </svg>
          </button>
          <div class="dropdown__menu dropdown__menu_select" data-dropdown="menu">
            <ul class="dropdown__list">
              <li class="dropdown__item">
                <input class="dropdown__link select__input" type="button" value="25">
              </li>
              <li class="dropdown__item">
                <input class="dropdown__link select__input" type="button" value="50">
              </li>
              <li class="dropdown__item">
                <input class="dropdown__link select__input" type="button" value="100">
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div><!-- /.table-footer -->
  </div><!-- /.table -->
  <script type="text/javascript">
    $(document).ready(function(){
      $('body').on('click', '.js-delete-model', function(e){
        
        
    });
    $('.js-add-object').click(function(e) {
        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            type: 'post',
            url: '/objects/{{ $model }}/store',
            async: false,
            data: {
                '_token': $('input[name=_token]').val(),
            },
            success: function(res) {
                console.log('res')
                console.log(res)
                ddajaxsidepanel.showhidepanel('/objects/{{ $model }}/show/'+res.id+'?ajax=y&create=Y', 'show', 'iframe');
                // var old_href = $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').attr('href');
                // $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').attr('href', '/orders/edit/'+res.id+'?ajax=y&create=Y');
                // $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').trigger('click');
                // $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').trigger('click');
                // $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').attr('href', old_href);

                // table.ajax.reload(function(){
                //    select2init();
                // });
                
            }
        });

    });
    })
  </script>
@endsection