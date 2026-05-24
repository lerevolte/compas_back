<div class="js-editable" data-field="{{ implode(',', $subfield_names) }}" data-value="{{ implode(',', $subfield_values) }}" data-type="multiple_input">
    <div class="row g-2 flex-nowrap">
        @foreach($subfields as $subfield)
        <div class="col-4">
            <div class="label text-secondary">
                {{ $subfield->display_name }}
            </div>
            <div class="position-relative">
                {!! $current->{$subfield->field} ?? '<span class="empty-val">не заполнено</span>' !!}
            </div>
        </div>
        @endforeach
    </div>
</div>