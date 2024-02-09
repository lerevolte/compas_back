@extends('layouts.main')
@section('content')
<link rel="stylesheet" type="text/css" href="{{ asset('css/leaflet-routing-machine.css?v=') }}<?=random_int(1, 20000)?>"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.2.0/dist/leaflet.css" />
        <link rel="stylesheet" type="text/css" href="{{ asset('css/leaflet-routing-machine.css?v=') }}<?=random_int(1, 20000)?>"/>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.datetimepicker.min.css') }}"/>
    <script src="https://unpkg.com/leaflet@1.2.0/dist/leaflet.js"></script>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
     <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
           integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
           crossorigin=""></script>
    <script src="https://api-maps.yandex.ru/2.1/?apikey=ef7607ff-665a-4e98-a65b-c73d97c69005&lang=ru_RU" type="text/javascript"></script>
    <script src="{{ asset('maps/layer/tile/Yandex.js') }}"></script>
    <script type="text/javascript" src="https://hst-api.wialon.com/wsdk/script/wialon.js"></script>
    <script src="{{ asset('js/leaflet-routing-machine.js') }}"></script>
    <script src="{{ asset('js/marker_cluster.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/jquery.datetimepicker.full.min.js') }}"></script>
    <div class="form-group mb-3">
        <select class="js-wialon-car form-control">
            <option value="0" selected>не выбрано</option>
            @foreach($cars as $car)
                @if(array_key_exists($car->id, $params['cars']) && $params['cars'][$car->id])
                <option value="{{ $params['cars'][$car->id] }}">{{ $car->name }}</option>
                @endif
            @endforeach
        </select>
    </div>
    <div class="form-group row mb-3">
        <div class="col-6">
            <input class="form-control" id="datetimepicker-1" type="text" name="" value="2023/06/27 00:00">
        </div>
        <div class="col-6">
            <input class="form-control" id="datetimepicker-2" type="text" name="" value="2023/06/27 18:00">
        </div>
    </div>
    <input type="button" class="btn btn-primary mb-3" value="Получить маршрут" id="load_btn"/><br/>
    <hr>
    <!-- <div class="row mb-3">
        <div class="col-6">Показать сообщения с <input type="text" id="show_from" value="0" class="form-control" /></div>
        <div class="col-6">по <input type="text" id="show_to" value="100" class="form-control"/></div>
    </div> -->
    <!-- <input type="button" value="Показать" id="show_btn" class="btn btn-primary mb-3" /> -->
    <div class="wrap"><table id="messages" class="d-none"></table></div>
    <hr>
    <div id="log" class="d-none"></div>
    <div id="messages" class="d-none">
    </div>
    <div class="map-wrapper w-100 h-100" style="position: sticky;border-radius: 5px;overflow: hidden;">
        <div id="map" class="h-100" style="min-height: 500px;">
            
        </div>
    </div>

    <script type="text/javascript">
        jQuery.datetimepicker.setLocale('ru');
        jQuery('#datetimepicker-1').datetimepicker();
        jQuery('#datetimepicker-2').datetimepicker();
        var map = L.map('map', {
            zoomControl: false,
            center: [55.72524,37.62896],
            zoom: 10
        });
        var stops = [];

        var mapCollectionDiections = L.layerGroup(), mapCollectionArrows = L.layerGroup();
        var mapCollection = [], mapCollectionClusters = L.layerGroup(), mapCollectionRouteClusters = L.layerGroup(), mapCollectionPlacemarks = L.layerGroup(), markerCollection = [], mapCollectionDrivers = L.layerGroup(), clusterLayer = L.layerGroup(), mapCollectionStops = L.layerGroup(), mapCollectionSignals = L.layerGroup();
        var counter_driver_draw = 0;
        var rotate_car;
        var markers, routeLayer, circle_point;
        var trigger_order = trigger_car = false;
        var routeClusters = {};
        var pane_active_route = map.createPane('active_route');

        @if($config->token)
        function msg(text) { $("#log").prepend(text + "<br/>"); }
        function init_wialon() { // Execute after login succeed
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
                    if (!units || !units.length){ msg("Units not found"); return; } // check if units found

                    for (var i = 0; i< units.length; i++){ // construct Select object using found units
                        var u = units[i]; // current unit in cycle
                        // append option to select
                        console.log(u)
                        $("#units").append("<option value='"+ u.getId() +"'>"+ u.getName()+ "</option>");
                        $('.js-cars').each(function(){
                            if($(this).data('value') == u.getId())
                                $(this).append("<option selected value='"+ u.getId() +"'>"+ u.getName()+ "</option>");
                            else
                                $(this).append("<option value='"+ u.getId() +"'>"+ u.getName()+ "</option>");
                        })
                    }
                    //$("#build").click( show_track );  // bind action to select change event
                    

                    var res = sess.getItems("avl_resource"); // get loaded 'avl_resource' items
                    if (!res || !res.length){ msg("No resources found"); return; } // check if resources found

                }
            );
        }
        function lngLatToLatLng(lngLat) {
          return [lngLat[1], lngLat[0]];
        }
        function show_track () {
            console.log('show_track0')
            if(routeLayer) {
                map.removeLayer(routeLayer);
            }
            mapCollectionStops.clearLayers();
            var unit_id =  $(".js-wialon-car").val(),
                sess = wialon.core.Session.getInstance(), // get instance of current Session    
                renderer = sess.getRenderer(),
                unit = sess.getItem(unit_id), // get unit by id
                color = "ff0000" || "ffffff"; // track color
                if (!unit) return; // exit if no unit
                var to = sess.getServerTime(); // get ServerTime, it will be end time
                var from = to - 3600*24; // get begin time ( end time - 24 hours in seconds )

                var date_1 = new Date($('#datetimepicker-1').val());
                var date_2 = new Date($('#datetimepicker-2').val()); 
                stops = [];
                from = date_1.getTime()/1000;
                to = date_2.getTime()/1000;
                console.log('show_track1')
                // check the existence info in table of such track 
                if (document.getElementById(unit_id))
                {
                    msg("You already have this track.");
                    return;
                }
                var pos = unit.getPosition(); // get unit position
                if(!pos) return; // exit if no position
                console.log('show_track2')
                // callback is performed, when messages are ready and layer is formed
                callback =  qx.lang.Function.bind(function(code, layer) {
                    console.log('show_track3')
                    console.log(code)
                    if (code) { msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code
                    
                    if (layer) { 
                        console.log(layer._data.units[0].trips)
                        $.each(layer._data.units[0].trips, function(index, trip){
                            if(index) {
                                console.log(layer._data.units[0].trips[index-1]['last']['time'])
                                stops.push([
                                    /*start: */layer._data.units[0].trips[index-1]['last']['time'],
                                    /*end: */layer._data.units[0].trips[index]['first']['time'],
                                    /*lat: */layer._data.units[0].trips[index-1]['last']['lat'],
                                    /*lng: */layer._data.units[0].trips[index-1]['last']['lon'],
                                    /*diff: */layer._data.units[0].trips[index]['first']['time'] - layer._data.units[0].trips[index-1]['last']['time']
                                ]);
                            }
                            
                        });
                        console.log(stops)
                        var icon,
                            marker,
                            distance,
                            in_radius,
                            from,
                            //wayPoints = mapCollection['route-'+tr_id].getWaypoints(),
                            point_service;


                        console.log('stops')
                        console.log(stops)
                        $.each(stops, function(index, point){
                            marker = L.marker([point[2], point[3]]);
                            from = marker.getLatLng();
                            in_radius = false;
                            // $.each(wayPoints, function (i, p) {
                            //     distance = getDistance(from, p.latLng);
                            //     if(distance < 300) {
                            //         in_radius = true;
                            //         point_service = p.options.order_id;
                            //     }
                            // });
                            if(in_radius) {
                                icon = new L.divIcon({
                                    className: 'unloading-marker route_marker',
                                    html: '<div style="width:22px;height:22px;display:flex;justify-content:center;align-items:center"><img src="/img/unloading.svg" style="max-width: 100%!important;)"><div class="place-time black" style="width: 75px;border: 1px solid #d68a8a;border-radius:5px">Обслуживание<span>с '+point.period+'<br><i>Время: </i>'+point.duration+' мин</span></div></div>',
                                    iconSize: null, 
                                })
                            } else {
                                var date = new Date(point[0] * 1000);
                                var hours = date.getHours();
                                var minutes = "0" + date.getMinutes();
                                var seconds = "0" + date.getSeconds();

                                var formattedTime_1 = hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);

                                date = new Date(point[1] * 1000);
                                hours = date.getHours();
                                minutes = "0" + date.getMinutes();
                                seconds = "0" + date.getSeconds();

                                var formattedTime_2 = hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);

                                icon = new L.divIcon({
                                    className: 'stop-marker route_marker',
                                    html: '<div style="width:22px;height:22px;display:flex;justify-content:center;align-items:center"><img src="/img/stop.svg?v=1" style="max-width: 100%!important;)"><div class="place-time black" style="width: 85px;border: 1px solid #d68a8a;border-radius:5px">Остановка<span>с '+formattedTime_1+'<br><i>Время: </i> '+Math.floor(point[4] / 60)+' мин</span></div></div>',
                                    iconSize: null, 
                                });
                            }
                            
                            
                            
                            marker.setIcon(icon);
                            marker.on('click', function(e) {
                                $('.route_marker.active .place-time').removeClass('active');
                                $('.route_marker.active').removeClass('active');
                                $(e.target._icon).addClass('active');
                                $(e.target._icon).find('.place-time').addClass('active')
                            });
                            marker.addTo(mapCollectionStops);
                        });
                        console.log('mapCollectionStops');
                        console.log(mapCollectionStops)
                        mapCollectionStops.addTo(map);
                    }
            });
            // query params
            var flag = 0;
            $('.masks input:checked').each(function(){
                flag = flag + parseInt($(this).val());
            });
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
        function getDistance(from, to)
        {
            return from.distanceTo(to).toFixed(0);
        }
        function createRouteWialon(points) {
            console.log('createRouteWialon')
            show_track();
            //return;

            



            var markers = L.markerClusterGroup({
                removeOutsideVisibleBound: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: false,
                spiderfyOnMaxZoom: false,
                //disableClusteringAtZoom: 19,
                maxClusterRadius: function(zoom) {
                    if(zoom > 18)
                        return 50;
                    else
                        return 80;
                },
                //spiderLegPolylineOptions: { weight: 1, color: '#222', opacity: 0.5 },
                iconCreateFunction : function(cluster) {
                    var val = 0,
                        childMarkers = cluster.getAllChildMarkers(),
                        total = childMarkers.length,
                        avg_point = (total/2).toFixed(0),
                        avg_point_index = (total/2).toFixed(0),
                        stop = false;
                    cluster.addTo(clusterLayer);
                    if(childMarkers[avg_point].options.speed == 0 && childMarkers[total - 1].options.speed == 0 && childMarkers[0].options.speed == 0) {
                        //avg_point_index = total - 1;
                        stop = true;
                    }
                    var from_avg = childMarkers[avg_point].getLatLng();
                    var from_last = childMarkers[total - 1].getLatLng();
                    var from_first = childMarkers[0].getLatLng();
                    var wayPoints = mapCollection['route-'+tr_id].getWaypoints();
                    var car_move = false, distance, diff, min_distance, in_radius = false, diff_next, diff_prev, distance_1, distance_2, distance_3, in_stop_radius = false;
                    

                    return new L.divIcon({
                        className: 'route_marker route_fact_marker',
                        html: '<div data-id="'+coords.lat+','+coords.lng+'" style="width:22px;height:22px;display:flex;justify-content:center;align-items:center"><img src="/img/leaflet_marker.svg" style="max-width: 100%!important;transform:rotate('+childMarkers[total-1].options.rotate+'deg)"><div class="place-time black" style="border: 1px solid #d68a8a;border-radius:5px">'+childMarkers[total-1].options.time+'<span><i>Дата: </i>'+childMarkers[total-1].options.date+'<br><i>Скорость: </i> '+childMarkers[total-1].options.speed+' км/ч</span></div></div>',
                        iconSize: null, 
                    })
                    
                }
            })


            // points.forEach(function(lngLat, i) {
            //     var diff, diff_prev;
            //     // if(points[i+1] !== undefined)
            //     //     diff = Math.abs(new Date(points_time[i+1]).getTime() - new Date(points_time[i]).getTime())/(1000*60);
            //     // if(points[i-1] !== undefined)
            //     //     diff_prev = Math.abs(new Date(points_time[i]).getTime() - new Date(points_time[i-1]).getTime())/(1000*60);
            //     var marker = L.marker(lngLatToLatLng(lngLat), {
            //         title: points_popup[i],
            //         speed: points_popup[i].toFixed(0),
            //         rotate: points_rotate[i],
            //         time: points_time_h_i[i],
            //         date: points_date[i],
            //         datetime: points_time[i],
            //         diff_prev: diff_prev,
            //         diff_next: diff
            //     });
            //     var from = marker.getLatLng();

            //     var wayPoints = mapCollection['route-'+tr_id].getWaypoints();
            //     var distance, icon, in_radius;
                
                

            //     icon = new L.DivIcon({
            //         className: 'route_marker route_fact_marker',
            //         html: '<div data-diff-prev="'+diff_prev+'" data-id="'+i+'" style="width:22px;height:22px;display:flex;justify-content:center;align-items:center"><img src="/img/leaflet_marker.svg" style="max-width: 100%!important;transform:rotate('+points_rotate[i]+'deg)"><div class="place-time black" style="border: 1px solid #d68a8a;border-radius:5px">'+points_time_h_i[i]+'<span><i>Дата: </i>'+points_date[i]+'<br><i>Скорость: </i> '+points_popup[i].toFixed(0)+' км/ч</span></div></div>',
            //         iconSize: null, 
            //     });
            //     marker.setIcon(icon);
            //     markers.addLayer(marker.on('click', function(e) {
            //         $('.route_marker.active .place-time').removeClass('active');
            //         $('.route_marker.active').removeClass('active');
            //         $(e.target._icon).addClass('active');
            //         $(e.target._icon).find('.place-time').addClass('active')
            //     }));//.bindPopup('<b>Скорость: </b>'+points_popup[i].toFixed(0)+' км/ч<br><b>Время: </b>'+points_time_h_i[i]).openPopup());
                
              
            //   i++;
            // });
            //map.addLayer(markers);

            var Lines = [{
                "type": "LineString",
                "coordinates": points,

            }];
            console.log('createRouteWialon2')
            var lineStyle = {
                dashArray: '5, 5',
            };
            //L.geoJson({"type": "Polygon", "coordinates": points}).setStyle(lineStyle).addTo(map);
            //routeLayer = L.geoJson({"type": "Polygon", "coordinates": [[55.72524,37.62896], [55.548125, 38.260335]]}).setStyle(lineStyle).addTo(map);

                //routeLayer.bringToBack();
            routeLayer = L.geoJSON(Lines, {
                style: lineStyle
            }).addTo(map);
            console.log('createRouteWialon3')
            routeLayer.addData(Lines);
            routeLayer.bringToBack();
            console.log('createRouteWialon4')
            console.log(routeLayer)
            // var multiRoute = L.Routing.control({
            //     useHints: false,
            //     waypoints: route,
            //     draggableWaypoints: false,
            //     createMarker: function(i, point, nWps) {
            //         return createMarker(tr_id, route_color, i, point, nWps);
            //     },
            //     lineOptions: {
            //         addWaypoints: false,
            //         styles: [{pane: pane_active_route, color: route_color, opacity: 1, weight: route_width}]
            //     },
            // });

            // multiRoute.addTo(map);
        }
        function loadMessages(){ // load messages function
            var date_1 = new Date($('#datetimepicker-1').val());
            var date_2 = new Date($('#datetimepicker-2').val());
            var sess = wialon.core.Session.getInstance(); // get instance of current Session    
            var to = sess.getServerTime(); // get ServerTime, it will be end time
            var from = to - 3600*24; // get begin time ( end time - 24 hours in seconds )
            
            from = date_1.getTime()/1000;
            to = date_2.getTime()/1000;
            var unit = $(".js-wialon-car").val(); // get selected unit id
            if(!unit){ msg("Select unit first"); return; } // exit if no unit selected
            var ml = sess.getMessagesLoader(); // get messages loader object for current session

            ml.loadInterval(unit, from, to, 0,0, 100, // load messages for given time interval
                function(code, data){ // loadInterval callback

                    if(code){ msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code
                    else { msg(data.count +" messages loaded. Click 'Show messages'");} // print success message 
                    showMessages(0, data.count)
                }
            );
        }

        function showMessages(from, to){ // print given indicies (from, to) of messages 
            $("#messages").html(""); // clear message container

            // get messages loader object for current session
            var ml = wialon.core.Session.getInstance().getMessagesLoader(); 
            ml.getMessages(from, to, //get messages data for given indicies
                function(code, data){ // getMessages callback
                    var route = [];
                    if(code){ msg(wialon.core.Errors.getErrorText(code)); return; } // exit if error code
                    else if(data.length == 0){ // exit if no messages loaded
                        alert('Данных за указанный период не найдено')
                        msg("Nothing to show. Load messages first"); return;}
                    var from_index = from; // counter for display
                    
                    
                    for(var i=0; i<data.length; i++) {
                        // console.log(data[i].pos['x'])
                        //console.log(data[i])
                        if(data[i].hasOwnProperty("pos") && data[i].pos !== null && data[i].pos.hasOwnProperty("x"))
                            // route.push(L.Routing.waypoint(L.latLng(data[i].pos['x'], data[i].pos['y']),'metka',{
                            //     speed: data[i].pos.s,
                            // }));
                            route.push([data[i].pos['x'], data[i].pos['y']]);
                        $("#messages").append( // append current message row to result table
                            "<tr"+ (i%2==1?" class='odd' ":"") +"><td>"+ (from_index++) +"</td>"+
                            // print Json data of current message
                            "<td>"+wialon.util.Json.stringify(data[i])+"</td></tr>"); 
                    }
                    msg(data.length + " messages shown from "+ from+" to "+ to); // Print message to log
                    console.log(route);

                    createRouteWialon(route)
                }
            );
        }
        $(document).ready(function () {
            //createRouteWialon([]);
            $("#load_btn").click( loadMessages );
            $("#show_btn").click( function(){ showMessages($("#show_from").val(),$("#show_to").val()); } );

            wialon.core.Session.getInstance().initSession("https://hst-api.wialon.com"); // init session
            // For more info about how to generate token check
            // http://sdk.wialon.com/playground/demo/app_auth_token
            wialon.core.Session.getInstance().loginToken("{{ $config->token }}", "", // try to login
                function (code) { // login callback
                    // if error code - print error message
                    if (code){ msg(wialon.core.Errors.getErrorText(code)); return; }
                    msg("Logged successfully"); init_wialon(); // when login suceed then run init() function


            });
        });
        @endif

        function traffic () {
            // https://tech.yandex.ru/maps/jsbox/2.1/traffic_provider
            var actualProvider = new ymaps.traffic.provider.Actual({}, { infoLayerShown: true });
            actualProvider.setMap(this._yandex);
        }

        function createRoute(route) {
            var multiRoute = L.Routing.control({
                useHints: false,
                waypoints: route,
                draggableWaypoints: false,
                // createMarker: function(i, point, nWps) {
                //     return createMarker(tr_id, route_color, i, point, nWps);
                // },
                // lineOptions: {
                //     addWaypoints: false,
                //     styles: [{pane: pane_active_route, color: route_color, opacity: 1, weight: route_width}]
                // },
            });

            multiRoute.addTo(map);
        }

        var baseLayers = {
            'Yandex map': L.yandex() // 'map' is default
                .addTo(map),
            'Yandex map + Traffic': L.yandex('map')
                .on('load', traffic),
            'Yandex satellite':  L.yandex({ type: 'satellite' }), // type can be set in options
            'Yandex hybrid':     L.yandex('hybrid'),
            'OSM': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            })
        };
    </script>
    <style>
        .leaflet-map-pane {
            height: 500px!important;
        }
    </style>
    <style>
        .js-hide {
            justify-content: center;
        }
        .ymaps-2-1-79-balloon-overlay {
            display: none!important;
        }
        [data-field="car_color"] {
            font-size: 0;
        }
        .payment-td .bg-status,.bg-status-payment {
            border-radius: 0;
        }
        .tooltip-inner {
            max-width: 320px;
        }
        .active-route {
            z-index: 651!important;
        }
        .table tbody tr.is_supply {
            
            font-weight: bold;
        }
        .orders-table-routes .sorting_1, #orders-table .sorting_1 {
            
            cursor: pointer;
        }
        #orders-table_filter {
            display: none;
        }





        .js-desc {
            width: 100px;
            min-height: 60px;
        }
        .car-item {
            display: flex;
            font-family: Helvetica;
            height: 171px;
            text-align: center;
            font-size: 14px;
            color: #000000;
            text-transform: uppercase;
            border: 1px solid #BCBCBC;
            margin-bottom: 30px;
            align-items: center;
            justify-content: center;
            padding: 5px;
            cursor: pointer;
        }
        .car-item.active,.car-item:hover {
            border: 1px solid #2F8CFF;
            color: #000000;
            text-decoration: none;
            box-shadow: 0 0 3px 0 #1253A2, inset 0 1px 2px 0 rgba(255,255,255,0.50);
        }
        #cars-table_wrapper {
            width: 100%;
            overflow: scroll;
        }
        /*#cars-table_wrapper {
            display: none;
        }*/
        .dt-buttons{
            display: none;
        }
        .table {
            position: relative;
        }
        /*.table-routes tbody tr td:first-child, .orders-table-routes tbody tr td:first-child {
            cursor: pointer;
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 9;
        }
        .table-routes thead tr th:first-child, .orders-table-routes thead tr th:first-child {
            position: sticky;
            left: 0;
            z-index: 99;
        }*/
        tr:focus {
            outline: none;
        }
        .search-results-item {
            color: #000;
        }
        .search-results-item:hover {
            text-decoration: none;
            color: #000;
            background: #ccc;
        }
        .circle-marker .place-time {
            top: -10px;
            left: -1px;
        }
        /*.place-time {
            display: inline-block;
            position: absolute;
            left: 5px;
            top: -6px;
            width: 80px;
            z-index: -1;
            height: 20px;
            line-height: 17px;
            background: #fff;
            border-radius: 0 5px 5px 0;
            border-width: 1px;
            border-style: solid;
            
            text-align: center;
        }*/
        .col-lg-6 {
            padding-right: 0.5rem;
            padding-left: 0.5rem;
        }
        .custom-control-label {
            max-width: 200px;
        }
        .orange {
            color: #fd8301;
        }
        .table-routes td {
            position: relative;
        }
        /*#routes-table_wrapper .sorting_1 {
            padding-left: 15px;
        }*/
        /*.orders-table-route__notification {
            position: sticky;
            left: calc(50% - 47px);
            display: flex;
            justify-content: center;

        }*/
        .orders-table-route__notification+.empty-car {
            display: none;
        }
        .orders-table-route__notification.d-none+.empty-car {
            display: flex;
        }
        .circle-marker>div {

            display: flex;
            background-size: contain;
            width: 22px!important;
            height: 22px!important;
            background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAYAAAAehFoBAAAAAXNSR0IArs4c6QAABJtJREFUWEfVWUtPI0cQ/grwQ2bARAYLLzKysewhQgQp2XvyA5Lck9yz9yT3sPfs3sN9N/fs/oDknkUiCIWxBbawWCODFQwD8gs6KmfGmWnP2B4bIrslHzxT3f11zVePriKMOIQQnwJQAXwCYA5ARloyC+AawDsAGhH9PsqWNMxkIQSD+xzAF8PMB/AGwFsi4kN4Gp4ACyE+A/AdgCeednEXfg/gJRH9Nuh6AwEWQjDAH43P3rW2rushXddna7VasNls+u/v76dYaGpq6t7n8zWCwWBNUZQbRVFuXYCxpp8TER+g5+gLWAjBn521yvzsDAZ5cXERqVarH7RaLV+/jfj9zMxMMxwO/724uFhxAM88Z20zXVxHT8BCCAb6tQy0VCrFrq6uFgYB6SYzPz9/GYvFSg7AXxPRS7d5roCFEEwBm1EVi8Un5XI5NgpQeW40Gi3F43GZCm+I6LnTPo6AhRDfA/jKnNBoNHz5fD6h6/r8Q4I111IU5SqZTBb8fn/Tsv4vRPRC3q8LsMFZ1m573N7eBo+Pj1P1ej3oBtbv9yMcDmN2dhaBQIC52hZttVqo1+u4ublBtVpFo9FwPW8gEKitra0dhUKhmkWIDdHGaRtgwxu8Mg2MNZvNZjNuYOfm5hCNRrGwMBidLy8vUS6XcX3N9tU9GHQmk8laNM2C31i9hwz4Z6vr0jQt7USD6elpxONxRCKRoRhSqVRQLBZxd3fXNZ/poapqzvLiHRE9M/93ABtB4SfzhZuBhUIhJJNJBIOuDBnoELVaDfl8ninXJe9giD+YwcUK+FczgrGP1TTtQ3klBptOpzscHQhZDyHmeC6XcwStqupfFpf3noi+5KXagI3cgOnQHrlcLiX7WabB+vr6yJqV8bOmDw8Pu+jBfjqdTh9Z5J9x7mEC3jaSGbhpN5FIDM3Zfl+COV0oFLrEJC1zsrRtAv7DlC4UCvFKpRK1zmZvkMnIWWM/GN7eZ7PZLu8RiUTKiUSi2DE4oqckG9ve3t5Hcm6QSqUGdl3eYP4nzS7v6MjKgH9zj62trT9ttBBCfAuAf4504KCwubk5LA5P8/b397uCi0SLHdbwDoCPeeWzs7Ol09PTVesuS0tLWF21PfIEwovwyckJzs/PbVNWVlZOlpeXzYe7DPi1ea1x4u9jGpt8GCfjk3icZcAdg3NyZ6qqQlEUL4oaWlbXdWiaZpsvuzcbYKdQvLGx8eC+1+1E7JMPDg5sr+VQPdmAJ4USE2d0E+fWJi5wcHGkkwePfWg20svJSX4MwBOXXtpoMfYJvKFlvk63iyRjeEUqEVG7qDO5l1BDyx2fzP/H5Jq/S0TtfN2mYQMwl1U58rXTszEopOhcjHQtpBigmSvjUqrq1CMcNWw+HJNi4A4RMUVto1e5teObzRn/Y7m1faV3ypv7FbRtZVfT5T1yQdtRsz0pYT2ZUX5l4LZ70iO0DNjAtvs1aPr2OCzegz9R+3YtjwdoyuwaYEdvykja5hDO2n6otkEJwIt+WrViGEjDskaN4iG7P24uDjPecnPx0RuLTsgM8NwZfWrw3Kl1y/zkFJaL0567n9Z9/wFp3TEYEjQYJAAAAABJRU5ErkJggg==);
            background-size: 22px 22px;
            margin-left: -11px!important;
            margin-top: -11px!important;
            justify-content: center;
            align-items: center;
        }
        /*.circle-marker .active {
            display: block;
            background: red;
            border-radius: 50%;
            width: 8px;
            height: 8px;
        }*/
        .circle-marker-supply>div {
            height: 28px!important;
            width: auto!important;
            background-repeat: repeat-x;
            background-position: 0 -60px;
            background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjAiIGhlaWdodD0iMTAwIj48cGF0aCBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9Ii44IiBkPSJNMCAxMy41QzAgNiA2IDAgMTMuNSAwUzI3IDYgMjcgMTMuNSAyMC45NiAyNyAxMy41IDI3IDAgMjEgMCAxMy41em0yMiAwYTguNSA4LjUgMCAxIDAtMTYuOTktLjAxQTguNSA4LjUgMCAwIDAgMjIgMTMuNXoiLz48Y2lyY2xlIGN4PSIxMy41IiBjeT0iMTMuNSIgcj0iMTEuNSIgZmlsbD0iIzFlOThmZiIvPjxjaXJjbGUgY3g9IjEzLjUiIGN5PSIxMy41IiByPSI4LjUiIGZpbGw9IiNmZmYiLz48Zz48bGluZWFyR3JhZGllbnQgaWQ9ImEiIHgxPSItMzQwIiB4Mj0iLTMzOS4xIiB5MT0iMzkxLjkiIHkyPSIzOTIuNyIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoMjEuMjE2MSAwIDAgLTIxLjc4NzkgNzI2Mi4wNCA4NTc1LjExKSI+PHN0b3Agb2Zmc2V0PSIwIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdG9wLW9wYWNpdHk9IjAiLz48L2xpbmVhckdyYWRpZW50PjxwYXRoIGZpbGw9InVybCgjYSkiIGQ9Ik00OC43MiAzOS43YzQuNzEtMi40IDE3LjgyLTExLjUyIDE4LjMtMTEuOTguNTYtLjUyIDEuMDctMS4wNiAxLjUtMS42MiAzLTQgMS4xMS03LjYyLTQuNDQtOC4wMy0uNDctLjAzLTEuMDYtLjEtMS42OC0uMTYtLjk2IDMuNDMtMy4yMiA3LjYtNi40MyAxMi4zM2ExMTQuMTIgMTE0LjEyIDAgMCAxLTYuMTQgOC4ybC0uNTQuNjdjLS4xMi4xNS0uMjcuMzItLjQ3LjUtLjAzLS4wMS0uMDItLjAxLS4xMi4wNmguMDJ6IiBvcGFjaXR5PSIuNSIvPjxwYXRoIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iLjgiIGQ9Ik0zNiAxMy41QzM2IDYgNDIgMCA0OS41IDBhMTMuNDQgMTMuNDQgMCAwIDEgMTMuMyAxNS44MmwuMDIuMTYtLjA0LjIzYy0uNiAzLjctMy4wNiA4LjQ3LTYuOCAxNGExMTguMzQgMTE4LjM0IDAgMCAxLTYuMTQgOC4yMmMtLjI1LjMtLjQ0LjUyLS41NS43YTMuNTkgMy41OSAwIDAgMS0xLjIuOTdjLS40LjItLjg2LjI3LTEuNC4ybC0uMjEtLjA0YTIuMjkgMi4yOSAwIDAgMS0xLjY2LTEuNjggMi40IDIuNCAwIDAgMSAwLTEuMTljMC0uMDcuMDItLjEzLjA0LS4xOWwyLjc3LTEwLjI2QTEzLjYxIDEzLjYxIDAgMCAxIDM2IDEzLjV6Ii8+PHBhdGggZmlsbD0iIzFlOThmZiIgZD0iTTM4IDEzLjVDMzggNy4xIDQzLjEgMiA0OS41IDJhMTEuNDQgMTEuNDQgMCAwIDEgMTEuMjggMTMuN3YuMTVDNTkuNSAyMy44NSA0Ny43IDM3LjggNDcuNyAzNy44cy0uMzguNTItLjczLjRjLS4zNy0uMS0uMi0uNTYtLjItLjU2bDMuNDItMTIuNzFjLS4yMS4wNy0uNDkuMDctLjY5LjA3QzQzLjE1IDI1IDM4IDE5LjkgMzggMTMuNXoiLz48Y2lyY2xlIGN4PSI0OS41IiBjeT0iMTMuNSIgcj0iOC41IiBmaWxsPSIjZmZmIi8+PC9nPjxnPjxwYXRoIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iLjgiIGQ9Ik0wIDYwaDEyMHYySDB6TTAgODVoMTIwdjJIMHoiLz48cGF0aCBmaWxsPSIjMWU5OGZmIiBkPSJNMCA2MmgxMjB2M0gwek0wIDgyaDEyMHYzSDB6Ii8+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTAgNjVoMTIwdjE3SDB6Ii8+PC9nPjwvc3ZnPg==);
            position: relative;
            z-index: 5;
            display: block;
            height: 27px;
            margin: 0 -3px;
            text-align: center;
            white-space: nowrap;
            color: #000;
            font: 13px Arial,sans-serif;
        }
        .circle-marker-supply>div:before {
            content: '';
            width: 13px;
            background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjAiIGhlaWdodD0iMTAwIj48cGF0aCBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9Ii44IiBkPSJNMCAxMy41QzAgNiA2IDAgMTMuNSAwUzI3IDYgMjcgMTMuNSAyMC45NiAyNyAxMy41IDI3IDAgMjEgMCAxMy41em0yMiAwYTguNSA4LjUgMCAxIDAtMTYuOTktLjAxQTguNSA4LjUgMCAwIDAgMjIgMTMuNXoiLz48Y2lyY2xlIGN4PSIxMy41IiBjeT0iMTMuNSIgcj0iMTEuNSIgZmlsbD0iIzFlOThmZiIvPjxjaXJjbGUgY3g9IjEzLjUiIGN5PSIxMy41IiByPSI4LjUiIGZpbGw9IiNmZmYiLz48Zz48bGluZWFyR3JhZGllbnQgaWQ9ImEiIHgxPSItMzQwIiB4Mj0iLTMzOS4xIiB5MT0iMzkxLjkiIHkyPSIzOTIuNyIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoMjEuMjE2MSAwIDAgLTIxLjc4NzkgNzI2Mi4wNCA4NTc1LjExKSI+PHN0b3Agb2Zmc2V0PSIwIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdG9wLW9wYWNpdHk9IjAiLz48L2xpbmVhckdyYWRpZW50PjxwYXRoIGZpbGw9InVybCgjYSkiIGQ9Ik00OC43MiAzOS43YzQuNzEtMi40IDE3LjgyLTExLjUyIDE4LjMtMTEuOTguNTYtLjUyIDEuMDctMS4wNiAxLjUtMS42MiAzLTQgMS4xMS03LjYyLTQuNDQtOC4wMy0uNDctLjAzLTEuMDYtLjEtMS42OC0uMTYtLjk2IDMuNDMtMy4yMiA3LjYtNi40MyAxMi4zM2ExMTQuMTIgMTE0LjEyIDAgMCAxLTYuMTQgOC4ybC0uNTQuNjdjLS4xMi4xNS0uMjcuMzItLjQ3LjUtLjAzLS4wMS0uMDItLjAxLS4xMi4wNmguMDJ6IiBvcGFjaXR5PSIuNSIvPjxwYXRoIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iLjgiIGQ9Ik0zNiAxMy41QzM2IDYgNDIgMCA0OS41IDBhMTMuNDQgMTMuNDQgMCAwIDEgMTMuMyAxNS44MmwuMDIuMTYtLjA0LjIzYy0uNiAzLjctMy4wNiA4LjQ3LTYuOCAxNGExMTguMzQgMTE4LjM0IDAgMCAxLTYuMTQgOC4yMmMtLjI1LjMtLjQ0LjUyLS41NS43YTMuNTkgMy41OSAwIDAgMS0xLjIuOTdjLS40LjItLjg2LjI3LTEuNC4ybC0uMjEtLjA0YTIuMjkgMi4yOSAwIDAgMS0xLjY2LTEuNjggMi40IDIuNCAwIDAgMSAwLTEuMTljMC0uMDcuMDItLjEzLjA0LS4xOWwyLjc3LTEwLjI2QTEzLjYxIDEzLjYxIDAgMCAxIDM2IDEzLjV6Ii8+PHBhdGggZmlsbD0iIzFlOThmZiIgZD0iTTM4IDEzLjVDMzggNy4xIDQzLjEgMiA0OS41IDJhMTEuNDQgMTEuNDQgMCAwIDEgMTEuMjggMTMuN3YuMTVDNTkuNSAyMy44NSA0Ny43IDM3LjggNDcuNyAzNy44cy0uMzguNTItLjczLjRjLS4zNy0uMS0uMi0uNTYtLjItLjU2bDMuNDItMTIuNzFjLS4yMS4wNy0uNDkuMDctLjY5LjA3QzQzLjE1IDI1IDM4IDE5LjkgMzggMTMuNXoiLz48Y2lyY2xlIGN4PSI0OS41IiBjeT0iMTMuNSIgcj0iOC41IiBmaWxsPSIjZmZmIi8+PC9nPjxnPjxwYXRoIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iLjgiIGQ9Ik0wIDYwaDEyMHYySDB6TTAgODVoMTIwdjJIMHoiLz48cGF0aCBmaWxsPSIjMWU5OGZmIiBkPSJNMCA2MmgxMjB2M0gwek0wIDgyaDEyMHYzSDB6Ii8+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTAgNjVoMTIwdjE3SDB6Ii8+PC9nPjwvc3ZnPg==);
            left: -13px;
            top: 0;
            background-position: 0 0;
            height: 27px;
            position: absolute;

        }
        .circle-marker-supply>div:after {
            content: '';
            background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjAiIGhlaWdodD0iMTAwIj48bGluZWFyR3JhZGllbnQgaWQ9ImEiIHgxPSItNjA4LjQiIHgyPSItNjA3LjQiIHkxPSIzNTQuMSIgeTI9IjM1NC45IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgyMS4yMTYxIDAgMCAtMjEuNzg3OSAxMjk5MS4xNiA3NzUyLjY0KSI+PHN0b3Agb2Zmc2V0PSIwIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdG9wLW9wYWNpdHk9IjAiLz48L2xpbmVhckdyYWRpZW50PjxwYXRoIGZpbGw9InVybCgjYSkiIGQ9Ik04NC43MiAzOS43YzQuNy0yLjQgMTcuODItMTEuNTIgMTguMy0xMS45OGExMy44IDEzLjggMCAwIDAgMS41LTEuNjJjMy00IDEuMTEtNy42Mi00LjQ0LTguMDMtLjQ3LS4wMy0xLjA2LS4xLTEuNjgtLjE2LS45NiAzLjQzLTMuMjIgNy42LTYuNDMgMTIuMzNhMTE2Ljc5IDExNi43OSAwIDAgMS02LjE0IDguMmwtLjU1LjY3Yy0uMTEuMTUtLjI3LjMyLS40Ni41LS4wMy0uMDEtLjAyLS4wMS0uMTIuMDZoLjAyeiIgb3BhY2l0eT0iLjUiLz48cGF0aCBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9Ii44IiBkPSJNODAgMjV2MmgzLjZsLTIuNzQgMTAuMTNhMi41MyAyLjUzIDAgMCAwLS4wNSAxLjM4Yy4yLjguOCAxLjUgMS43IDEuNjhoLjJjLjU0LjEgMSAwIDEuNC0uMTdhMy40IDMuNCAwIDAgMCAxLjE5LS45OGwuNTUtLjY1Yy40Ni0uNTcuOTgtMS4yIDEuNTItMS45IDEuNTctMi4wMiAzLjE1LTQuMTUgNC42Mi02LjMyIDMuNzUtNS41MiA2LjIxLTEwLjMgNi44LTE0bC4wNC0uMjMtLjAzLS4xMkExMy40NCAxMy40NCAwIDAgMCA4NS41IDBIODB2MiIvPjxwYXRoIGZpbGw9IiMxZTk4ZmYiIGQ9Ik04MCAyMnYzaDUuNWMuMjQgMCAuNDggMCAuNzEtLjAybC0zLjQzIDEyLjdzLS4xNi41LjIuNmMuMzUuMS43My0uNC43My0uNFM5NS41MSAyMy45IDk2LjggMTUuODlsLS4wMi0uMTVBMTEuNDQgMTEuNDQgMCAwIDAgODUuNSAySDgwdjMiLz48cGF0aCBmaWxsPSIjZmZmIiBkPSJNODUuNSA1SDgwdjE3aDUuNWE4LjUgOC41IDAgMCAwIDAtMTd6Ii8+PC9zdmc+);
            right: -26px;
            top: 0;
            width: 26px!important;
            height: 41px!important;
            background-position: -80px 0;
            position: absolute;
        }
        .leaflet-routing-container {
            display: none!important;
        }
        .map-menu .map-control {
            min-width: 70px;
        }
        div.dataTables_paginate {
            display:none;
        }
        .store-point {
            background-image: url(/img/store.png);
            margin-top: -18px;
            margin-left: -18px;
            width: 36px;
            height: 36px;
            background-size: 36px;
        }
        .store-point.active {
            background-image: url(/img/store_active.png);
        }
        .map-menu .map-control {
            min-width: 70px;
        }
        .js-show-directions, .js-show-arrows  {
            border-radius: 5px;
            width: 24px;
            height: 24px;
        }
        .js-lasso-btn/*,.js-fact-route-btn*/, .js-show-directions, .js-show-arrows {
            background: #fff;
            padding: 0;
            border: none;
            box-shadow: 2px 2px 2px 1px rgba(0, 0, 0, 0.2);
        }

        /*.js-fact-route-btn {
            height: 25px;
             padding: 0 5px;
            background: #fff;
            border-radius: 5px;
            border: 1px solid transparent;
        }
        .js-fact-route-btn.active {
            color: #1253A2;
            border-color: #1253A2;
        }*/
        .map-control-tools {
            box-shadow: 2px 2px 2px 1px rgba(0, 0, 0, 0.2);
        }
        .js-show-directions svg g, .js-show-arrows svg g{
            fill: #000;
        }
        .js-show-directions.active {
            color: #1253A2;
        }
        .js-lasso-btn.active svg rect+g, .js-show-directions.active svg g, .js-show-arrows.active svg g{
            fill: #1253A2;
        }
        .map-control.active {
            color: #1253a2;
        }
        [href="http://leafletjs.com"] {
            display: none!important;
        }
        .js-route-date a {
            pointer-events: none;
            text-decoration: none!important;
            color: #000!important;
            cursor: default;
        }
        .form-group {
            height: auto;
        }
        .route_marker {
            display: flex;
            justify-content: center;
        }
        .route_marker.active {
            z-index: 9999!important;
        }
        .route_marker .place-time {
            left: 10px;
            top: 0;
        }
        .route_marker span {
            display: none;
        }
        .route_marker .place-time.active {
            min-width: 124px;
            height: auto!important;
        }
        .route_marker .place-time.active span {
            display: block;
        }
        .route_marker i {
            font-style: normal;
            color: #aaa;
            font-size: 10px;
        }
        .geo-marker img {
            border: 2px solid;
            border-radius: 50%;
            border-color: #009e00;

        }

        .geo-marker-1 img {
            border-color: #96d35f;
        }
        .geo-marker-2 img {
            border-color: #be0000;
        }

        .geo-marker .place-time, .unloading-marker .place-time {
            padding-left: 20px;
            
        }
        .geo-marker .place-time {
            min-width: 124px;
        }
        .unloading-marker .place-time {
            min-width: 118px;
        }
        .geo-marker .place-time.active {
            min-width: 124px;
        }
        .geo-marker-1 .place-time.active {
            min-width: 153px;
        }
        .icon-a1227e47 {
            z-index: 201;
            position: absolute;
        }
        .icon-point-group-matrix {
            width: 25px;
            height: 100%;
        }
        .point-group .place-time {
            
             width: 60px;
            height: 25px;
            top: 6px;
            left: 26px;
            padding-left: 18px;
            border-radius: 5px!important;
        }
        .point-group .point-map-name {
            font-size: 16px;
            line-height: 22px;
        }
        .point-group.active .place-time {
            width: 105px;
            height: auto;
            text-align: left;
        }
        .point-group-list {
            position: relative;
            z-index: 999;
        }
        .point-group-list__item {
            display: flex;
            align-items: center;
        }
        .map-status-rect {
            width: 10px;
            height: 10px;
            margin-right: 5px;
        }
        .point-group-list__item.active {
            font-weight: bold;
        }
        .driver-icon {
            z-index: 1001!important;
        }
        
        .unloading-marker, .stop-marker {
            z-index: 998!important;
        }
        .circle-marker {
            z-index: 997!important;
        }
        .route-waypoint {
            z-index: 996!important;
        }
        .geo-marker {
            z-index: 994!important;
        }
        .geo-marker-1 {
            z-index: 995!important;
        }
        .route_fact_marker {
            z-index: 993!important;
        }
        .point-group-wrapper {
            width: 34px;
            height: 34px;
            background: black;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .point-group {
            margin-left: -18px!important;
            margin-top: -23px!important;
            z-index: 1008!important;
        }
        .route-waypoint .place-time {
            /*display: none;*/
        }
        /*path[stroke-width="3"] {
            z-index: 993!important;
        }
        path[stroke-dasharray] {
            z-index: 992!;
        }*/
        .route-waypoint.active-route {
            z-index: 997!important;
        }
        .unloading-marker.active, .stop-marker.active {
            z-index: 1010!important;
        }
        .circle-marker.active {
            z-index: 1009!important;
        }
        .point-group.active {
            z-index: 1009!important;
        }
        .route-waypoint.active {
            z-index: 1008!important;
        }
        .geo-marker.active {
            z-index: 1007!important;
        }
        .geo-marker-1.active {
            z-index: 1007!important;
        }
        .route_fact_marker.active {
            z-index: 1006!important;
        }

        .map-clusters {
            display: flex;
            width: max-content!important;
            border: none!important;
            padding-left: 0!important;
            padding-right: 0!important;
            overflow: hidden;
            border-radius: 5px;
            background: transparent;
        }
        .map-clusters-item {
            background: #fff;
            border: 1px solid;
            padding-left: 18px;
            padding-top: 25px;
            padding-bottom: 12px;
            position: relative;
        }
        .map-clusters div.map-cluster-item:first-child {
            border-radius: 5px 0 0 5px;
        }
        .map-clusters div.map-cluster-item:last-child {
            border-radius: 0 5px 5px 0;
        }
        .map-clusters-count {
            position: absolute;
            top: 4px;
            font-size: 16px;
        }
        /*.recovery-signal {
            z-index: 998!important;
        }*/
        
        
        #map2 {
            display: none;
        }
        .black-marker {
            background-color: #000!important;
        }
        .place-time {
display: inline-block;
position: absolute;
left: 6px;
top: -6px;
width: 68px;
z-index: -1;
height: 20px;
transition: width 2s, height 2s;
line-height: 18px;
background: #fff;
border-radius: 0 5px 5px 0;
border-width: 1px;
border-style: solid;
text-align: left;
padding-left: 14px;
padding-right: 11px;
overflow: hidden;
}
        
    </style>
@endsection
