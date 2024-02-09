<div class="position-relative">
    <div class="row g-2">
        @foreach($names as $key => $name)
        <div class="col-12">
            <div class="label text-secondary">
                {{ $labels[$name] }}
            </div>
            <div class="position-relative">
                <input name="{{ $name }}" type="text" class="form-control" value="{{ $value[$key] }}">
            </div>
        </div>
        @endforeach
        

        <!-- <div class="settings col-auto pt-label">
            <a class="dropdown-toggle btn p-0 text-secondary " href="#" id="s2" role="button" data-toggle="dropdown" aria-expanded="false">
                <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
            </a>

            <ul class="dropdown-menu" >
                <li><a class="dropdown-item js-field-update" data-field="{{ $name }}" href="#">Настроить</a></li>
                <li>
                    <div class="dropdown-item">
                        <div class="form-check form-check-xs mb-0">
                          <input class="form-check-input js-field-show" type="checkbox" value="1" id="flexCheckDefault{{ $name }}" data-field="{{ $name }}">
                          <label class="form-check-label " for="flexCheckDefault{{ $name }}">
                            Показывать всегда
                          </label>
                        </div>
                    </div>
                </li>
                <li><a class="dropdown-item js-field-hide" data-field="{{ $name }}" href="#">Скрыть</a></li>
            </ul>
        </div>  -->
    </div>
    
</div>