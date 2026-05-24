<div id="updateField" class="fancy-modal">  
    <form class="form" enctype="multipart/form-data">
        {{ csrf_field() }}
        <h5 class="section-title text-center mb-4">
            Настроить поле
        </h5>
        
        <div class="mb-2 field-content-edit">
            <!-- AJAX CONTENT -->
        </div>
        
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary rounded-1 js-field-update">Сохранить</button>
            <button class="btn btn-secondary rounded-1 js-close-modal">Отменить</button>
        </div>
    </form>
</div>
