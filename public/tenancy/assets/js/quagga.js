console.log('azaza')
var Quagga = window.Quagga;
var backCamID = null;
var last_camera = null;
var str = '';
navigator.mediaDevices.enumerateDevices()
.then(function(devices) {
	
  devices.forEach(function(device) {
  	str+=device.kind+' '+device.label+'<br>';
 if( device.kind == "videoinput" && device.label.match(/back/) !== null ){
      backCamID = device.deviceId;
    }
	if( device.kind == "videoinput"){
		last_camera = device.deviceId;
	} 

});
if( backCamID == null){
	backCamID = last_camera;
}
})
.catch(function(err) {         });
let redirect = false;
var App = {
    _lastResult: null,
    init: function() {
        this.attachListeners();
    },
    activateScanner: function() {
        var scanner = this.configureScanner('.overlay__content'),
            onDetected = function (result) {
                if(redirect)
                    return false;
                //this.addToResults(result);
                $('#barcode').val(result.codeResult.code);
                
                var val = $('#barcode').val();
                $('.preloader').show();
                $.ajax({
                    type: 'post',
                    url: '/shipments/scan',
                    async: false,
                    data: {
                        '_token': $('input[name=_token]').val(),
                        'barcode': val,
                    },
                    success: function(data) {
                        console.log(data)
                        if(data.id) {
                            redirect = true;
                            location.href = '/shipments/'+data.id;
                        }
                        else if (data == 0) {
                            redirect = false;
                            $('.preloader').hide();
                        }
                        else {
                            $('.preloader').hide();
                            alert(data)
                            location.reload();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('.preloader').hide();
                        redirect = false;
                      //alert(xhr.responseText);
                    }
                });
            }.bind(this),
            stop = function() {
                scanner.stop();  // should also clear all event-listeners?
                scanner.removeEventListener('detected', onDetected);
                this.hideOverlay();
                this.attachListeners();
            }.bind(this);

        this.showOverlay(stop);
        console.log("activateScanner");
        scanner.addEventListener('detected', onDetected).start();
    },
    addToResults: function(result) {
        if (this._lastResult === result.codeResult.code) {
            return;
        }
        this._lastResult = result.codeResult.code;
        var resultSets = document.querySelectorAll('ul.results');

        Array.prototype.slice.call(resultSets).forEach(function(resultSet) {
            var li = document.createElement('li'),
                format = document.createElement('span'),
                code = document.createElement('span');

            console.log(result);
            li.className = "result";
            format.className = "format";
            code.className = "code";

            li.appendChild(format);
            li.appendChild(code);

            format.appendChild(document.createTextNode(result.codeResult.format));
            code.appendChild(document.createTextNode(result.codeResult.code));

            resultSet.insertBefore(li, resultSet.firstChild);
        });
    },
    attachListeners: function() {
        var button = document.querySelector('button.scan'),
            self = this;

        button.addEventListener("click", function clickListener (e) {
            e.preventDefault();
            button.removeEventListener("click", clickListener);
            self.activateScanner();
        });
    },
    showOverlay: function(cancelCb) {
        document.querySelector('.controls')
            .classList.add('hide');
        document.querySelector('.overlay--inline')
            .classList.add('show');
        var closeButton = document.querySelector('.overlay__close');
        closeButton.addEventListener('click', function closeHandler() {
            closeButton.removeEventListener("click", closeHandler);
            cancelCb();
        });
    },
    hideOverlay: function() {
        document.querySelector('.controls')
            .classList.remove('hide');
        document.querySelector('.overlay--inline')
            .classList.remove('show');
    },
    querySelectedReaders: function() {
        return Array.prototype.slice.call(document.querySelectorAll('.readers input[type=checkbox]'))
            .filter(function(element) {
                return !!element.checked;
            })
            .map(function(element) {
                return element.getAttribute("name");
            });
    },
    configureScanner: function(selector) {
    	if(backCamID !== null) {
	    	var scanner = Quagga
	            .decoder({readers: this.querySelectedReaders()})
	            .locator({patchSize: 'medium'})
	            .fromSource({
	                target: selector,
	                constraints: {
	                    width: 600,
	                    height: 600,
	                    deviceId: backCamID
	                }
	            });
    	}
    	else
	        var scanner = Quagga
	            .decoder({readers: this.querySelectedReaders()})
	            .locator({patchSize: 'medium'})
	            .fromSource({
	                target: selector,
	                constraints: {
	                    width: 600,
	                    height: 600,
	                    facingMode: "environment"
	                }
	            });
        return scanner;
    }
};
App.init();

var metas = document.getElementsByTagName('meta');
var i;
if (navigator.userAgent.match(/iPhone/i)) {
  for (i=0; i<metas.length; i++) {
    if (metas[i].name == "viewport") {
      metas[i].content = "width=device-width, minimum-scale=1.0, maximum-scale=1.0";
    }
  }
  document.addEventListener("gesturestart", gestureStart, false);
}
function gestureStart() {
  for (i=0; i<metas.length; i++) {
    if (metas[i].name == "viewport") {
      metas[i].content = "width=device-width, minimum-scale=0.25, maximum-scale=1.6";
    }
  }
}
var triggerElementID = null; // this variable is used to identity the triggering element
var fingerCount = 0;
var startX = 0;
var startY = 0;
var curX = 0;
var curY = 0;
var deltaX = 0;
var deltaY = 0;
var horzDiff = 0;
var vertDiff = 0;
var minLength = 72; // the shortest distance the user may swipe
var swipeLength = 0;
var swipeAngle = null;
var swipeDirection = null;

// The 4 Touch Event Handlers

// NOTE: the touchStart handler should also receive the ID of the triggering element
// make sure its ID is passed in the event call placed in the element declaration, like:
// <div id="picture-frame" ontouchstart="touchStart(event,'picture-frame');"  ontouchend="touchEnd(event);" ontouchmove="touchMove(event);" ontouchcancel="touchCancel(event);">

function touchStart(event,passedName) {
    // disable the standard ability to select the touched object
    event.preventDefault();
    // get the total number of fingers touching the screen
    fingerCount = event.touches.length;
    // since we're looking for a swipe (single finger) and not a gesture (multiple fingers),
    // check that only one finger was used
    if ( fingerCount == 1 ) {
        // get the coordinates of the touch
        startX = event.touches[0].pageX;
        startY = event.touches[0].pageY;
        // store the triggering element ID
        triggerElementID = passedName;
    } else {
        // more than one finger touched so cancel
        touchCancel(event);
    }
}

function touchMove(event) {
    event.preventDefault();
    if ( event.touches.length == 1 ) {
        curX = event.touches[0].pageX;
        curY = event.touches[0].pageY;
        $('.overlay__close').trigger('click')
    } else {
        touchCancel(event);
    }
}

function touchEnd(event) {
    event.preventDefault();
    // check to see if more than one finger was used and that there is an ending coordinate
    if ( fingerCount == 1 && curX != 0 ) {
        // use the Distance Formula to determine the length of the swipe
        swipeLength = Math.round(Math.sqrt(Math.pow(curX - startX,2) + Math.pow(curY - startY,2)));
        // if the user swiped more than the minimum length, perform the appropriate action
        if ( swipeLength >= minLength ) {
            caluculateAngle();
            determineSwipeDirection();
            processingRoutine();
            touchCancel(event); // reset the variables
        } else {
            touchCancel(event);
        }   

    } else {
        touchCancel(event);
    }
}

function touchCancel(event) {
    // reset the variables back to default values
    fingerCount = 0;
    startX = 0;
    startY = 0;
    curX = 0;
    curY = 0;
    deltaX = 0;
    deltaY = 0;
    horzDiff = 0;
    vertDiff = 0;
    swipeLength = 0;
    swipeAngle = null;
    swipeDirection = null;
    triggerElementID = null;
}

function caluculateAngle() {
    var X = startX-curX;
    var Y = curY-startY;
    var Z = Math.round(Math.sqrt(Math.pow(X,2)+Math.pow(Y,2))); //the distance - rounded - in pixels
    var r = Math.atan2(Y,X); //angle in radians (Cartesian system)
    swipeAngle = Math.round(r*180/Math.PI); //angle in degrees
    if ( swipeAngle < 0 ) { swipeAngle =  360 - Math.abs(swipeAngle); }
}

function determineSwipeDirection() {

    if ( (swipeAngle <= 45) && (swipeAngle >= 0) ) {
        swipeDirection = 'left';
    } else if ( (swipeAngle <= 360) && (swipeAngle >= 315) ) {
        swipeDirection = 'left';
    } else if ( (swipeAngle >= 135) && (swipeAngle <= 225) ) {
        swipeDirection = 'right';
    } else if ( (swipeAngle > 45) && (swipeAngle < 135) ) {
        swipeDirection = 'down';
    } else {
        swipeDirection = 'up';
    }
}

function processingRoutine() {
    var swipedElement = document.getElementById(triggerElementID);
    if ( swipeDirection == 'left' ) {
        sliders.goToNext();         
    } else if ( swipeDirection == 'right' ) {
        sliders.goToPrev();
    } else if ( swipeDirection == 'up' ) {
        sliders.goToPrev();         
    } else if ( swipeDirection == 'down' ) {
        sliders.goToNext();         
    }
}