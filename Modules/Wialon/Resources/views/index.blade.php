@extends('layouts.main')
@section('content')
    <script type="text/javascript" src="https://hst-api.wialon.com/wsdk/script/wialon.js"></script>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.2/leaflet.css" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.2/leaflet.js"></script>
    <form method="post" action="/wialon/update">
        {{ csrf_field() }}
        <div class="mb-3">
            <label>Токен</label>
            <input name="token" class="form-control" type="text" value="{{ $config->token }}" placeholder="Введите токен">
        </div>
        <br>
        
        <div class="mb-3">
            <button class="btn btn-primary" type="submit">Сохранить</button>
        </div>
    </form>
    @if($config->token)
    <script type="text/javascript">
        var map, markers = {}, tile_layer, layers = {}; // global variables
        var cur_unit = null; // global variable
        var cur_prop = null; // global variable
        var sess = null;
        // Print message to log
        function msg(text) { $("#log").prepend(text + "<br/>"); }

        function init() { // Execute after login succeed
            var sess = wialon.core.Session.getInstance(); // get instance of current Session
            // flags to specify what kind of data should be returned
            var flags = wialon.item.Item.dataFlag.base | wialon.item.Unit.dataFlag.lastMessage;

            sess.loadLibrary("itemIcon"); // load Icon Library  
            sess.updateDataFlags( // load items to current session
            [{type: "type", data: "avl_unit", flags: flags, mode: 0}], // Items specification
                function (code) { // updateDataFlags callback
                    if (code) { msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code

                    // get loaded 'avl_unit's items  
                    var units = sess.getItems("avl_unit");
                    /*
                    avl_account
                    avl_resource
                    user
                    3
                    avl_unit_group
                    avl_retranslator
                    avl_route
                    */
                    if (!units || !units.length){ msg("Units not found"); return; } // check if units found

                    for (var i = 0; i< units.length; i++){ // construct Select object using found units
                        var u = units[i]; // current unit in cycle
                        // append option to select
                        console.log(u)
                        $("#units").append("<option value='"+ u.getId() +"'>"+ u.getName()+ "</option>");
                        $("#units-2").append("<option value='"+ u.getId() +"'>"+ u.getName()+ "</option>");
                        $("#units-3").append("<option value='"+ u.getId() +"'>"+ u.getName()+ "</option>");
                        $('.js-cars').each(function(){
                            if($(this).data('value') == u.getId())
                                $(this).append("<option selected value='"+ u.getId() +"'>"+ u.getName()+ "</option>");
                            else
                                $(this).append("<option value='"+ u.getId() +"'>"+ u.getName()+ "</option>");
                        })
                    }
                    // bind action to select change event
                    $("#units").change( getSelectedUnitInfo );
                    $("#units-2").change( getProperties );
                    $("#build").click( show_track );  // bind action to select change event
                    $("#tracks").on("click", ".close_btn", delete_track); //click, when need delete current track
                    $("#tracks").on("click", ".unit", focus_track); //click, when need to see any track
                    

                    var res = sess.getItems("avl_resource"); // get loaded 'avl_resource' items
                    if (!res || !res.length){ msg("No resources found"); return; } // check if resources found
                    
                    for (var i = 0; i< res.length; i++) // construct Select list using found resources
                        $("#res").append("<option value='"+ res[i].getId() +"'>"+ res[i].getName() +"</option>");

                    // bind resource change action
                    $("#res").change( function(){ 
                        var id_res = $("#res").val(); // get selected resource id
                        if(!id_res) return; // exit if no resource selected
                        var res = sess.getItem(id_res); // get Resource by id
                        // print message with selected resource name
                        msg("<b>"+ res.getName() +"</b> selected"); 
                    });
                }
            );
        }
        function getProperties(){ // construct properties Select list for selected item
            sess = wialon.core.Session.getInstance();
            if(!$("#units-2").val()){ msg("Properties item"); return;} // exit if no item selected
            
            clearForm(); // clear fields
            var id = parseInt( $("#units-2").val() );
          
            // IMPORTANT! for loading custom fields needed loaded library "itemCustomFields"
          
            sess.loadLibrary("itemCustomFields");

            // flags to specify what kind of data should be returned
            
            var flags = wialon.util.Number.or(wialon.item.Item.dataFlag.base, wialon.item.Item.dataFlag.customFields, wialon.item.Item.dataFlag.adminFields);
            
            sess.updateDataFlags( // load items to current session
            [{type: "type", data: "avl_unit", flags: flags, mode: 0}], // Items specification
                function (code) { // updateDataFlags callback
                    console.log('EEEE')
                    console.log(wialon.core.Errors.getErrorText(code))
                    if (code) { msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code
                    console.log('id '+id)
                    // get loaded 'avl_unit's item by ID  
                    var unit = sess.getItem( id );
                    console.log(unit)
                    var pr  = unit.getCustomFields();
                  
                    // save to global variable
                    cur_unit = unit;
                    cur_prop = pr;
                    console.log(pr)

                    // reset select
                    $("#props").html('<option>Выбрать</option>')
                    
                    for (var i in pr ) {  // construct select list


                        $("#props").append("<option value='" + pr[i].id + "'>" + pr[i].n + "</option>");
                        msg( 'load property: ' +  pr[i].n)
                    }
                        msg('');
                    
                    // bind action to select change event
                    $("#props").change( renderProp );
                
                }
            );
        }

        function clearForm(){ // clear fields function
            cur_prop = null;
            $("#prop_id").val("");
            $("#prop_name").val("");
            $("#prop_value").val("");
            $('#props').prop('selectedIndex',0);
        }
          
        function renderProp(){ // get and show information about selected property
            var prop_id = $("#props").val();

            if( !prop_id ){ msg("Select item"); return; } // exit if no item selected
            if( !$("#props").val() ){ clearForm(); return; } // clear fields if empty element selected

            // put property information to corresponding fields
            $("#prop_id").val( prop_id );
            $("#prop_name").val( cur_prop[prop_id].n );
            $("#prop_value").val( cur_prop[prop_id].v );
        }

        function createProperty(){ // create property for selected unit using entered data
            // get property information from corresponding fields
          
          
            var prop_id =  $("#prop_id").val(),
                name = $("#prop_name").val(),
                value = $("#prop_value").val();
          
            // validate ID
            if (prop_id in cur_prop) {
                msg('You can not create a property with an existing ID!');
                return;
            }

            // check empty field        
            if  ( !name.length || !value.length || !cur_unit) {
                msg('Please fill in all fields.')
                return;
            }
          
            // add property
            cur_unit.createCustomField( {id: prop_id, n: name, v: value} );

            msg( 'Property add: name=' + name + ', value=' +  value );

            // update DOM
            $('#units-2').change();
            getProperties();
        }

        function updateProperties(){ // update selected property using entered data
            // get property information from corresponding fields
          
          
            var prop_id =  $("#prop_id").val(),
                name = $("#prop_name").val(),
                value = $("#prop_value").val();

            // check exist editionly field  
            if  ( !(prop_id in cur_prop) || !name.length || !value.length || !cur_unit) {
                msg('Please fill in all fields.')
                return;
            }

            // check empty field        
            if  ( !name.length || !value.length || !cur_unit) {
                msg('Please fill in all fields.')
                return;
            }
            // update property
            cur_unit.updateCustomField( {id: prop_id, n: name, v: value} );

            msg( 'Property update: name=' + name + ', value=' +  value );

            // update DOM
            getProperties();
        }

        function deleteProperty(){ // delete selected property
            // get property information from corresponding fields    
            var prop_id =  $("#prop_id").val();

            if  ( !prop_id ) return;

            if ( !(prop_id in cur_prop) ) {
                msg('Property id not found in unit');
                return;
            }

            // confirm user for delete property;
            var answer = confirm('Do you really want to delete property "' + $("#prop_name").val() + '"?')

            if (!answer) return;
          
            // delete property
            cur_unit.deleteCustomField( prop_id );
            
            //delete cur_unit[name];

            msg( 'Property delete: id=' + prop_id );
            
            // update DOM
            clearForm();
            $('#units-2').change();
            getProperties();
        }
        function show_track () {
            var unit_id =  $("#units").val(),
                sess = wialon.core.Session.getInstance(), // get instance of current Session    
                renderer = sess.getRenderer(),

                cur_day = new Date(),
                from = Math.round(new Date(cur_day.getFullYear(), cur_day.getMonth(), cur_day.getDate()) / 1000), // get begin time - beginning of day
                to = from + 3600 * 24 - 1, // end of day in seconds
                unit = sess.getItem(unit_id), // get unit by id
                color = "ff0000" || "ffffff"; // track color
                if (!unit) return; // exit if no unit

                // check the existence info in table of such track 
                if (document.getElementById(unit_id))
                {
                    msg("You already have this track.");
                    return;
                }
              
                var pos = unit.getPosition(); // get unit position
                if(!pos) return; // exit if no position

                // callback is performed, when messages are ready and layer is formed
                callback =  qx.lang.Function.bind(function(code, layer) {
                    if (code) { msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code
                    
                    if (layer) { 
                        console.log(layer)
                        console.log(layer._data.units[0].trips)
                        var layer_bounds = layer.getBounds(); // fetch layer bounds
                        if (!layer_bounds || layer_bounds.length != 4 || (!layer_bounds[0] && !layer_bounds[1] && !layer_bounds[2] && !layer_bounds[3])) // check all bounds terms
                            return;
                        console.log('bounds')
                        console.log(layer_bounds)
                        // if map existence, then add tile-layer and marker on it
                        if (map) {
                           //prepare bounds object for map
                            var bounds = new L.LatLngBounds(
                            L.latLng(layer_bounds[0],layer_bounds[1]),
                            L.latLng(layer_bounds[2],layer_bounds[3])
                            );
                            map.fitBounds(bounds); // get center and zoom
                            // create tile-layer and specify the tile template
                            if (!tile_layer)
                                tile_layer = L.tileLayer(sess.getBaseUrl() + "/adfurl" + renderer.getVersion() + "/avl_render/{x}_{y}_{z}/"+ sess.getId() +".png", {zoomReverse: true, zoomOffset: -1}).addTo(map);

                            else 
                                tile_layer.setUrl(sess.getBaseUrl() + "/adfurl" + renderer.getVersion() + "/avl_render/{x}_{y}_{z}/"+ sess.getId() +".png");
                            // push this layer in global container
                            layers[unit_id] = layer;
                            // get icon
                            var icon = L.icon({ iconUrl: unit.getIconUrl(24) });
                            //create or get marker object and add icon in it
                            var marker = L.marker({lat: pos.y, lng: pos.x}, {icon: icon}).addTo(map);
                            
                            marker.setLatLng({lat: pos.y, lng: pos.x}); // icon position on map
                            marker.setIcon(icon); // set icon object in marker
                            markers[unit_id] = marker;      
                        }
                        // create row-string with data
                        console.log(unit)
                        var row = "<tr id='" + unit_id + "'>";  
                        // print message with information about selected unit and its position
                        row += "<td class='unit'><img src='" + unit.getIconUrl(16) + "'/> " + unit.getName() + "</td>"; 
                        row += "<td>Текущие координаты " + pos.x + ", " + pos.y + "<br> Пробег " + layer.getMileage() + "</td>";
                        row += "<td style='border: 1px solid #" + color + "'>     </td>";
                        row += "<td class='close_btn'>x</td></tr>";
                        //add info in table
                        $("#tracks-table").append(row);
                    }
            });
            // query params
            var flag = 0;
            $('.masks input:checked').each(function(){
                flag = flag + parseInt($(this).val());
            });
            // console.log($('input[name="from"]').val())
            // console.log((new Date($('input[name="from"]').val())));
            // from = Date.parse($('input[name="from"]').val());
            // from = from/1000;
            // console.log(from);
            // to = Date.parse($('input[name="to"]').val());
            // to = to/1000;
            // console.log(to);
            params = {
                "layerName": "route_unit_" + unit_id, // layer name
                "itemId": unit_id, // ID of unit which messages will be requested
                "timeFrom": from, //interval beginning
                "timeTo": to, // interval end
                "tripDetector": 1, //use trip detector: 0 - no, 1 - yes
                "trackColor": color, //track color in ARGB format (A - alpha channel or transparency level)
                "trackWidth": 5, // track line width in pixels
                "arrows": 1, //show course of movement arrows: 0 - no, 1 - yes
                "points": 1, // show points at places where messages were received: 0 - no, 1 - yes
                "pointColor": color, // points color
                "annotations": 1, //show annotations for points: 0 - no, 1 - yes
                "flags": flag//'0x00A0'//'0x0020'//['0x0020', '0x0080']
            };
            var rd = renderer.createMessagesLayer(params, callback);

        }

        // Флаг Значение
        // 0x0001   группировка маркеров
        // 0x0002   нумерация маркеров
        // 0x0004   маркеры событий
        // 0x0008   заправки
        // 0x0010   полученные изображения
        // 0x0020   стоянки
        // 0x0040   превышения скорости
        // 0x0080   остановки
        // 0x0100   сливы
        // 0x0800   маркеры видео
        function update_renderer () {
            var sess = wialon.core.Session.getInstance(),
                renderer = sess.getRenderer();
            if (tile_layer && tile_layer.setUrl)
                tile_layer.setUrl(sess.getBaseUrl() + "/adfurl" + renderer.getVersion() + "/avl_render/{x}_{y}_{z}/" + sess.getId() + ".png"); // update url-mask in tile-layer
        }

        function focus_track (evt) {
            var row = evt.target.parentNode, // get row with data by target parentNode
                unit_id = row.id; // get unit id from current row
            // get bounds for map
            if (layers && layers[unit_id])
                var bounds =  layers[unit_id].getBounds();
            if (bounds && map)
            {
                // create object with need params
                var map_bounds = new L.LatLngBounds(
                    L.latLng(bounds[0],bounds[1]),
                    L.latLng(bounds[2],bounds[3])
                );
                // set view in geting bounds
                map.fitBounds(map_bounds); // get center and zoom
            }
        }

        function delete_track (evt) {
            var row = evt.target.parentNode, // get row with data by target parentNode
                unit_id = row.id, // get unit id from current row
                sess = wialon.core.Session.getInstance(),
                renderer = sess.getRenderer();
            if (layers && layers[unit_id])
            {
                // delete layer from renderer
                renderer.removeLayer(layers[unit_id], function(code) { 
                    if (code) 
                        msg(wialon.core.Errors.getErrorText(code)); // exit if error code
                    else 
                        msg("Track removed."); // else send message, then ok
                });
                delete layers[unit_id]; // delete layer from container
            }
            // move marker behind bounds
            if (map)
                map.removeLayer(markers[unit_id]);
            delete markers[unit_id];
            // remove row from info table
            $(row).remove();
        }

        function init_map() {
            // create a map in the "map" div, set the view to a given place and zoom
            map = L.map('map').setView([53.9, 27.55], 10);
            var sess = wialon.core.Session.getInstance(); // get instance of current Session    
            // add WebGIS tile layer
            L.tileLayer(sess.getBaseGisUrl("render") + "/gis_render/{x}_{y}_{z}/" + sess.getCurrUser().getId() + "/tile.png", {
                zoomReverse: true, 
                zoomOffset: -1
            }).addTo(map);
        }

        function getSelectedUnitInfo(){ // print information about selected Unit

            var val = $("#units").val(); // get selected unit id
            if(!val) return; // exit if no unit selected
            
            var unit = wialon.core.Session.getInstance().getItem(val); // get unit by id
            if(!unit){ msg("Unit not found");return; } // exit if unit not found
            
            // construct message with unit information
            var text = "<div>'"+unit.getName()+"' selected. "; // get unit name
            var icon = unit.getIconUrl(32); // get unit Icon url
            if(icon) text = "<img class='icon' src='"+ icon +"' alt='icon'/>"+ text; // add icon to message
            var pos = unit.getPosition(); // get unit position
            if(pos){ // check if position data exists
                var time = wialon.util.DateTime.formatTime(pos.t);
                text += "<b>Last message</b> "+ time +"<br/>"+ // add last message time
                    "<b>Position</b> "+ pos.x+", "+pos.y +"<br/>"+ // add info about unit position
                    "<b>Speed</b> "+ pos.s; // add info about unit speed
                // try to find unit location using coordinates 
                wialon.util.Gis.getLocations([{lon:pos.x, lat:pos.y}], function(code, address){ 
                    if (code) { msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code
                    msg(text + "<br/><b>Location of unit</b>: "+ address+"</div>"); // print message to log
                });
            } else // position data not exists, print message
                msg(text + "<br/><b>Location of unit</b>: Unknown</div>");
        }


        function loadMessages(){ // load messages function
            var sess = wialon.core.Session.getInstance(); // get instance of current Session    
            var to = sess.getServerTime(); // get ServerTime, it will be end time
            var from = to - 3600*24; // get begin time ( end time - 24 hours in seconds )
            
            var unit = $("#units-3").val(); // get selected unit id
            if(!unit){ msg("Select unit first"); return; } // exit if no unit selected
            var ml = sess.getMessagesLoader(); // get messages loader object for current session

            ml.loadInterval(unit, from, to, 0,0, 100, // load messages for given time interval
                function(code, data){ // loadInterval callback
                    console.log(data)
                    if(code){ msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code
                    else { msg(data.count +" messages loaded. Click 'Show messages'");} // print success message 
                }
            );
        }

        function showMessages(from, to){ // print given indicies (from, to) of messages 
            $("#messages").html(""); // clear message container

            
      
            // get messages loader object for current session
            var ml = wialon.core.Session.getInstance().getMessagesLoader(); 
            ml.getMessages(from, to, //get messages data for given indicies
                function(code, data){ // getMessages callback
                    if(code){ msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code
                    else if(data.length == 0){ // exit if no messages loaded
                        msg("Nothing to show. Load messages first"); return;}
                    var from_index = from; // counter for display
                    for(var i=0; i<data.length; i++) // display result cycle
                        $("#messages").append( // append current message row to result table
                            "<tr"+ (i%2==1?" class='odd' ":"") +"><td>"+ (from_index++) +"</td>"+
                            // print Json data of current message
                            "<td>"+wialon.util.Json.stringify(data[i])+"</td></tr>"); 
                    msg(data.length + " messages shown from "+ from+" to "+ to); // Print message to log
                }
            );
        }

        // execute when DOM ready
        $(document).ready(function () {
            $("#load_btn").click( loadMessages );
            $("#show_btn").click( function(){ showMessages($("#show_from").val(),$("#show_to").val()); } );
            $("#create_btn").click( createProperty );
            $("#update_btn").click( updateProperties );
            $("#delete_btn").click( deleteProperty );
            wialon.core.Session.getInstance().initSession("https://hst-api.wialon.com"); // init session
            // For more info about how to generate token check
            // http://sdk.wialon.com/playground/demo/app_auth_token
            wialon.core.Session.getInstance().loginToken("{{ $config->token }}", "", // try to login
                function (code) { // login callback
                    // if error code - print error message
                    if (code){ msg(wialon.core.Errors.getErrorText(code)); return; }
                    msg("Logged successfully"); init_map();init(); // when login suceed then run init() function


            });
        });




    </script>
    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tracks-tab" data-toggle="tab" data-target="#tracks" type="button" role="tab" aria-controls="home" aria-selected="true">Треки</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="drivers-tab" data-toggle="tab" data-target="#drivers" type="button" role="tab" aria-controls="profile" aria-selected="false">Сопоставление машин</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="contact-tab" data-toggle="tab" data-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">Сообщения</button>
          </li>
    </ul>
    <div class="tab-content" id="myTabContent">
          <div class="tab-pane fade show active" id="tracks" role="tabpanel">
                <div class="mb-3">
                    <label>Машины</label>
                    <select id="units" class="form-control"><option></option></select>

                </div>
                <div class="mb-3 masks">
                    <label>Данныe на карте</label>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="0x0040" id="flexCheckDefault0">
                      <label class="form-check-label" for="flexCheckDefault0">
                        Превышение скорости
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="0x0008" id="flexCheckDefault1">
                      <label class="form-check-label" for="flexCheckDefault1">
                        Заправки
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="0x0020" id="flexCheckDefault2">
                      <label class="form-check-label" for="flexCheckDefault2">
                        Стоянки
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="0x0080" id="flexCheckDefault3">
                      <label class="form-check-label" for="flexCheckDefault3">
                         Остановки
                      </label>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Интервал</label>
                    <div class="row">
                        <div class="col-6">
                            <input name="from" class="form-control" type="text" value="{{ date('d.m.Y H:i:s', strtotime('-1 day')) }}" placeholder="От">
                        </div>
                        <div class="col-6">
                            <input name="to" class="form-control" type="text" value="{{ date('d.m.Y H:i:s') }}" placeholder="До">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <input class="btn btn-primary" id="build" type="button" value="Показать маршрут">
                </div>
                <br>
                <table id="tracks-table" class="table"></table>
                <div id="map" style="width:100%;height: 500px;"></div>
          </div>
          <div class="tab-pane fade" id="drivers" role="tabpanel">
            <form method="post" action="/wialon/update">
                {{ csrf_field() }}
                <input type="hidden" name="token" value="{{ $config->token }}">
                <div class="row mb-3">
                    @foreach($cars as $car)
                    <div class="col-6">
                        <input type="text" name="" disabled value="{{ $car->name }}">
                    </div>
                    <div class="col-6">
                        <select class="js-cars" name="config[cars][{{ $car->id }}]" data-value="{{ $params['cars'][$car->id] }}">
                         <option value="0">Не выбрано</option>   
                        </select>
                       
                    </div>
                    @endforeach
                </div>
                <button class="btn btn-primary" type="submit">Сохранить</button>
            </form>
          </div>
          <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                <div class="mb-3">
                    <label>Выберите машину</label>
                    <select id="units-3" class="form-control">
                        <option>Выбрать</option>
                    </select>
                </div>
                <input type="button" class="btn btn-primary mb-3" value="Загрузить сообщения за 24ч" id="load_btn"/><br/>
                <hr>
                <div class="row mb-3">
                    <div class="col-6">Показать сообщения с <input type="text" id="show_from" value="0" class="form-control" /></div>
                    <div class="col-6">по <input type="text" id="show_to" value="100" class="form-control"/></div>
                </div>
                <input type="button" value="Показать" id="show_btn" class="btn btn-primary mb-3" />
                <div class="wrap"><table id="messages"></table></div>
                <hr>
                <div id="log" class="d-none"></div>
          </div>
    </div>
    
    

    
    
    



    @endif
@endsection
