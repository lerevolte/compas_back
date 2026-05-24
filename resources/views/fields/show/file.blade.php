@php
    if(request()->edit) {
        $value = json_decode($value,true);
        if(is_array($value))
            $value = \App\Models\File::whereIn('id', $value)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$value).")"))->get();
    }
    $tenant = tenant('id');
@endphp
<div class="row g-2 flex-nowrap">
    <div class="col-lg-12">
        <div class="position-relative">

            <div class="file-list">
                @if($value)
                    @foreach($value as $val)
                        @if(strstr($val->name, '.pdf'))
                        <div class="file-item file-item-pdf file-list-item" data-id="{{ $val->id }}">
                            <div class="file-control-wrap">
                                <div class="file-control" role="button" data-toggle="dropdown" aria-expanded="true">
                                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                </div>
                                <ul class="dropdown-menu dropdown-menu__actions" aria-labelledby="dd184" x-placement="bottom-start" style="position: absolute; transform: translate3d(200px, 26px, 0px); top: 0px; left: 0px; will-change: transform;">
                                    <li><a href="{{ Storage::disk()->url($val->path) }}" target="_blank" class="dropdown-item">Просмотреть</a></li>
                                    <li><span class="dropdown-item js-delete-file" data-id="{{ $val->id }}"><span class="text-danger">Удалить</span></span></li>
                                </ul>
                            </div>
                            <a href="{{ '/storage/tenant'.$tenant.'/app/'.$val->path }}" target="_blank">
                                <img src="/img/pdf.svg" height="30">
                            </a>
                        </div>
                        @elseif(!strstr($val->name, '.jpeg') && !strstr($val->name, '.jpg') && !strstr($val->name, '.png'))
                        <div class="file-item file-item-pdf file-list-item" data-id="{{ $val->id }}">
                            <div class="file-control-wrap">
                                <div class="file-control" role="button" data-toggle="dropdown" aria-expanded="true">
                                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                </div>
                                <ul class="dropdown-menu dropdown-menu__actions" aria-labelledby="dd184" x-placement="bottom-start" style="position: absolute; transform: translate3d(200px, 26px, 0px); top: 0px; left: 0px; will-change: transform;">
                                    <li><a href="{{ Storage::disk()->url($val->path) }}" target="_blank" class="dropdown-item">Просмотреть</a></li>
                                    <li><span class="dropdown-item js-delete-file" data-id="{{ $val->id }}"><span class="text-danger">Удалить</span></span></li>
                                </ul>
                            </div>
                            <a href="{{ '/storage/tenant'.$tenant.'/app/'.$val->path }}" target="_blank">
                                <img src="/img/file.svg" height="30">
                            </a>
                        </div>
                        @else
                        <div class="file-item file-list-item" data-id="{{ $val->id }}">
                            <div class="file-control-wrap">
                                <div class="file-control" role="button" data-toggle="dropdown" aria-expanded="true">
                                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                </div>
                                <ul class="dropdown-menu dropdown-menu__actions">
                                    <li><a href="{{ Storage::disk()->url($val->path) }}" data-fancybox="{{ $field_data->field }}" class="dropdown-item">Просмотреть</a></li>
                                    <li><span class="dropdown-item js-delete-file" data-id="{{ $val->id }}"><span class="text-danger">Удалить</span></span></li>
                                </ul>
                            </div>
                            <a href="{{ '/storage/tenant'.$tenant.'/app/'.$val->path }}" data-fancybox="{{ $field_data->field }}-1">
                                <img src="{{ \Thumbnail::src(str_replace('/public', '/tenant'.$tenant.'/app/public', Storage::disk('public')->url($val->path)))->heighten(200)->url() }}" height="96">
                            </a>
                        </div>
                        @endif
                    @endforeach
                @endif

                <label class="btn rounded-1 position-relative add-file-btn">
                    <svg width="31px" height="24px" viewBox="0 0 31 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g transform="translate(-600.000000, -360.000000)">
                                <g transform="translate(361.000000, 259.000000)">
                                    <g transform="translate(222.000000, 91.000000)">
                                        <g transform="translate(17.540000, 10.000000)">
                                            <path d="M29.9189602,21.5904873 L29.9233594,23.2894261 C29.9079954,23.3688097 29.8737194,23.439543 29.8205315,23.5016259 C29.7673435,23.5637088 29.6928332,23.6069903 29.5970006,23.6314705 L0.370578125,23.6337157 C0.288202118,23.6141415 0.215294883,23.5792999 0.151856419,23.529191 C0.0884179565,23.4790821 0.03779915,23.4159885 0,23.3399104 L0,21.5904873 L29.9189602,21.5904873 Z M29.9201272,5.94987482 L29.9172886,21.2550055 L0.00561723148,21.2570795 L0.00561723148,5.95085955 L29.9201272,5.94987482 Z M15.1443074,7.0188337 C11.6207516,7.0188337 8.76434518,9.87462506 8.76434518,13.3974222 C8.76434518,16.9202194 11.6207516,19.7760107 15.1443074,19.7760107 C18.6678632,19.7760107 21.5242696,16.9202194 21.5242696,13.3974222 C21.5242696,9.87462506 18.6678632,7.0188337 15.1443074,7.0188337 Z M15.1443074,8.37959925 C17.9474918,8.37959925 20.2199217,10.6515399 20.2199217,13.4541208 C20.2199217,16.2567016 17.9474918,18.5286423 15.1443074,18.5286423 C12.341123,18.5286423 10.068693,16.2567016 10.068693,13.4541208 C10.068693,10.6515399 12.341123,8.37959925 15.1443074,8.37959925 Z M19.3955521,0 C19.4679771,0 19.5269459,0.00996509823 19.5724583,0.0298952947 C19.6179708,0.0498254911 19.6645892,0.0837296116 19.7123138,0.131607656 L19.7123138,0.131607656 L22.8055375,3.50536874 L29.5970006,3.50589048 C29.6928332,3.53037067 29.7673435,3.57365221 29.8205315,3.63573511 C29.8737194,3.69781801 29.9079954,3.76855127 29.9233594,3.84793489 L29.9233594,3.84793489 L29.9189602,5.54687362 L0,5.54687362 L0,3.79745058 C0.03779915,3.72137242 0.0884179565,3.65827889 0.151856419,3.60816998 C0.215294883,3.55806108 0.288202118,3.52321949 0.370578125,3.50364524 L0.370578125,3.50364524 L7.50587449,3.50419339 L10.6612439,0.134983227 C10.7066653,0.084854802 10.752184,0.0498254911 10.7978001,0.0298952947 C10.8434161,0.00996509823 10.9027354,0 10.9757578,0 L10.9757578,0 Z M5.81547362,1.74893476 L5.93907275,1.86173608 L5.93907275,3.15827711 L3.15699888,3.16943889 L3.14704205,1.86173608 L3.26682267,1.74893476 L5.81547362,1.74893476 Z" id="Combined-Shape" fill="#BCBCBC"></path>
                                            <ellipse fill="#FFFFFF" cx="21.6093357" cy="7.6708672" rx="1" ry="1"></ellipse>
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </g>
                    </svg>
                    {{ $field_data->button_name }}<input class="d-none js-file-list" type="file" multiple name="{{ $field_data->field }}[]">
                </label>
            </div>
            
            <input class="js-sort-files" type="hidden" value="">
            <input type="hidden" name="{{ $field_data->field }}" value="{{ $db_value ?? '' }}">
        </div>                       
    </div>
    
</div>