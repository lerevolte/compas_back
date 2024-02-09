ymaps.ready(init);
var adress_needed = [55.74891146638803,37.54294049999999];

function init() {
    var myMap = new ymaps.Map('map', {
        center: [55.74891146638803,37.54294049999999],
        zoom: 15,
        controls: []
    });


    ymaps.geocode(adress_needed, {
        results: 1
    }).then(function (res) {
        myMap.controls.add('zoomControl', { left: 5, top: 5 });
        var firstGeoObject = res.geoObjects.get(0),
            coords = firstGeoObject.geometry.getCoordinates(),
            bounds = firstGeoObject.properties.get('boundedBy');

        myMap.setBounds(bounds, {
            checkZoomRange: true
        });

        var myPlacemark = new ymaps.Placemark(coords, {
            balloonContent: 'Мы здесь, друг!',
            hintContent: ''
        }, {
            iconLayout: 'default#image',
            iconImageHref: 'images/marker-1.svg',
            iconImageSize: [98, 95],
            iconImageOffset: [0, -50]
        });           

        

        myMap.geoObjects.add(myPlacemark);

        //myPlacemark.balloon.open();
    });


}