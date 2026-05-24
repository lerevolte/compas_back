<div class="js-editable"  data-field="{{ $field->field }}" data-value="{{ $current->{$field->field} }}" data-type="{{ $field->type }}" style="{{ $field->label_color ? 'color:'.$field->label_color : ''}}">
    @if($current->{$field->field} && $current->{$field->field} != 'null')
        @php
            $photos = $current->getValue($field);
        @endphp
        @if($photos)
        @foreach($photos as $photo)
            @if(strstr($photo->name, '.pdf'))
            <div class="file-item file-item-pdf" data-id="{{ $photo->id }}">
                <a href="{{ str_replace('/public', '', Storage::disk('public')->url($photo->path)) }}" target="_blank" >
                    <img src="/img/pdf.svg" height="30">
                </a>
            </div>
            @elseif(!strstr($photo->name, '.jpeg') && !strstr($photo->name, '.jpg') && !strstr($photo->name, '.png'))
            <div class="file-item file-item-pdf file-list-item" data-id="{{ $photo->id }}">
                <a href="{{ str_replace('/public', '', Storage::disk('public')->url($photo->path)) }}" target="_blank">
                    <img src="/img/pdf.svg" height="30">
                </a>
            </div>
            @else

            <div class="file-item" data-id="{{ $photo->id }}">
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
