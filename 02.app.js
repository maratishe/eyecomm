var socket, onmsg, $body, $eyeL, $eyeR, $pupilR, $pupilL; 
var tobii = { xmin: -40.92, xmax: 1289.40, ymin: -99.65, ymax: 2034.32}; 
function init() {
	var host = "ws://127.0.0.1:9000/echobot"; // SET THIS TO YOUR SERVER
	try {
		socket = new WebSocket( host);
		console.log( 'WebSocket - status ' + socket.readyState);
		socket.onopen = function( msg) { console.log( "Welcome - status " + this.readyState);  }
		socket.onmessage = function( msg) {  console.log( "Received: " + msg.data); if ( onmsg) onmsg( msg.data);  }
		socket.onclose = function( msg) {  console.log( "Disconnected - status " + this.readyState);  }
	}
	catch( ex) { console.log( ex); }
	//$( "msg").focus();
}
function quit() { if (socket != null) { console.log( "Goodbye!"); socket.close(); socket=null; } }
function reconnect() { quit(); init(); }
function rx() { $( 'title').first().empty().append( 'websockets'); onmsg = function( msg) { 
	var h = $.tth( msg); // x, y
	var X = Math.round( 100 * ( ( h.x - tobii.xmin) / ( tobii.xmax - tobii.xmin))); 
	var Y = Math.round( 100 * ( ( h.y - tobii.ymin) / ( tobii.ymax - tobii.ymin))); 
	var W = $eyeR.width(); var H = $eyeR.height(); var w = $pupilR.width(); h = $pupilR.height();
	var x = Math.round( X - ( 100 * 0.5 * w) / W); var y = Math.round( Y - ( 100 * 0.5 * h) / H); 
	$pupilR.css({ left: x + '%', top: y + '%'}); 
	$pupilL.css({ left: x + '%', top: y + '%'}); 
}};
function start() { $body.stopTime().oneTime( '3s', function() { 
	$body.css({ margin: '0px', padding: '0px', width: '100%', height: '100%', 'background-color': '#000'});
	$eyeL = $body.ioover().css( $.tth( 'top=20%,left=10%,width=30%,height=30%,background-color=#fff,border=3px solid #666'));
	$eyeR = $body.ioover().css( $.tth( 'top=20%,left=60%,width=30%,height=30%,background-color=#fff,border=3px solid #666'));
	$pupilL = $eyeL.ioover( $.tth( 'display=block,position=absolute,top=40%,left=45%,width=30%,height=auto'), 'img', { src: 'pupil.png'});
	$pupilR = $eyeR.ioover( $.tth( 'display=block,position=absolute,top=40%,left=45%,width=30%,height=auto'), 'img', { src: 'pupil.png'});
	try { socket.send( "first");  console.log( 'Sent "first"');  } catch( ex) {  console.log( ex);  }
})};
$( document).ready( function() { init(); rx(); $body = $( 'body').empty(); start(); })