<div id="addSection" class="fancy-modal">  
    <form class="form js-section-create">
        {{ csrf_field() }}
        
        <input type="hidden" name="page" value="{{ $model }}">
        <input type="hidden" name="column_id">
        <h5 class="section-title text-center mb-4">
            Добавить раздел
        </h5>
        
        <div class="mb-2">
            <div class="position-relative mb-3 px-5">
                <label for="#" class="label text-dark mb-1">
                    Название раздела
                </label>
                <input type="text" name="name" value="" class="form-control">
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary rounded-1 js-add-model" data-model="field_sections">Сохранить</button>
        </div>
    </form>
</div>