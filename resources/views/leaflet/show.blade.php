<!DOCTYPE html>
<html>
<head>
    <title>Leaflet Map with Layer Control</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Подключаем Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
    <link rel="stylesheet" type="text/css" href="https://avixo.ru/css/leaflet-routing-machine.css">

    <style>
        #map {
            height: 500px;
            width: 100%;
        }
        .leaflet-control-layers {
            background: white;
            padding: 10px;
            border-radius: 5px;
        }
        .route-info {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px;
        }
    </style>
</head>
<body>
    <div id="map"></div>
    <div id="map-settings-container" class="leaflet-control leaflet-bar">
	    <a id="settings-button" href="#" title="Настройки"><span class="settings-icon"></span></a>

	    <div id="settings-menu" class="settings-panel">
	        <div id="main-menu" class="menu-panel active">
	            <div class="menu-item" data-submenu="orders-menu">Заказы <span class="arrow">›</span></div>
	            <div class="menu-item" data-submenu="route-display-menu">Отображение маршрута <span class="arrow">›</span></div>
	            <div class="menu-item" data-submenu="maps-menu">Карты <span class="arrow">›</span></div>
	            <div class="menu-item" data-submenu="analytics-menu">Аналитика <span class="arrow">›</span></div>
	        </div>

	        <div id="orders-menu" class="menu-panel submenu">
	            <div class="submenu-header"><span class="back-button">‹</span> Заказы</div>
	            <div class="menu-item radio-item"><label><input type="radio" name="orders" value="all" checked> Все</label></div>
	            <div class="menu-item radio-item"><label><input type="radio" name="orders" value="in_car"> В машине</label></div>
	            <div class="menu-item radio-item"><label><input type="radio" name="orders" value="unprocessed"> Показать необработанные</label></div>
	        </div>

	        <div id="route-display-menu" class="menu-panel submenu">
	            <div class="submenu-header"><span class="back-button">‹</span> Отображение маршрута</div>
	            <div class="menu-item radio-item"><label><input type="radio" name="route_display" value="standard" checked> Стандартный</label></div>
	            <div class="menu-item radio-item"><label><input type="radio" name="route_display" value="decorated"> С направлением</label></div>
	            <div class="menu-item radio-item"><label><input type="radio" name="route_display" value="vector"> Вектор движения</label></div>
	        </div>

	        <div id="maps-menu" class="menu-panel submenu">
	            <div class="submenu-header"><span class="back-button">‹</span> Карты</div>
	            <div class="menu-item radio-item"><label><input type="radio" name="map_type" value="OpenStreetMap" checked> OpenStreetMap</label></div>
	            <div class="menu-item radio-item"><label><input type="radio" name="map_type" value="Яндекс.Карты"> Яндекс.Карты</label></div>
	            <div class="menu-item radio-item"><label><input type="radio" name="map_type" value="Яндекс.Спутник"> Яндекс.Спутник</label></div>
	        </div>

	        <div id="analytics-menu" class="menu-panel submenu">
	            <div class="submenu-header"><span class="back-button">‹</span> Аналитика</div>
	            <div class="menu-item checkbox-item"><label><input type="checkbox" name="analytics" value="stops" checked> Остановки</label></div>
	            <div class="menu-item checkbox-item"><label><input type="checkbox" name="analytics" value="signal_loss" checked> Потеря сигнала</label></div>
	            <div class="menu-item checkbox-item"><label><input type="checkbox" name="analytics" value="actual_path" checked> Фактический маршрут</label></div>
	        </div>
	    </div>
	</div>
    
    <!-- Подключаем Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://api-maps.yandex.ru/2.1/?apikey=10946c08-3ea9-4fac-95d1-c833ee44dd6b&amp;suggest_apikey=ac70b70c-5e2e-4a1e-9d90-a66aa3b11ddd&amp;lang=ru_RU" type="text/javascript">
	</script>
    <!-- Подключаем плагин для Яндекс.Карт -->
    <script src="https://avixo.ru/maps/layer/tile/Yandex.js"></script>
    <script src="https://avixo.ru/js/leaflet-routing-machine.js?v=1"></script>
    
    <script src="https://unpkg.com/leaflet.markercluster@1.5.1/dist/leaflet.markercluster.js"></script>
    <script src="https://unpkg.com/leaflet-polylinedecorator@1.6.0/dist/leaflet.polylineDecorator.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/leaflet-arrowheads@1.4.0/src/leaflet-arrowheads.js"></script>
    <script>
        // Инициализация карты с центром на Москве
        var map = L.map('map').setView([55.751244, 37.618423], 12);
        
        // Определяем базовые слои
        var baseLayers = {
            "OpenStreetMap": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }),
            "Яндекс.Карты": L.yandex(),
            "Яндекс.Спутник": L.yandex({ type: 'satellite' }),
            "Яндекс.Гибрид": L.yandex('hybrid')
        };
        
        // Добавляем OSM слой по умолчанию
        baseLayers["OpenStreetMap"].addTo(map);
        
        // Добавляем контроль слоев
        //L.control.layers(baseLayers, null, {position: 'topright'}).addTo(map);
        
        // Функция для отображения пробок (если нужно)
        function traffic() {
            L.yandex.traffic().addTo(map);
        }

		const allRoutes = [];
		const markersCache = {};
		const actualPathLayers = [];
		const actualMarkerLayers = [];
		const settingsState = {
		    orders: 'all',
		    route_display: 'standard',
		    map_type: 'OpenStreetMap',
		    analytics: {
		        stops: true,
		        signal_loss: true,
		        actual_path: true
		    }
		};
		const unassignedTasksLayer = L.layerGroup();
		const stopMarkersLayer = L.layerGroup();
		const signalLossMarkersLayer = L.layerGroup();
		const plannedRouteDecoratorsLayer = L.layerGroup();
		let serviceRadiusCircle = null;
		let activeMarkerElement = null;

		

		/**
		 * Главная функция для отображения нескольких маршрутов.
		 * Учитывает время обслуживания (в минутах) и задачи без адреса.
		 * @param {Array} routesData - Массив объектов маршрутов.
		 */
		async function showMultipleRoutes(routesData) {
		    // Очистка карты от старых слоёв
		    map.eachLayer(layer => {
		        if (layer instanceof L.Marker || layer instanceof L.Routing.Control) {
		            map.removeLayer(layer);
		        }
		    });

		    actualPathLayers.forEach(layer => map.removeLayer(layer));
		    actualPathLayers.length = 0;
		    actualMarkerLayers.forEach(layer => map.removeLayer(layer)); 
		    actualMarkerLayers.length = 0;

		    allRoutes.length = 0;

		    unassignedTasksLayer.clearLayers();
		    stopMarkersLayer.clearLayers();
		    signalLossMarkersLayer.clearLayers();
		    plannedRouteDecoratorsLayer.clearLayers();


		    Object.keys(markersCache).forEach(key => delete markersCache[key]);

		    const routePromises = routesData.map(routeData => {
		        return new Promise((resolve) => {
		            const allTasks = routeData.tasks.sort((a, b) => a.order - b.order);
		            const tasksWithAddress = allTasks.filter(task => {
		                try {
		                    const addr = JSON.parse(task.address);
		                    return addr && addr.coords && addr.coords.length === 2;
		                } catch (e) { return false; }
		            });
		            
		            if (tasksWithAddress.length === 0) {
		                routeData.tasks = [];
		                resolve(routeData);
		                return;
		            }

		            const waypoints = tasksWithAddress.map(task => L.latLng(JSON.parse(task.address).coords));

		            const control = L.Routing.control({
		                waypoints,
		                routeWhileDragging: false,
		                show: false,
		                useHints: false,
		                createMarker: () => null,
		                lineOptions: {
		                    styles: [{ color: routeData.color || '#8601ff', opacity: 0.7, weight: 5 }]
		                }
		            }).addTo(map);

		            control.on('routesfound', function(e) {
		                const route = e.routes[0];
		                const [startHours, startMinutes] = routeData.loading_time.split(':').map(Number);
		                const startTime = new Date();
		                startTime.setHours(startHours, startMinutes, 0, 0);

		                const reachPoints = [];
		                let cumulativeTravelTime = 0;
		                route.instructions.forEach(instr => {
		                    cumulativeTravelTime += instr.time;
		                    if (instr.type === "WaypointReached" || instr.type === "DestinationReached") {
		                        reachPoints.push({ time: cumulativeTravelTime });
		                    }
		                });
		                
		                let cumulativeServiceTimeInSeconds = 0; // Счётчик теперь в секундах
		                let waypointIndex = 0;

		                const tasksWithTime = allTasks.map((task, taskIndex) => {
		                    let arrivalTime = null;
		                    let departureTime = null;
		                    const hasAddress = task.address && JSON.parse(task.address).coords;

		                    if (hasAddress) {
		                        const travelTime = (waypointIndex === 0) ? 0 : reachPoints[waypointIndex - 1].time;
		                        const sameCoordOffset = allTasks.slice(0, taskIndex)
		                            .filter(t => t.address && task.address && JSON.parse(t.address).coords.join() === JSON.parse(task.address).coords.join())
		                            .length * 60;
		                        
		                        const totalTimeInSeconds = travelTime + cumulativeServiceTimeInSeconds + sameCoordOffset;
		                        arrivalTime = new Date(startTime.getTime() + totalTimeInSeconds * 1000);
		                        
		                        // ✅ ИСПРАВЛЕНО: service_time (в минутах) * 60 (в секунды) * 1000 (в миллисекунды)
		                        departureTime = new Date(arrivalTime.getTime() + (task.service_time || 0) * 60 * 1000);
		                        
		                        waypointIndex++;
		                    }
		                    
		                    // ✅ Добавляем к общему времени обслуживания ВРЕМЯ В СЕКУНДАХ
		                    cumulativeServiceTimeInSeconds += (task.service_time || 0) * 60;

		                    return {
		                        ...task,
		                        latLng: hasAddress ? L.latLng(JSON.parse(task.address).coords) : null,
		                        arrivalTime,
		                        departureTime,
		                        routeId: routeData.id,
		                        routeColor: routeData.color || '#8601ff',
		                        routeName: routeData.name || `Маршрут ${routeData.id}`
		                    };
		                });
		                
		                routeData.tasks = tasksWithTime.filter(t => t.latLng && t.arrivalTime);
		                routeData.routingControl = control; // Сам объект control
		                routeData.coordinates = route.coordinates; // Координаты подробного пути
		                routeData.waypointCoords = waypoints; // Координаты только ключевых точек

		                resolve(routeData);
		            });
		             control.on('routingerror', function() {
		                routeData.tasks = [];
		                resolve(routeData);
		            });
		        });
		    });

		    const processedRoutes = await Promise.all(routePromises);
		    const adjustedRoutes = adjustPlannedTimes(processedRoutes);

		    allRoutes.push(...adjustedRoutes);


		    createCombinedMarkers(allRoutes);
		    drawActualPaths(processedRoutes);
		    showActualPathMarkers(processedRoutes);
		    showStopMarkers(processedRoutes);
		    showSignalLossMarkers(adjustedRoutes);

		    showUnassignedTasks(unassignedTasksFromLaravel);

		    map.addLayer(unassignedTasksLayer);
		    map.addLayer(stopMarkersLayer);
		    map.addLayer(signalLossMarkersLayer);
		    map.addLayer(plannedRouteDecoratorsLayer);

		    applyMapSettings();
		}

		/**
		 * Отображает маркеры Обслуживания и Остановки на карте.
		 * @param {Array} routes - Массив обработанных маршрутов.
		 */
		function showStopMarkers(routes) {
			stopMarkersLayer.clearLayers();

		    const createMarkers = (stops, type) => {
		        if (!stops || stops.length === 0) {
		            return;
		        }

		        const isService = type === 'service';
		        const markers = L.markerClusterGroup({
		            disableClusteringAtZoom: 17,
		            iconCreateFunction: cluster => L.divIcon({ html: '', className: 'hidden-cluster', iconSize: L.point(0, 0) })
		        });

		        stops.forEach(stop => {
		            const latLng = [stop.lat, stop.lon];
		            
		            // Определяем стили и контент в зависимости от типа
		            const label = isService ? 'Обслуживание' : 'Остановка';
		            const borderColor = isService ? '#bf0000;' : '#bf0000;';
		            
		            // Иконка для обслуживания (ваш SVG)
		            const serviceIconSvg = `
		                <svg width="19px" height="11px" viewBox="0 0 19 11">
						    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
						        <g transform="translate(-173, -403)" fill="#000000">
						            <g transform="translate(168, 393)">
						                <path d="M12.571875,17.2732143 C13.4518323,17.2732143 14.1651786,17.9865606 14.1651786,18.8665179 C14.1651786,19.7464751 13.4518323,20.4598214 12.571875,20.4598214 C11.6919177,20.4598214 10.9785714,19.7464751 10.9785714,18.8665179 C10.9785714,17.9865606 11.6919177,17.2732143 12.571875,17.2732143 Z M20.6727679,17.2732143 C21.5527251,17.2732143 22.2660714,17.9865606 22.2660714,18.8665179 C22.2660714,19.7464751 21.5527251,20.4598214 20.6727679,20.4598214 C19.7928106,20.4598214 19.0794643,19.7464751 19.0794643,18.8665179 C19.0794643,17.9865606 19.7928106,17.2732143 20.6727679,17.2732143 Z M18.7339286,10.2857143 C18.8818076,10.335588 18.9841886,10.3867785 19.0410714,10.4392857 C19.0979543,10.4917929 19.1619424,10.5941739 19.2330357,10.7464286 L19.2330357,12.5508929 L23.1875,12.5508929 C23.3207497,12.5863533 23.410333,12.6247461 23.45625,12.6660714 C23.502167,12.7073967 23.5405599,12.8097777 23.5714286,12.9732143 L23.5714286,18.7705357 C23.5379199,18.940305 23.499527,19.0554836 23.45625,19.1160714 C23.412973,19.1766593 23.3233896,19.2406474 23.1875,19.3080357 L22.7267857,19.3080357 L22.7267857,16.00625 L20.8071429,16.0446429 C20.7340023,16.0226928 20.6828119,15.9970976 20.6535714,15.9678571 C20.624331,15.9386167 20.5987358,15.8874262 20.5767857,15.8142857 L20.5767857,14.2401786 C20.6011199,14.1435347 20.6267152,14.0795466 20.6535714,14.0482143 C20.6804277,14.016882 20.7316182,13.9912867 20.8071429,13.9714286 L22.7267857,13.9714286 L22.7267857,13.4339286 L19.2330357,13.4339286 L19.2330357,17.4267857 C19.0969254,17.5157727 19.007342,17.5925584 18.9642857,17.6571429 C18.9212294,17.7217273 18.8828365,17.8369059 18.8491071,18.0026786 L14.4339286,18.0026786 C14.05,17.2092262 13.4229167,16.8125 12.5526786,16.8125 C11.6824405,16.8125 11.0681548,17.2092262 10.7098214,18.0026786 L10.01875,18.0026786 L10.01875,18.4633929 L10.5178571,18.4633929 C10.4922619,18.6169643 10.4794643,18.7449405 10.4794643,18.8473214 C10.4794643,18.9497024 10.4922619,19.1032738 10.5178571,19.3080357 L9.48125,19.3080357 L9.17410714,19.0008929 L9.17410714,16.275 L7.71517857,16.275 L7.71517857,17.4267857 L5.14285714,15.3919643 L7.71517857,13.2803571 L7.71517857,14.4321429 L10.7098214,14.4321429 L10.7098214,15.5071429 L13.2053571,13.5491071 L10.7098214,11.5526786 L10.7098214,12.6276786 L9.17410714,12.6276786 L9.17410714,10.7464286 C9.17410714,10.6440476 9.22529762,10.5416667 9.32767857,10.4392857 C9.43005952,10.3369048 9.51964286,10.2857143 9.59642857,10.2857143 L18.7339286,10.2857143 Z M12.571875,18.425 C12.3280314,18.425 12.1303571,18.6226743 12.1303571,18.8665179 C12.1303571,19.1103614 12.3280314,19.3080357 12.571875,19.3080357 C12.8157186,19.3080357 13.0133929,19.1103614 13.0133929,18.8665179 C13.0133929,18.6226743 12.8157186,18.425 12.571875,18.425 Z M20.6727679,18.425 C20.4289243,18.425 20.23125,18.6226743 20.23125,18.8665179 C20.23125,19.1103614 20.4289243,19.3080357 20.6727679,19.3080357 C20.9166114,19.3080357 21.1142857,19.1103614 21.1142857,18.8665179 C21.1142857,18.6226743 20.9166114,18.425 20.6727679,18.425 Z M18.6955357,18.4633929 C18.6699405,18.6510913 18.6571429,18.7918651 18.6571429,18.8857143 C18.6571429,18.9795635 18.6699405,19.1203373 18.6955357,19.3080357 L14.6258929,19.3080357 C14.6514881,19.128869 14.6642857,18.9880952 14.6642857,18.8857143 C14.6642857,18.7833333 14.6514881,18.6425595 14.6258929,18.4633929 Z" ></path>
						            </g>
						        </g>
						    </g>
						</svg>`;
		            
		            // Иконка для остановки (буква 'P')
		            // const parkingIconSvg = `
		            //     <svg width="14px" height="14px" viewBox="0 0 100 100">
		            //         <path d="M43.5,67.6H28.9V32.4h22.2c6.9,0,11.9,1.5,15.1,4.6c3.2,3.1,4.8,7.3,4.8,12.7c0,5-1.5,9.1-4.5,12.2c-3,3.1-7.7,4.7-13,4.7H43.5z M43.5,56.1h7.8c3.2,0,5.6-0.7,7.2-2.2c1.6-1.5,2.4-3.5,2.4-6.1c0-2.8-0.8-4.9-2.5-6.3c-1.7-1.4-4-2.1-7.1-2.1H43.5V56.1z" fill="#000000"></path>
		            //     </svg>`;
					const parkingIconSvg = 'P';

		            const markerHtml = `
		                <div class="actual-path-marker stop-marker">
		                    <div class="marker-main-view" style="border-color: ${borderColor};">
		                        <div class="icon-container">
		                            <div class="icon-background" style="background-color: ${borderColor};">
		                                ${isService ? serviceIconSvg : parkingIconSvg}
		                            </div>
		                        </div>
		                        <span class="marker-time-label">${label}</span>
		                    </div>
		                    <div class="route-popup__extend">
		                        <div class="point-attrs">
		                            <span class="point-attrs__item">
		                                <span class="point-attrs__label">Период:</span>
		                                <span class="point-attrs__val">с ${stop.start_time} по ${stop.end_time}</span>
		                            </span>
		                            <span class="point-attrs__item">
		                                <span class="point-attrs__label">Время:</span>
		                                <span class="point-attrs__val">${stop.duration} минут</span>
		                            </span>
		                        </div>
		                    </div>
		                </div>`;

		            const customIcon = L.divIcon({
		                className: 'custom-div-icon',
		                html: markerHtml,
		                iconSize: [120, 30],
		                iconAnchor: [15, 15]
		            });

		            const marker = L.marker(latLng, { icon: customIcon });
		            marker.on('click', handleActualMarkerClick);
		            markers.addLayer(marker);
		        });

		        // stopMarkerLayers.push(markers);
		        // map.addLayer(markers);

		        stopMarkersLayer.addLayer(markers);
		    };

		    routes.forEach(routeData => {
		        createMarkers(routeData.service_stops, 'service');
		        createMarkers(routeData.parking_stops, 'parking');
		    });
		}

		/**
		 * Рассчитывает скорректированное время, НЕ изменяя исходный план.
		 * @param {Array} routes - Массив обработанных маршрутов.
		 * @returns {Array} - Массив с добавленными полями adjustedTime и factTime.
		 */
		function adjustPlannedTimes(routes) {
		    routes.forEach(route => {
		        // Накопительная разница во времени (в миллисекундах)
		        let cumulativeDelta = 0;

		        route.tasks.forEach(task => {
		            // Исходный план не трогаем.
		            // Скорректированное время = плановое время убытия + дельта от предыдущих точек.
		            task.adjustedTime = new Date(task.departureTime.getTime() + cumulativeDelta);
		            
		            // Сбрасываем факт. время по умолчанию
		            task.factTime = '';

		            // Ищем факт обслуживания для текущей точки
		            const service = route.service_stops.find(s => s.related_task_id === task.id);

		            if (service) {
		                // Устанавливаем факт. время для отображения (начало обслуживания)
		                task.factTime = service.start_time;

		                // Создаем объект Date для времени ОКОНЧАНИЯ обслуживания
		                const [h, m] = service.end_time.split(':');
		                const actualServiceEnd = new Date(task.departureTime.getTime());
		                actualServiceEnd.setHours(h, m, 0, 0);

		                // Скорректированное время для ТЕКУЩЕЙ точки = время окончания обслуживания
		                task.adjustedTime = actualServiceEnd;

		                // Пересчитываем дельту для ВСЕХ ПОСЛЕДУЮЩИХ точек
		                cumulativeDelta = actualServiceEnd.getTime() - task.departureTime.getTime();
		            }
		        });
		    });
		    return routes;
		}

		/**
		 * Рисует линии фактических треков на карте.
		 * @param {Array} routes - Массив обработанных маршрутов.
		 */
		function drawActualPaths(routes) {
		    routes.forEach(routeData => {
		        if (routeData.actual_path && routeData.actual_path.length > 1) {
		            const coordinates = routeData.actual_path.map(p => [p.lat, p.lon]);
		            
		            const polyline = L.polyline(coordinates, {
		                color: '#0000FF',
		                weight: 4,
		                opacity: 0.7,
		                // ✅ ДОБАВЛЕНА ЭТА СТРОКА для пунктирной линии
		                dashArray: '10, 10' 
		            });
		            
		            actualPathLayers.push(polyline);
		            polyline.addTo(map);
		        }
		    });
		}

		/**
		 * Отображает кластеризованные маркеры для фактического трека с фильтрацией по расстоянию.
		 */
		function showActualPathMarkers(routes) {
		    // Минимальное расстояние в метрах между двумя маркерами
		    const MIN_DISTANCE_METERS = 200; 

		    routes.forEach(routeData => {
		        const pathPoints = routeData.actual_path;
		        if (!pathPoints || pathPoints.length === 0) {
		            return;
		        }

		        const markers = L.markerClusterGroup({
		             // Мы больше не отключаем кластеризацию, т.к. точек теперь всегда будет разумное количество
		            iconCreateFunction: cluster => L.divIcon({ html: '', className: 'hidden-cluster', iconSize: L.point(0, 0) })
		        });

		        let lastMarkerLatLng = null; // Запоминаем координаты последнего добавленного маркера

		        pathPoints.forEach((point, index) => {
		            const currentLatLng = L.latLng(point.lat, point.lon);

		            // Добавляем маркер, если это первая точка или расстояние от последней достаточно большое
		            if (!lastMarkerLatLng || lastMarkerLatLng.distanceTo(currentLatLng) > MIN_DISTANCE_METERS) {
		                
		                let bearing = 0;
		                if (index < pathPoints.length - 1) {
		                    const nextPoint = pathPoints[index + 1];
		                    bearing = calculateBearing(point.lat, point.lon, nextPoint.lat, nextPoint.lon);
		                } else if (index > 0) {
		                    const prevPoint = pathPoints[index - 1];
		                    bearing = calculateBearing(prevPoint.lat, prevPoint.lon, point.lat, point.lon);
		                }
		            
		                const speedKmh = (point.speed || 0) * 1.60934;
		            
		                const markerHtml = `
		                <div class="actual-path-marker">
		                    <div class="marker-main-view">
		                        <div class="icon-container">
		                            <div class="icon-background">
		                                <svg width="21px" height="21px" viewBox="0 0 21 21" version="1.1" style="transform: rotate(${bearing}deg);">
										    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
										        <g transform="translate(-173, -643)" fill="#FFFFFF">
										            <g transform="translate(162.5096, 634.5096)">
										                <g transform="translate(-0, -0)">
										                    <g transform="translate(0, -0)">
										                        <g transform="translate(20.4904, 20.4904) translate(-20.4904, -20.4904)translate(5.4904, 5.4904)">
										                            <path d="M15.1494358,9.06894691 L20.8386983,19.53719 C21.366145,20.5076919 21.0069778,21.722019 20.0364759,22.2494657 C19.5336745,22.5227274 18.9379025,22.567518 18.3998958,22.3725051 L13.3578793,20.544911 L13.3578793,20.544911 L8.39705498,22.3555009 C7.35943553,22.7342088 6.21127538,22.2000555 5.83256749,21.162436 C5.63583603,20.6234127 5.68009629,20.0258551 5.95409468,19.5216981 L11.6349377,9.06894691 C12.1623844,8.09844508 13.3767115,7.73927789 14.3472134,8.26672453 C14.6864494,8.45109194 14.9650683,8.72971089 15.1494358,9.06894691 Z" transform="translate(13.3922, 15.0023) translate(-13.3922, -15.0023)"/>
										                        </g>
										                    </g>
										                </g>
										            </g>
										        </g>
										    </g>
										</svg>
		                            </div>
		                        </div>
		                        <span class="marker-time-label">${point.time}</span>
		                    </div>
		                    <div class="route-popup__extend">
		                        <div class="point-attrs">
		                            <span class="point-attrs__item">
		                                <span class="point-attrs__label">Время:</span>
		                                <span class="point-attrs__val">${point.time}</span>
		                            </span>
		                            <span class="point-attrs__item">
		                                <span class="point-attrs__label">Скорость:</span>
		                                <span class="point-attrs__val">${speedKmh.toFixed(1)} км/ч</span>
		                            </span>
		                        </div>
		                    </div>
		                </div>`;
		                const customIcon = L.divIcon({
				                className: 'custom-div-icon',
				                html: markerHtml,
				                iconSize: [90, 30], // Ширина ~90px, высота 30px
				                iconAnchor: [15, 15]  // Якорь в центре синего круга
				            });

				            const marker = L.marker(currentLatLng, { icon: customIcon });
				            marker.on('click', handleActualMarkerClick);
				            markers.addLayer(marker);
		                
		                // Обновляем позицию последнего добавленного маркера
		                lastMarkerLatLng = currentLatLng; 
		            }
		        });

		        actualMarkerLayers.push(markers);
		        map.addLayer(markers);
		    });
		}
		/**
		 * Обрабатывает клик по маркеру фактического трека, показывая/скрывая кастомное окно.
		 * @param {Event} e - Событие клика Leaflet.
		 */
		function handleActualMarkerClick(e) {
			L.DomEvent.stopPropagation(e);

		    const clickedMarkerElement = e.target.getElement();
		    if (!clickedMarkerElement) return;

		    if (activeMarkerElement) {
		        activeMarkerElement.style.zIndex = '';
		    }
		    // Поднимаем текущий маркер наверх и запоминаем его
		    clickedMarkerElement.style.zIndex = 1000;
		    activeMarkerElement = clickedMarkerElement;

		    // Проверяем, был ли этот маркер уже активен
		    const wasActive = clickedMarkerElement.classList.contains('active');

		    // Сначала закрываем все другие активные маркеры
		    document.querySelectorAll('.actual-path-marker.active').forEach(activeMarker => {
		        activeMarker.classList.remove('active');
		    });

		    // Если кликнутый маркер не был активен, делаем его активным
		    if (!wasActive) {
		        clickedMarkerElement.querySelector('.actual-path-marker').classList.add('active');
		    }
		    
		    // Предотвращаем "всплытие" клика до карты, чтобы она не закрыла наш попап
		    L.DomEvent.stopPropagation(e);
		}

		/**
		 * Вычисляет азимут (направление) от одной точки до другой.
		 * @returns {number} - Угол в градусах.
		 */
		function calculateBearing(lat1, lon1, lat2, lon2) {
		    const dLon = (lon2 - lon1);
		    const y = Math.sin(dLon) * Math.cos(lat2);
		    const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);
		    let brng = Math.atan2(y, x) * (180 / Math.PI);
		    brng = (brng + 360) % 360;
		    return brng;
		}

		/**
		 * Отображает на карте задачи, не привязанные к маршрутам.
		 * @param {Array} tasks - Массив объектов задач.
		 */
		function showUnassignedTasks(tasks) {
			unassignedTasksLayer.clearLayers();

		    tasks.forEach(task => {
		        try {
		            const coords = JSON.parse(task.address).coords;
		            if (!coords || coords.length !== 2) return;

		            const markerHtml = generateUnassignedMarkerHTML(task);
		            const marker = L.marker(coords, {
		                icon: L.divIcon({
		                    className: 'unassigned-marker-container',
		                    html: markerHtml,
		                    iconAnchor: [15, 15]
		                })
		            });

		            unassignedTasksLayer.addLayer(marker);

		        } catch (e) {
		            console.error("Ошибка обработки задачи без маршрута:", task, e);
		        }
		    });
		}

		/**
		 * Генерирует HTML для маркера задачи без маршрута.
		 * @param {Object} task - Объект задачи.
		 * @returns {string} - HTML-строка.
		 */
		function generateUnassignedMarkerHTML(task) {
		    return `
		    <div class="unassigned-marker">
		        <div class="marker-label">
		            <span class="marker-checkbox"></span>
		            <span class="marker-id">${task.name}</span>
		        </div>
		        <div class="marker-details">
		            <p><b>Название:</b> ${task.name}</p>
		            <p><b>Статус точки:</b> <span class="status-indicator" style="background-color: ${task.statusColor};"></span></p>
		            <p><b>План. время:</b> ${task.planned_time || '—'}</p>
		            <p><b>Факт. время:</b> <span class="red">${task.fact_time || '—'}</span></p>
		        </div>
		    </div>
		    `;
		}
		/**
		 * Создает сгруппированные маркеры для всех точек на карте.
		 * @param {Array} processedRoutes - Массив маршрутов с обработанными задачами.
		 */
		function createCombinedMarkers(processedRoutes) {
		    const tasksByCoords = {};
		    processedRoutes.flatMap(route => route.tasks).forEach(task => {
		        const coordsKey = task.latLng.toString();
		        if (!tasksByCoords[coordsKey]) {
		            tasksByCoords[coordsKey] = [];
		        }
		        tasksByCoords[coordsKey].push(task);
		    });

		    Object.entries(tasksByCoords).forEach(([coordsKey, coordTasks]) => {
		        const firstTask = coordTasks[0];
		        const tasksByRoute = coordTasks.reduce((acc, task) => {
		            (acc[task.routeId] = acc[task.routeId] || []).push(task);
		            return acc;
		        }, {});

		        const routeCount = Object.keys(tasksByRoute).length;
		        const isMultipleRoutes = routeCount > 1;
		        const isSingleTask = coordTasks.length === 1;

		        let markerHtml;
		        let iconSize = [40, 40];
		        let iconAnchor = [20, 40];

		        if (isSingleTask) {
		            markerHtml = generateSinglePointHTML(firstTask);
		            iconSize = [150, 40];
		            iconAnchor = [75, 40];
		        } else if (isMultipleRoutes) {
		            markerHtml = generateMultiRouteHTML(tasksByRoute, routeCount);
		        } else { // Несколько точек одного маршрута
		            markerHtml = generateMultiPointHTML(coordTasks, firstTask.routeColor);
		        }

		        const marker = L.marker(firstTask.latLng, {
		            icon: L.divIcon({
		                className: '',
		                html: markerHtml,
		                //iconSize,
		                //iconAnchor,
		                popupAnchor: [0, -40]
		            }),
		            zIndexOffset: 1000
		        }).addTo(map);

		        marker.getElement().addEventListener('click', handlePopupInteraction);
		        
		        markersCache[coordsKey] = {
		            element: marker.getElement(),
		            tasksByRoute,
		            allTasks: coordTasks
		        };
		    });
		}

		/**
		 * Единый обработчик кликов внутри попапа маркера (делегирование).
		 * @param {Event} e - Событие клика.
		 */
		function handlePopupInteraction(e) {
		    const markerElement = e.currentTarget;
		    if (!markerElement) return;

		    if (activeMarkerElement) {
		        activeMarkerElement.style.zIndex = '';
		    }
		    // Поднимаем текущий маркер наверх и запоминаем его
		    markerElement.style.zIndex = 1000;
		    activeMarkerElement = markerElement;

		    const coordsKey = Object.keys(markersCache).find(key => markersCache[key].element === markerElement);
		    if (!coordsKey) return;

		    const cacheEntry = markersCache[coordsKey];
		    const extend = markerElement.querySelector('.route-popup__extend');
		    
		    // Закрываем другие открытые маркеры
		    document.querySelectorAll('.route-popup__extend.active').forEach(activeExtend => {
		        if (activeExtend !== extend) {
		            activeExtend.classList.remove('active');
		        }
		    });

		    const routeItem = e.target.closest('[data-route-id]');
		    const pointItem = e.target.closest('[data-task-id]');
		    const backButton = e.target.closest('.back-button');

		    e.stopPropagation();

		    if (backButton) {
		        const level = backButton.dataset.level;

		        if (level === 'points') { // Назад от списка точек к списку маршрутов
		            renderInitialContent(markerElement, cacheEntry);

		        } else if (level === 'details') { // Назад от деталей к списку точек
		            const routeCount = Object.keys(cacheEntry.tasksByRoute).length;
		            
		            if (routeCount > 1) {
		                // Если маршрутов много, возвращаемся к списку точек конкретного маршрута
		                const routeId = backButton.dataset.routeId;
		                renderRoutePoints(markerElement, routeId, cacheEntry);
		            } else {
		                // Если маршрут один (случай multiple-points), возвращаемся к начальному виду
		                renderInitialContent(markerElement, cacheEntry);
		            }
		        }
		        return;
		    }
		    
		    if (pointItem) {
		        const taskId = pointItem.dataset.taskId;
		        const task = cacheEntry.allTasks.find(t => t.id == taskId);
		        if (task) renderTaskDetails(markerElement, task); // Передаем только задачу
		        return;
		    }
		    
		    if (routeItem) {
		        const routeId = routeItem.dataset.routeId;
		        renderRoutePoints(markerElement, routeId, cacheEntry);
		        return;
		    }
		    
		    if (!backButton && !routeItem && !pointItem) {
		        const coordsKey = Object.keys(markersCache).find(key => markersCache[key].element === e.currentTarget);
		        if (coordsKey) {
		            const cacheEntry = markersCache[coordsKey];
		            const pointLatLng = cacheEntry.allTasks[0].latLng;
		            const radius = 500; // Радиус в метрах

		            // Удаляем старый круг, если он есть
		            if (serviceRadiusCircle) {
		                map.removeLayer(serviceRadiusCircle);
		            }
		            // Рисуем новый круг
		            serviceRadiusCircle = L.circle(pointLatLng, {
		                radius: radius,
		                color: '#ff0000',
		                fillColor: '#f03',
		                fillOpacity: 0.2
		            }).addTo(map);
		        }
		    }
		    // Клик по самому маркеру (не по элементу внутри)
		    if(extend) extend.classList.toggle('active');
		}


		/**
		 * Отображает детальную информацию о задаче.
		 */
		function renderTaskDetails(markerElement, task) {
		    const extend = markerElement.querySelector('.route-popup__extend');
		    

		    const timeStr = task.departureTime.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
		    
		    extend.innerHTML = `
		      <div class="point-details">
		          <button class="back-button" data-level="details" data-route-id="${task.routeId}">
		              <svg class="back-arrow" width="6" height="9" viewBox="0 0 6 9"><path d="M5.085.057L.75 4.325a.344.344 0 0 0-.068.222c0 .08.023.154.068.222l4.335 4.269a.587.587 0 0 0 .683-.102c.204-.228.261-.456.17-.729L1.917 4.547l3.78-3.619c.114-.25.068-.489-.137-.716a.544.544 0 0 0-.717-.17z" fill="#a6b7d4"/></svg>
		              Назад к точкам
		          </button>
		          <div class="point-attrs">
		              <span class="point-attrs__item"><span class="point-attrs__label">Название:</span><span class="point-attrs__val">${task.name}</span></span>
		              <span class="point-attrs__item"><span class="point-attrs__label">План. время:</span><span class="point-attrs__val">${timeStr}</span></span>
		              <span class="point-attrs__item"><span class="point-attrs__label">Факт. время:</span><span class="point-attrs__val red">${task.factTime || '—'}</span></span>
		          </div>
		      </div>`;
		    extend.classList.add('active');
		}

		// --- Функции рендеринга ---

		function renderInitialContent(markerElement, cacheEntry) {
		    const { tasksByRoute, allTasks } = cacheEntry;
		    const routeCount = Object.keys(tasksByRoute).length;
		    let newHtml;

		    if (routeCount > 1) {
		        newHtml = generateMultiRouteHTML(tasksByRoute, routeCount, true);
		    } else {
		        newHtml = generateMultiPointHTML(allTasks, allTasks[0].routeColor, true);
		    }
		    
		    markerElement.innerHTML = newHtml;
		    markerElement.querySelector('.route-popup__extend').classList.add('active');
		}

		// --- Функции рендеринга ---

		function renderRoutePoints(markerElement, routeId, cacheEntry) {
		    const extend = markerElement.querySelector('.route-popup__extend');
		    const routeTasks = cacheEntry.tasksByRoute[routeId].sort((a,b) => a.order - b.order);
		    const firstTask = routeTasks[0];

		    const pointsHtml = `
		      <div class="route-list">
		          <span class="route-list__item back-button" data-level="points">
		             <svg class="back-arrow" width="6" height="9" viewBox="0 0 6 9"><path d="M5.085.057L.75 4.325a.344.344 0 0 0-.068.222c0 .08.023.154.068.222l4.335 4.269a.587.587 0 0 0 .683-.102c.204-.228.261-.456.17-.729L1.917 4.547l3.78-3.619c.114-.25.068-.489-.137-.716a.544.544 0 0 0-.717-.17z" fill="#a6b7d4"/></svg>
		             <span class="route-popup__name">
		                <span class="route-popup__indicator" style="background-color: ${firstTask.routeColor}"></span>
		                <span>${firstTask.routeName}</span>
		             </span>
		          </span>
		          <div class="point-list">
		              ${routeTasks.map(task => {
		                  // ✅ Используем departureTime для отображения
		                  const timeStr = task.departureTime.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
		                  return `
		                  <span class="point-list__item" data-task-id="${task.id}">
		                      <span class="point-order">${task.order}.</span>
		                      <span class="point-name">${task.name}</span>
		                      <span class="route-popup__time">${timeStr} • <span class="red">${task.factTime || ''}</span></span>
		                  </span>`;
		              }).join('')}
		          </div>
		      </div>`;
		    
		    extend.innerHTML = pointsHtml;
		    extend.classList.add('active');
		}

		function renderTaskDetails(markerElement, task) {
		    const extend = markerElement.querySelector('.route-popup__extend');
		    const timeStr = task.departureTime.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
		    
		    extend.innerHTML = `
		      <div class="point-details">
		          <button class="back-button" data-level="details" data-route-id="${task.routeId}">
		              <svg class="back-arrow" width="6" height="9" viewBox="0 0 6 9"><path d="M5.085.057L.75 4.325a.344.344 0 0 0-.068.222c0 .08.023.154.068.222l4.335 4.269a.587.587 0 0 0 .683-.102c.204-.228.261-.456.17-.729L1.917 4.547l3.78-3.619c.114-.25.068-.489-.137-.716a.544.544 0 0 0-.717-.17z" fill="#a6b7d4"/></svg>
		              Назад к точкам
		          </button>
		          <div class="point-attrs">
		              <span class="point-attrs__item"><span class="point-attrs__label">Название:</span><span class="point-attrs__val">${task.name}</span></span>
		              <span class="point-attrs__item"><span class="point-attrs__label">План. время:</span><span class="point-attrs__val">${timeStr}</span></span>
		              <span class="point-attrs__item"><span class="point-attrs__label">Факт. время:</span><span class="point-attrs__val red">${task.factTime || '—'}</span></span>
		          </div>
		      </div>`;
		    extend.classList.add('active');
		}


		// --- Функции генерации HTML для маркеров ---

		function generateSinglePointHTML(task) {
			
		    const planTimeStr = task.departureTime.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
		    const adjustedTimeStr = task.adjustedTime.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });


		    return `
		    <div class="route-popup">
		        <div class="route-popup__main">
		            <span class="route-popup__counter" style="background-color: ${task.routeColor}">${task.order}</span>
		            <span class="route-popup__time">${planTimeStr} ${adjustedTimeStr ? '• <span class="red">' + adjustedTimeStr : ''}</span></span>
		        </div>
		        <div class="route-popup__extend">
		            <div class="point-attrs">
		                <span class="point-attrs__item"><span class="point-attrs__label">Название:</span><span class="point-attrs__val">${task.name}</span></span>
		                <span class="point-attrs__item"><span class="point-attrs__label">План. время:</span><span class="point-attrs__val">${planTimeStr}</span></span>
		                <span class="point-attrs__item"><span class="point-attrs__label">Факт. время:</span><span class="point-attrs__val red">${adjustedTimeStr || '—'}</span></span>
		            </div>
		        </div>
		    </div>`;
		}

		function generateMultiPointHTML(coordTasks, color, forceExtend = false) {
		    return `
		    <div class="route-popup multiple-points">
		        <div class="route-popup__main">
		            <span class="route-popup__counter" style="background-color: ${color}">
		            	<svg width="20px" height="20px" viewBox="0 0 20 20"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-1165, -279)"><g transform="translate(1160, 274)"><g transform="translate(5, 5.5)"><rect fill="#FFFFFF" x="0" y="5.5" width="8" height="8" rx="2"></rect><rect fill="#FFFFFF" x="15" y="0" width="5" height="5" rx="1"></rect><rect fill="#FFFFFF" x="15" y="7" width="5" height="5" rx="1"></rect><rect fill="#FFFFFF" x="15" y="14" width="5" height="5" rx="1"></rect><line x1="7.5" y1="7.92" x2="15.5" y2="2.92" stroke="#FFFFFF"></line><line x1="8" y1="9.5" x2="15" y2="9.5" stroke="#FFFFFF"></line><line x1="7.5" y1="11" x2="15.5" y2="16" stroke="#FFFFFF"></line></g></g></g></g></svg>
		            </span>
		            <span class="route-popup__time">${coordTasks.length}</span>
		        </div>
		        <div class="route-popup__extend ${forceExtend ? 'active' : ''}">
		            <div class="point-list">
		            ${coordTasks.sort((a,b) => a.order - b.order).map(task => {
		                // ✅ Используем departureTime для отображения
		                const timeStr = task.departureTime.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
		                return `
		                <span class="point-list__item" data-task-id="${task.id}">
		                    <span class="route-popup__indicator" style="background-color:${task.statusColor}"></span>
		                    <span>${task.order}. <span class="route-popup__time">${timeStr} • <span class="red">${task.factTime || ''}</span></span></span>
		                </span>`;
		            }).join('')}
		            </div>
		        </div>
		    </div>`;
		}
		function generateMultiRouteHTML(tasksByRoute, routeCount, forceExtend = false) {
		    return `
		    <div class="route-popup multiple-routes">
		        <div class="route-popup__main">
		            <span class="route-popup__counter" style="background: linear-gradient(135deg, #CA0000 0%, #0070AA 100%);">
		                <svg width="20px" height="20px" viewBox="0 0 20 20"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-1165, -279)"><g transform="translate(1160, 274)"><g transform="translate(5, 5.5)"><rect fill="#FFFFFF" x="0" y="5.5" width="8" height="8" rx="2"></rect><rect fill="#FFFFFF" x="15" y="0" width="5" height="5" rx="1"></rect><rect fill="#FFFFFF" x="15" y="7" width="5" height="5" rx="1"></rect><rect fill="#FFFFFF" x="15" y="14" width="5" height="5" rx="1"></rect><line x1="7.5" y1="7.92" x2="15.5" y2="2.92" stroke="#FFFFFF"></line><line x1="8" y1="9.5" x2="15" y2="9.5" stroke="#FFFFFF"></line><line x1="7.5" y1="11" x2="15.5" y2="16" stroke="#FFFFFF"></line></g></g></g></g></svg>
		            </span>
		            <span class="route-popup__time">${routeCount}</span>
		        </div>
		        <div class="route-popup__extend ${forceExtend ? 'active' : ''}">
		            <div class="route-list">
		                ${Object.values(tasksByRoute).map(routeTasks => {
		                    const task = routeTasks[0];
		                    return `
		                    <span class="route-list__item" data-route-id="${task.routeId}">
		                        <span class="route-popup__name">
		                            <span class="route-popup__indicator" style="background-color:${task.routeColor}"></span>
		                            <span>${task.routeName}</span>
		                        </span>
		                        ${routeTasks.length > 1 ? `<span class="route-popup__counter">${routeTasks.length}</span>` : ''}
		                        <svg width="6" height="9" viewBox="0 0 6 9"><path d="M.915 8.943L5.25 4.675a.344.344 0 0 0 .068-.222.344.344 0 0 0-.068-.222L.915.066a.587.587 0 0 0-.683.102C.027.35-.03.578.06.851l3.688 3.585L.06 8.056c-.114.25-.068.489.137.716.204.228.443.285.717.171z" fill="#a6b7d4"/></svg>
		                    </span>`;
		                }).join('')}
		            </div>
		        </div>
		    </div>`;
		}

		function handleSettingsChange(e) {
		    const input = e.target;
		    const { name, value, type, checked } = input;

		    if (type === 'radio') {
		        settingsState[name] = value;
		    }
		    if (type === 'checkbox') {
		        settingsState.analytics[value] = checked;
		    }

		    // Применяем изменения
		    applyMapSettings();
		}

		function applyMapSettings() {
		    // --- Заказы ---
		    if (settingsState.orders === 'in_car') {
		        map.removeLayer(unassignedTasksLayer);
		    } else {
		        map.addLayer(unassignedTasksLayer);
		    }

		    // --- Отображение маршрута ---
		    // Эта логика будет встроена в showMultipleRoutes

		    // --- Карты ---
		    Object.values(baseLayers).forEach(layer => map.removeLayer(layer));
		    baseLayers[settingsState.map_type].addTo(map);

		    // --- Аналитика ---
		    settingsState.analytics.stops ? map.addLayer(stopMarkersLayer) : map.removeLayer(stopMarkersLayer);
		    settingsState.analytics.signal_loss ? map.addLayer(signalLossMarkersLayer) : map.removeLayer(signalLossMarkersLayer);
		    
		    if (settingsState.analytics.actual_path) {
		        actualPathLayers.forEach(l => map.addLayer(l));
		        actualMarkerLayers.forEach(l => map.addLayer(l));
		    } else {
		        actualPathLayers.forEach(l => map.removeLayer(l));
		        actualMarkerLayers.forEach(l => map.removeLayer(l));
		    }

		    // Перерисовываем маршруты, чтобы применить новые стили
		    if(allRoutes.length > 0) {
		        redrawAllRoutes();
		    }
		}

		/**
		 * Перерисовывает все плановые маршруты в соответствии с выбранным стилем.
		 */
		function redrawAllRoutes() {
		    // Очищаем слой с кастомными элементами (стрелками)
		    plannedRouteDecoratorsLayer.clearLayers();

		    allRoutes.forEach(route => {
		        if (!route.routingControl || !route.coordinates) return;

		        // Сначала скрываем стандартный маршрут
		        //route.routingControl.hide();

		        switch (settingsState.route_display) {
		            case 'standard':
		                // Просто показываем стандартный маршрут (пунктирный)
		                L.polyline(route.coordinates, {
		                    color: route.color || '#8601ff',
		                    opacity: 0.7,
		                    weight: 5,
		                    dashArray: '10, 10'
		                }).addTo(plannedRouteDecoratorsLayer);
		                break;

		            case 'decorated':
		                // Рисуем сплошную линию
		                L.polyline(route.coordinates, {
		                    color: route.color || '#8601ff',
		                    opacity: 0.7,
		                    weight: 5
		                }).addTo(plannedRouteDecoratorsLayer);
		                
		                // Добавляем декоратор со стрелками
		                L.polylineDecorator(route.coordinates, {
		                    patterns: [{
		                        offset: 25,
		                        repeat: 100, // Увеличим расстояние между стрелками
		                        symbol: L.Symbol.arrowHead({
		                            pixelSize: 15,
		                            pathOptions: { color: route.color || '#8601ff', fillOpacity: 1, weight: 0 }
		                        })
		                    }]
		                }).addTo(plannedRouteDecoratorsLayer);
		                break;

		            case 'vector':
		                if (route.waypointCoords && route.waypointCoords.length > 1) {
		                    // Проходим по всем отрезкам маршрута (между двумя точками)
		                    for (let i = 0; i < route.waypointCoords.length - 1; i++) {
		                        const startPoint = route.waypointCoords[i];
		                        const endPoint = route.waypointCoords[i + 1];
		                        const segment = [startPoint, endPoint];

		                        // Рисуем сам отрезок (прямую линию)
		                        L.polyline(segment, { color: route.color || '#8601ff', weight: 5 }).addTo(plannedRouteDecoratorsLayer);

		                        // Добавляем одну стрелку в конце этого отрезка
		                        L.polylineDecorator(segment, {
		                            patterns: [{
		                                offset: '100%', // В самый конец линии
		                                repeat: 0,
		                                symbol: L.Symbol.arrowHead({
		                                    pixelSize: 15,
		                                    polygon: false,
		                                    pathOptions: {
		                                        stroke: true,
		                                        color: route.color || '#8601ff',
		                                        fillOpacity: 1,
		                                        weight: 5
		                                    }
		                                })
		                            }]
		                        }).addTo(plannedRouteDecoratorsLayer);
		                    }
		                }
		                break;
		        }
		    });
		}

		/**
		 * Отображает маркеры потери и восстановления сигнала.
		 * @param {Array} routes - Массив обработанных маршрутов.
		 */
		function showSignalLossMarkers(routes) {
		    signalLossMarkersLayer.clearLayers(); // Очищаем слой

		    const createMarker = (point, type, event) => {
		        const isLoss = type === 'loss';
		        const label = isLoss ? 'Потеря сигнала' : 'Восстановление';
		        const borderColor = '#f07178'; // Цвет для обоих маркеров
		        
		        const lossIconSvg = `<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12,5.5A6.5,6.5 0 0,1 18.5,12A6.5,6.5 0 0,1 12,18.5A6.5,6.5 0 0,1 5.5,12A6.5,6.5 0 0,1 12,5.5M12,3.5A8.5,8.5 0 0,0 3.5,12A8.5,8.5 0 0,0 12,20.5A8.5,8.5 0 0,0 20.5,12A8.5,8.5 0 0,0 12,3.5M2,12L4,12M20,12L22,12M12,2L12,4M12,20L12,22M19.07,4.93L17.66,6.34M6.34,17.66L4.93,19.07M19.07,19.07L17.66,17.66M6.34,6.34L4.93,4.93M16.24,14.83L14.83,16.24L12,13.41L9.17,16.24L7.76,14.83L10.59,12L7.76,9.17L9.17,7.76L12,10.59L14.83,7.76L16.24,9.17L13.41,12L16.24,14.83Z" /></svg>`;
		        const restoreIconSvg = `<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12,5.5A6.5,6.5 0 0,1 18.5,12A6.5,6.5 0 0,1 12,18.5A6.5,6.5 0 0,1 5.5,12A6.5,6.5 0 0,1 12,5.5M12,3.5A8.5,8.5 0 0,0 3.5,12A8.5,8.5 0 0,0 12,20.5A8.5,8.5 0 0,0 20.5,12A8.5,8.5 0 0,0 12,3.5M2,12L4,12M20,12L22,12M12,2L12,4M12,20L12,22M19.07,4.93L17.66,6.34M6.34,17.66L4.93,19.07M19.07,19.07L17.66,17.66M6.34,6.34L4.93,4.93Z" /></svg>`;

		        const markerHtml = `
		            <div class="actual-path-marker stop-marker">
		                <div class="marker-main-view" style="border-color: ${borderColor};">
		                    <div class="icon-container">
		                        <div class="icon-background" style="background-color: ${borderColor};">
		                           ${isLoss ? lossIconSvg : restoreIconSvg}
		                        </div>
		                    </div>
		                    <span class="marker-time-label">${label}</span>
		                </div>
		                <div class="route-popup__extend">
		                    <div class="point-attrs">
		                        <span class="point-attrs__item">
		                            <span class="point-attrs__label">Период:</span>
		                            <span class="point-attrs__val">${event.loss_point.time} - ${event.restore_point.time}</span>
		                        </span>
		                        <span class="point-attrs__item">
		                            <span class="point-attrs__label">Время:</span>
		                            <span class="point-attrs__val">${event.duration} минут</span>
		                        </span>
		                    </div>
		                </div>
		            </div>`;

		        const customIcon = L.divIcon({
		            className: 'custom-div-icon',
		            html: markerHtml,
		            iconSize: [150, 30],
		            iconAnchor: [15, 15]
		        });

		        const marker = L.marker([point.lat, point.lon], { icon: customIcon });
		        marker.on('click', handleActualMarkerClick);
		        signalLossMarkersLayer.addLayer(marker);
		    };

		    routes.forEach(routeData => {
		        if (!routeData.signal_loss_events) return;
		        routeData.signal_loss_events.forEach(event => {
		            createMarker(event.loss_point, 'loss', event);
		            createMarker(event.restore_point, 'restore', event);
		        });
		    });
		}
		
		const testRoutes = [
		    {
		        id: 123,
		        name: "ОПТ6, Длинномер SITRAK K902OB797",
		        loading_time: "07:00",
		        color: "#8601ff",
		        tasks: [
		            {
		                id: 1,
		                order: 1,
		                name: "Забрать груз",
		                address: '{"text":"Москва, Кремль","coords":[55.751244, 37.618423]}',
		                factTime: "07:05"
		            },
		            {
		                id: 2,
		                order: 2,
		                name: "Доставить клиенту 1",
		                address: '{"text":"Москва, ул. Тверская, 6","coords":[55.760857, 37.608263]}'
		            },
		            {
		                id: 3,
		                order: 3,
		                name: "Доставить клиенту 3",
		                address: '{"text":"Москва, ул. Арбат, 1","coords":[55.750446, 37.606529]}'
		            },
		            {
		                id: 4,
		                order: 4,
		                name: "Доставить клиенту 2",
		                address: '{"text":"Москва, ул. Тверская, 6","coords":[55.760857, 37.608263]}'
		            },
		            {
		                id: 45,
		                order: 5,
		                name: "Доставить клиенту 5",
		                address: '{"text":"Москва, ул. Арбат, 1","coords":[55.750446, 37.606529]}'
		            }
		        ]
		    },
		    {
		        id: 124,
		        name: "ОПТ7, Газель A123BC777",
		        loading_time: "08:30",
		        color: "#f00",
		        tasks: [
		            {
		                id: 5,
		                order: 1,
		                name: "Забрать груз",
		                address: '{"text":"Москва, Кремль","coords":[55.75124, 37.618423]}',
		                factTime: "08:35"
		            },
		            {
		                id: 6,
		                order: 2,
		                name: "Доставить клиенту A",
		                address: '{"text":"Москва, ул. Тверская, 6","coords":[55.76085, 37.608263]}'
		            }
		        ]
		    },
		    {
		        id: 125,
		        name: "ОПТ8, Камаз B456DE777",
		        loading_time: "09:15",
		        color: "#0f0",
		        tasks: [
		            {
		                id: 7,
		                order: 1,
		                name: "Забрать груз",
		                address: '{"text":"Москва, ул. Арбат, 1","coords":[55.750446, 37.606529]}'
		            },
		            {
		                id: 8,
		                order: 2,
		                name: "Доставить клиенту B",
		                address: '{"text":"Москва, Кремль","coords":[55.751244, 37.618423]}'
		            }
		        ]
		    }
		];

	
		const routesFromLaravel = @json($routes);
		const unassignedTasksFromLaravel = @json($unassignedTasks);

	    showMultipleRoutes(routesFromLaravel);
	    showUnassignedTasks(unassignedTasksFromLaravel);

	    map.on('click', function() {
		    document.querySelectorAll('.actual-path-marker.active, .route-popup__extend.active').forEach(activeEl => {
		        activeEl.classList.remove('active');
		    });

		    // ✅ ДОБАВЛЕННЫЙ БЛОК: Сбрасываем z-index у активного маркера
		    if (activeMarkerElement) {
		        activeMarkerElement.style.zIndex = '';
		        activeMarkerElement = null;
		    }
		    
		    // Удаляем круг радиуса
		    if (serviceRadiusCircle) {
		        map.removeLayer(serviceRadiusCircle);
		        serviceRadiusCircle = null;
		    }
		});

		// --- Логика работы меню настроек ---
		document.addEventListener('DOMContentLoaded', () => {
		    const settingsContainer = document.getElementById('map-settings-container');
		    const settingsButton = document.getElementById('settings-button');
		    const settingsMenu = document.getElementById('settings-menu');
		    
		    // По клику на кнопку - показать/скрыть меню
		    settingsButton.addEventListener('click', (e) => {
		        e.stopPropagation();
		        settingsMenu.style.display = settingsMenu.style.display === 'block' ? 'none' : 'block';
		    });

		    // Переключение на подменю
		    document.querySelectorAll('.menu-item[data-submenu]').forEach(item => {
		        item.addEventListener('click', () => {
		            const submenuId = item.dataset.submenu;
		            document.querySelector('.menu-panel.active').classList.remove('active');
		            document.getElementById(submenuId).classList.add('active');
		        });
		    });

		    // Возврат в главное меню
		    document.querySelectorAll('.back-button').forEach(button => {
		        button.addEventListener('click', () => {
		            document.querySelector('.menu-panel.active').classList.remove('active');
		            document.getElementById('main-menu').classList.add('active');
		        });
		    });
		    
		    // Клик вне меню - скрыть его
		    document.addEventListener('click', (e) => {
		        if (!settingsContainer.contains(e.target)) {
		            settingsMenu.style.display = 'none';
		        }
		    });

		    // Обработчики для всех инпутов
		    settingsContainer.addEventListener('change', handleSettingsChange);
		});
    </script>
    <style type="text/css">
    	.red {
			color: #b41802!important;
    	}
    	.route-popup {
    		position: relative;
    		display: inline-block;
    	}
    	.route-popup__main {
    		display: inline-flex;
    		align-items: center;
    		height: 30px;
			padding: 0 14px 0 0;
			border-radius: 7px;
			border: solid 1px #8601ff;
			background-color: #fff;
			font-family: Helvetica;
			font-size: 14px;
			font-weight: normal;
			font-stretch: normal;
			font-style: normal;
			line-height: normal;
			letter-spacing: normal;
			color: #000;
			width: max-content;
    	}
    	.route-popup__extend {
    		display: none;
/*    		width: calc(100% - 20px);*/
			width: max-content;
    		position: absolute;
			top: calc(100% + 10px);
			left: 0;
			border-radius: 7px;
			box-shadow: 0 0 3px 0 #000;
			background-color: #fff;
			overflow: hidden;
    	}
    	.route-popup__extend.active {
    		display: inline-block;
    	}
    	.route-popup__counter {
    		display: flex;
    		align-items: center;
    		justify-content: center;
    		width: 30px;
			height: 30px;
			margin: 0 10px 0 0;
			border-radius: 7px;
			font-family: Helvetica;
			font-size: 16px;
			font-weight: bold;
			font-stretch: normal;
			font-style: normal;
			line-height: normal;
			letter-spacing: normal;
			text-align: center;
			color: #fff;
    	}
    	.route-popup__indicator {
    		display: block;
    		min-width: 15px;
    		width: 15px;
			height: 15px;
			border-radius: 5px;
			background-color: #ccc;
			margin-right: 5px;
    	}
    	.route-popup__indicator_green {
    		background-color: #089a1e;
    	}
    	
    	.point-attrs {

    	}
    	.point-attrs__item {
    		display: flex;
    		align-items: center;
			font-family: Helvetica;
			font-size: 14px;
			font-weight: normal;
			font-stretch: normal;
			font-style: normal;
			line-height: normal;
			letter-spacing: normal;
			padding: 0 10px;
			height: 30px;
    	}
    	.point-attrs__label {
    		color: #8f8f8f;
    		margin-right: 10px;
    	}
    	.point-attrs__val {
    		color: #000;
    	}
    	.point-list__item, .route-list__item {
    		display: flex;
    		align-items: center;
    		font-family: Helvetica;
			font-size: 14px;
			font-weight: normal;
			font-stretch: normal;
			font-style: normal;
			line-height: normal;
			letter-spacing: normal;
			padding: 10px;
			min-height: 25px;
			max-width: 250px;
			justify-content: space-between;
    	}
    	.route-popup__name {
    		display: flex;
    		max-width: 220px;
    	}
    	.point-list__item:hover, .route-list__item:hover{
    		background-color: #eef3f8;
    		cursor: pointer;
    	}
    	.back-button {
    		background: transparent;
		    display: flex;
		    width: 100%;
		    min-height: 25px;
		    padding: 10px;
		    border: none;
		    cursor: pointer;
    	}
    	.back-button:hover {
    		background-color: #eef3f8;
    	}
    	/* Убираем стандартный фон и рамку иконки Leaflet */
		.unassigned-marker-container {
		    background: transparent;
		    border: none;
		}

		/* Основной контейнер маркера, на который вешается hover */
		.unassigned-marker {
		    position: relative;
		    cursor: pointer;
		}

		/* Видимая часть маркера (в свернутом состоянии) */
		.marker-label {
		    display: flex;
		    align-items: center;
		    border: 1px solid #a9a9a9;
		    background-color: #f0f0f0;
		    border-radius: 6px;
		    padding: 4px 8px 4px 4px;
		    white-space: nowrap;
		    font-family: Helvetica, sans-serif;
		    font-size: 14px;
		    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
		}

		/* Квадратик слева, имитирующий чекбокс */
		.marker-checkbox {
		    width: 16px;
		    height: 16px;
		    border: 1px solid #b0b0b0;
		    background: #fff;
		    border-radius: 3px;
		    margin-right: 6px;
		}

		/* Детальная информация (всплывающее окно) */
		.marker-details {
		    display: none; /* Скрыто по умолчанию */
		    position: absolute;
		    top: 100%;
		    left: 0;
		    margin-top: 8px; /* Отступ от маркера */
		    background: #fff;
		    border: 1px solid #ccc;
		    border-radius: 7px;
		    padding: 10px;
		    width: max-content;
		    min-width: 220px;
		    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
		    z-index: 1000;
		}

		/* Показываем детали при наведении на весь маркер */
		.unassigned-marker:hover .marker-details {
		    display: block;
		}

		.marker-details p {
		    margin: 0 0 5px 0;
		    font-family: Helvetica, sans-serif;
		    font-size: 14px;
		    display: flex;
		    align-items: center;
		}
		.marker-details p:last-child {
		    margin-bottom: 0;
		}

		/* Цветной кружок статуса */
		.status-indicator {
		    display: inline-block;
		    width: 14px;
		    height: 14px;
		    border-radius: 50%;
		    margin-left: 8px;
		    border: 1px solid rgba(0,0,0,0.1);
		}
		.unassigned-marker-container {
			width: auto!important;
		}

		/* Убираем фон и рамку у стандартной обертки иконки Leaflet */
		.custom-div-icon {
		    background: transparent;
		    border: none;
		    width: auto!important;
		}

		/* Убираем фон и рамку у стандартной обертки иконки Leaflet */
		.custom-div-icon {
		    background: transparent;
		    border: none;
		}

		/* Основной контейнер нашего маркера */
		.actual-path-marker {
		    position: relative;
		    cursor: pointer;
		}

	
		.marker-main-view {
		    display: flex;
		    align-items: center;
		    height: 30px;
		    padding-right: 12px;
		    border-radius: 15px 7px 7px 15px; /* Полностью скругленные края */
		    border: 1px solid #0000FF; /* Синяя рамка */
		    background-color: #fff;
		    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
		    white-space: nowrap;
		    font-family: Helvetica, sans-serif;
		}

		/* Контейнер для иконки (круга) */
		.actual-path-marker .icon-container {
		    /* Этот элемент больше не нужен для позиционирования, но оставляем для структуры */
		}

		/* Стили для круглого фона иконки */
		.actual-path-marker .icon-background {
		    display: flex;
		    justify-content: center;
		    align-items: center;
		    width: 30px; /* Делаем круг чуть больше */
		    height: 30px;
		    background-color: #0000FF; /* Синий фон */
		    border-radius: 50%; /* Делаем его круглым */
		    /* Убираем рамку, т.к. она теперь на родительском блоке */
		    /* box-shadow теперь на родительском блоке */
		}

		/* Стили для SVG внутри иконки */
		.actual-path-marker svg {
		    width: 16px;
		    height: 16px;
		    transform-origin: center; 
		}

	
		.marker-time-label {
		    font-family: Helvetica;
		    font-size: 14px;
		    font-weight: normal;
		    font-stretch: normal;
		    font-style: normal;
		    line-height: normal;
		    letter-spacing: normal;
		    padding-left: 8px;
		}

		/* Стили для кастомного окна, которое появляется по клику */
		.actual-path-marker .route-popup__extend {
		    display: none;
		    position: absolute;
		    top: 100%;
		    left: 0; /* Позиционируем относительно всей плашки */
		    margin-top: 8px;
		    padding: 5px;
		    width: max-content;
		    min-width: 200px;
		    background: #fff;
		    border-radius: 7px;
		    box-shadow: 0 1px 5px rgba(0,0,0,0.4);
		    z-index: 20;
		}

		/* Показываем кастомное окно, когда у родителя есть класс .active */
		.actual-path-marker.active .route-popup__extend {
		    display: block;
		}

		/* Дополнительные стили для маркеров остановок и обслуживания */
		.stop-marker .marker-main-view {
		    border-width: 2px; /* Делаем рамку потолще */
		}

		.stop-marker .icon-background {
		    background-color: #FFFFFF !important; /* Фон всегда белый */
		    border: solid 2px #bf0000;
		}

		.stop-marker svg path {
		    fill: #000000; /* Цвет иконки внутри - черный */
		}

		.stop-marker .marker-time-label {
		    /* Можно добавить специфичные стили для текста, если нужно */
		}

		/* --- Стили для меню настроек --- */
		#map-settings-container {
		    position: absolute;
		    top: 10px;
		    right: 10px;
		    z-index: 1000;
		}
		#settings-button {
		    width: 30px;
		    height: 30px;
		    background-color: white;
		    border-radius: 4px;
		    display: flex;
		    justify-content: center;
		    align-items: center;
		    box-shadow: 0 1px 5px rgba(0,0,0,0.65);
		    cursor: pointer;
		}
		.settings-icon {
		    width: 20px;
		    height: 20px;
		    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.4,11.5c-0.2-0.8-0.6-1.6-1-2.3L19,8.5c0.2-0.2,0.2-0.5,0-0.7l-2.2-2.2c-0.2-0.2-0.5-0.2-0.7,0l-0.7,0.7c-0.7-0.4-1.5-0.8-2.3-1l-0.2-0.9C12.8,4.2,12.6,4,12.3,4h-3c-0.3,0-0.5,0.2-0.5,0.4l-0.2,0.9c-0.8,0.2-1.6,0.6-2.3,1l-0.7-0.7c-0.2-0.2-0.5-0.2-0.7,0L2.7,7.8c-0.2,0.2-0.2,0.5,0,0.7l0.7,0.7c-0.4,0.7-0.8,1.5-1,2.3L2,12.3c0,0.3,0.2,0.5,0.4,0.5h0.9c0.2,0.8,0.6,1.6,1,2.3l-0.7,0.7c-0.2,0.2-0.2,0.5,0,0.7l2.2,2.2c0.2,0.2,0.5,0.2,0.7,0l0.7-0.7c0.7,0.4,1.5,0.8,2.3,1l0.2,0.9c0,0.3,0.2,0.5,0.5,0.5h3c0.3,0,0.5-0.2,0.5-0.4l0.2-0.9c0.8-0.2,1.6-0.6,2.3-1l0.7,0.7c0.2,0.2,0.5,0.2,0.7,0l2.2-2.2c0.2-0.2,0.2-0.5,0-0.7l-0.7-0.7c0.4-0.7,0.8-1.5,1-2.3l0.9-0.2c0.3,0,0.5-0.2,0.4-0.5L19.4,11.5z M12,15.5c-1.9,0-3.5-1.6-3.5-3.5s1.6-3.5,3.5-3.5s3.5,1.6,3.5,3.5S13.9,15.5,12,15.5z"/></svg>');
		    background-size: contain;
		}
		.settings-panel {
		    display: none;
		    position: absolute;
		    top: 35px;
		    right: 0;
		    width: 250px;
		    background-color: white;
		    border-radius: 4px;
		    box-shadow: 0 1px 5px rgba(0,0,0,0.65);
		    overflow: hidden;
		}
		.menu-panel { display: none; }
		.menu-panel.active { display: block; }
		.menu-item {
		    padding: 10px 15px;
		    cursor: pointer;
		    border-bottom: 1px solid #eee;
		    display: flex;
		    justify-content: space-between;
		    align-items: center;
		}
		.menu-item:hover { background-color: #f4f4f4; }
		.menu-item:last-child { border-bottom: none; }
		.submenu-header {
		    padding: 10px 15px;
		    font-weight: bold;
		    background-color: #f8f8f8;
		    border-bottom: 1px solid #ddd;
		    display: flex;
		    align-items: center;
		}
		.back-button {
		    font-size: 20px;
		    font-weight: bold;
		    cursor: pointer;
		    margin-right: 10px;
		}
		.menu-item label {
		    display: block;
		    width: 100%;
		    cursor: pointer;
		}

		/* Этот класс-обертка, который создает Leaflet. 
   Делаем его невидимым, так как весь стиль будет у нашего внутреннего элемента. */
		.planned-marker-container {
		    background: none;
		    border: none;
		}

		/* Наш основной элемент маркера (.route-popup)
		   Мы смещаем его так, чтобы якорь был в нужном месте. */
		.planned-marker-container .route-popup {
		    position: absolute;
		    
		    /* * Волшебство происходит здесь:
		     * transform: translate(X, Y);
		     * X: Смещаем влево на 15px (половина ширины цветного кружка-счетчика).
		     * Y: Смещаем вверх на 30px (полная высота плашки).
		     * Теперь центр кружка будет точно на географической координате.
		    */
		    transform: translate(-15px, -30px);
		}
		.leaflet-right {
			display: none!important;
		}
    </style>
</body>
</html>