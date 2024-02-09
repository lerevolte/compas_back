<div class="position-relative pe-4">
    <div class="position-relative">
        <div class="row g-2 flex-nowrap">
            @foreach($options as $key => $option)
            <div class="col-4">
                <div class="form-check form-check-type position-relative text-center">
                    <input class="form-check-input start-50 translate-middle top-50 m-auto position-absolute" type="radio" name="{{ $name }}" value="{{ $key }}" id="{{ $name }}{{ $key }}" @if ($value == $key) checked @endif>

                    <label class="form-check-label w-100" for="{{ $name }}{{ $key }}">
                        <span class="bg-light d-flex align-items-center justify-content-center">
                            <img src="/images/car-{{ $key }}.svg" alt="">
                        </span>
                        <span>{{ $option }}</span>
                    </label>
                </div>
            </div>
            @endforeach
            

            <!-- <div class="settings col-auto">
                <a class="dropdown-toggle btn p-0 text-secondary " href="#" role="button" data-toggle="dropdown" aria-expanded="false">
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
</div>