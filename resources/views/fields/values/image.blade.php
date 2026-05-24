<div class="js-editable" style="float: left" data-field="{{ $field->field }}" data-value="{{ $current->{$field->field} }}" data-type="{{ $field->type }}" style="{{ $field->label_color ? 'color:'.$field->label_color : ''}}">
    @if($current->{$field->field} && $current->{$field->field} != null && $current->{$field->field} != 'users/default.png')
        @php
            $photos = $current->getValue($field);
        @endphp
        @if(is_array($photos))
        @foreach($photos as $photo)
            @if(strstr($photo->name, '.pdf'))
            <div class="file-item file-item-pdf" data-id="{{ $photo->id }}">
                <div class="file-control-wrap">
                    <div class="file-control" role="button" data-toggle="dropdown" aria-expanded="true">
                        <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                    </div>
                    <ul class="dropdown-menu dropdown-menu__actions" aria-labelledby="dd184" x-placement="bottom-start" style="position: absolute; transform: translate3d(200px, 26px, 0px); top: 0px; left: 0px; will-change: transform;">
                        <li><a href="{{ Storage::disk()->url($photo->path) }}" target="_blank" class="dropdown-item">Просмотреть</a></li>
                        <li><span class="dropdown-item js-delete-file" data-id="{{ $photo->id }}"><span class="text-danger">Удалить</span></span></li>
                    </ul>
                </div>
                <a href="{{ str_replace('/public', '', Storage::disk('public')->url($photo->path)) }}" target="_blank">
                    <img src="/img/pdf.svg" height="30">
                </a>
            </div>
            @elseif(!strstr($photo->name, '.jpeg') && !strstr($photo->name, '.jpg') && !strstr($photo->name, '.png'))
            <div class="file-item file-item-pdf file-list-item" data-id="{{ $photo->id }}">
                <div class="file-control-wrap">
                    <div class="file-control" role="button" data-toggle="dropdown" aria-expanded="true">
                        <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                    </div>
                    <ul class="dropdown-menu dropdown-menu__actions" aria-labelledby="dd184" x-placement="bottom-start" style="position: absolute; transform: translate3d(200px, 26px, 0px); top: 0px; left: 0px; will-change: transform;">
                        <li><a href="{{ Storage::disk()->url($photo->path) }}" target="_blank" class="dropdown-item">Просмотреть</a></li>
                        <li><span class="dropdown-item js-delete-file" data-id="{{ $photo->id }}"><span class="text-danger">Удалить</span></span></li>
                    </ul>
                </div>
                <a href="{{ str_replace('/public', '', Storage::disk('public')->url($photo->path)) }}" target="_blank">
                    <img src="/img/pdf.svg" height="30">
                </a>
            </div>
            @else

            <div class="file-item" data-id="{{ $photo->id }}">
                <div class="file-control-wrap">
                    <div class="file-control" role="button" data-toggle="dropdown" aria-expanded="true">
                        <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                    </div>
                    <ul class="dropdown-menu dropdown-menu__actions" aria-labelledby="dd184" x-placement="bottom-start" style="position: absolute; transform: translate3d(200px, 26px, 0px); top: 0px; left: 0px; will-change: transform;">
                        <li><a href="{{ str_replace('/public', '', Storage::disk('public')->url($photo->path)) }}" data-fancybox="{{ $field->field }}" class="dropdown-item">Просмотреть</a></li>
                        <li><span class="dropdown-item js-delete-file" data-id="{{ $photo->id }}"><span class="text-danger">Удалить</span></span></li>
                    </ul>
                </div>
                <a href="{{ str_replace('/public', '', Storage::disk('public')->url($photo->path)) }}" data-fancybox="{{ $field->field }}-1">
                    <img src="{{ \Thumbnail::src('https://compas.pro'.Storage::disk()->url($photo->path))->heighten(200)->url() }}" height="96">
                </a>
            </div>

            @endif
        @endforeach
        @endif
        <input class="js-sort-files" type="hidden" value="">
    @else
        <span class="empty-val">не заполнено</span>
    @endif
</div>
