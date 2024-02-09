<ul class="position-relative row list-unstyled c-list js-sort-form js-filter-fields ui-sortable"> 
@foreach($filter->fields() as $field)
    @if($field->type == 'file' || $field->type == 'timestamp')
        @continue
    @endif
    @if($field->type != 'status')
        @include('fields.filter.'.$field->type, ['field_data' => $field, 'filter' => $filter ])
    @else
        @include('fields.values.'.$field->type, ['field' => $field, 'filter' => $filter])
    @endif
@endforeach
</ul>
<div>
    <div class="settings position-relative d-inline-block">
        <a class="dropdown-toggle link show me-2 fs-14" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="true">
            Выбрать поле
        </a>
        <ul class="dropdown-menu start-0">
            @if($hidden_fields = $filter->hidden_fields())
                @foreach($hidden_fields as $field)
                <li><a class="dropdown-item js-filter-field-add" href="javascript:;" data-filter="{{ $filter->id }}" data-type="{{ $filter->data_type }}" data-field="{{ $field->field }}">{{ $field->display_name }}</a></li>
                @endforeach
            @endif
        </ul>
    </div>
</div>