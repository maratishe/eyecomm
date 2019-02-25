<?php
require_once( 'websockets.php');
class TobiiCapture { public $e; public $gap = 0.1; public $file = 'raw.tobii.bz64jsonl'; public $socket; public $last = null; public function line( $v) { 
	$L = ttl( $v, ';'); extract( lth( $L, ttl( 'x,y'))); $time = tsystem(); if ( ! $this->last) $this->last = tsystem(); if ( $time - $this->last < $this->gap) return; // wait for end of gap
	$out = foutopen( $this->file, 'a'); foutwrite( $out, compact( ttl( 'time,x,y'))); foutclose( $out); $this->last = $time; 
	if ( $this->socket) $this->socket->tobiisend( $x, $y);
	if ( $this->e) echoe( $this->e, "  $x  $y");  
}}
class TobiiCalibrate { public $e; public $gap = 0.1; public $last = null; var $stats = null; public function line( $v) { 
	$L = ttl( $v, ';'); extract( lth( $L, ttl( 'x,y'))); $time = tsystem(); if ( ! $this->last) $this->last = tsystem(); if ( $time - $this->last < $this->gap) return; // wait for end of gap
	$xmax = $ymax = $xmin = $ymin = null; if ( $this->stats) extract( $this->stats); // xmax, xmin, ymax, ymin
	if ( $xmax === null || $x > $xmax) $xmax = $x; 
	if ( $xmin === null || $x < $xmin) $xmin = $x; 
	if ( $ymax === null || $y > $ymax) $ymax = $y; 
	if ( $ymin === null || $y < $ymin) $ymin = $y; 
	$this->stats = compact( ttl( 'xmin,ymin,xmax,ymax'));
	if ( $this->e) echoe( $this->e, " X: $xmin $xmax   Y: $ymin $ymax");  
}}
class echoServer extends WebSocketServer {
	public $oneuser;
	function __construct( $addr, $port, $bufferLength = 2048) { parent::__construct( $addr, $port, $bufferLength); $this->userClass = 'MyUser'; }
	//protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.
	protected function process( $user, $message) {  $this->oneuser = $user;  echo "received msg[$message]\n"; if ( $message == 'first') return $this->tobii();}
	protected function connected( $user) { } // do nothing for now
	protected function closed( $user) { } // do nothing for now
	// tobii specific functions
	public function tobii( $name = 'test', $gap = 0.1, $cleanup = false) {  // gap (seconds)
		if ( $cleanup) `rm -Rf $name*`; $HERE = getcwd();
		$c = "pkill GazeTrackEyeXGazeStream"; echo "$c\n"; @system( $c); 
		//if ( count( procpids( 'php')) > 1) { echo "Too many running PHPs, 'pkill php' -- will die myself, so run again.\n"; system( "pkill php"); die(); }
		$A = new TobiiCapture(); $A->e = echoeinit(); $A->file = "$name.tobii.bz64jsonl"; $A->socket = $this; $A->gap = $gap; 
		chdir( $HERE); $c = "GazeTrackEyeXGazeStream/GazeTrackEyeXGazeStream.exe"; procpipe( $c, true, true, $A); 	 
	}
	public function tobiisend( $x, $y) { $this->send( $this->oneuser, "x=$x,y=$y"); }
}
$CLASS = 'app'; class app { // USER code 
	public $silent = false;
	public function __construct( $silent = false) { $this->silent = $silent; }
	public function tobiicalibrate( $gap = 0.1) { 
		$c = "pkill GazeTrackEyeXGazeStream"; echo "$c\n"; @system( $c); 
		//if ( count( procpids( 'php')) > 1) { echo "Too many running PHPs, 'pkill php' -- will die myself, so run again.\n"; system( "pkill php"); die(); }
		$A = new TobiiCalibrate(); $A->e = echoeinit(); $A->gap = $gap; 
		$c = "GazeTrackEyeXGazeStream/GazeTrackEyeXGazeStream.exe"; procpipe( $c, true, true, $A); 	
	}
	public function run() { $echo = new echoServer( "0.0.0.0", "9000"); try { $echo->run(); } catch ( Exception $e) { $echo->stdout( $e->getMessage()); } }
}
if ( isset( $argv) && count( $argv) && strpos( $argv[ 0], "$CLASS.php") !== false) { // direct CLI execution, redirect to one of the functions 
	// this is a standalone script, put the header
	set_time_limit( 0);
	ob_implicit_flush( 1);
	//ini_set( 'memory_limit', '4000M');
	for ( $prefix = is_dir( 'ajaxkit') ? 'ajaxkit/' : ''; ! is_dir( $prefix) && count( explode( '/', $prefix)) < 4; $prefix .= '../'); if ( ! is_file( $prefix . "env.php")) $prefix = '/web/ajaxkit/'; 
	if ( ! is_file( $prefix . "env.php") && ! is_file( 'requireme.php')) die( "\nERROR! Cannot find env.php in [$prefix] or requireme.php in [.], check your environment! (maybe you need to go to ajaxkit first?)\n\n");
	if ( is_file( 'requireme.php')) require_once( 'requireme.php'); else foreach ( explode( ',', ".,$prefix") as $p) foreach ( array( 'functions', 'env') as $k) if ( is_dir( $p) && is_file( "$p/$k.php")) require_once( "$p/$k.php");
	chdir( clgetdir()); clparse(); $JSONENCODER = 'jsonencode'; // jsonraw | jsonencode    -- jump to lib dir
	// help
	clhelp( "FORMAT: php$CLASS WDIR COMMAND param1 param2 param3...     ($CLNAME)");
	foreach ( file( $CLNAME) as $line) if ( ( strpos( trim( $line), '// SECTION:') === 0 || strpos( trim( $line), 'public function') === 0) && strpos( $line, '__construct') === false) clhelp( lshift( ttl( trim( str_replace( 'public function', '', $line)), '{'))); // }
	// parse command line
	lshift( $argv); if ( ! count( $argv)) die( clshowhelp()); 
	//$wdir = lshift( $argv); if ( ! is_dir( $wdir)) { echo "ERROR! wdir#$wdir is not a directory\n\n"; clshowhelp(); die( ''); }
	//echo "wdir#$wdir\n"; if ( ! count( $argv)) { echo "ERROR! no action after wdir!\n\n"; clshowhelp(); die( ''); }
	$f = lshift( $argv); $C = new $CLASS(); chdir( $CWD); 
	switch ( count( $argv)) { case 0: $C->$f(); break; case 1: $C->$f( $argv[ 0]); break; case 2: $C->$f( $argv[ 0], $argv[ 1]); break; case 3: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2]); break; case 4: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3]); break; case 5: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4]); break; case 6: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4], $argv[ 5]); break; }
 	//switch ( count( $argv)) { case 0: $C->$f( $wdir); break; case 1: $C->$f( $wdir, $argv[ 0]); break; case 2: $C->$f( $wdir, $argv[ 0], $argv[ 1]); break; case 3: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2]); break; case 4: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3]); break; case 5: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4]); break; case 6: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4], $argv[ 5]); break; }
 	die();
}
if ( ! isset( $argv) && ( isset( $_GET) || isset( $_POST)) && ( $_GET || $_POST)) { // web API 
	set_time_limit( 0);
	ob_implicit_flush( 1);
	for ( $prefix = is_dir( 'ajaxkit') ? 'ajaxkit/' : ''; ! is_dir( $prefix) && count( explode( '/', $prefix)) < 4; $prefix .= '../'); if ( ! is_file( $prefix . "env.php")) $prefix = '/web/ajaxkit/'; 
	if ( ! is_file( $prefix . "env.php") && ! is_file( 'requireme.php')) die( "\nERROR! Cannot find env.php in [$prefix] or requireme.php in [.], check your environment! (maybe you need to go to ajaxkit first?)\n\n");
	if ( is_file( 'requireme.php')) require_once( 'requireme.php'); else foreach ( explode( ',', ".,$prefix") as $p) foreach ( array( 'functions', 'env') as $k) if ( is_dir( $p) && is_file( "$p/$k.php")) require_once( "$p/$k.php");
	htg( hm( $_GET, $_POST)); $JSONENCODER = 'jsonencode';
	// check for webkey.json and webkey parameter in request
	if ( is_file( 'webkeys.php') && ! isset( $webkey)) die( jsonsend( jsonerr( 'webkey env not set, run [phpwebkey make] first'))); 
	$good = true; if ( is_file( 'webkeys.php')) $good = false; 
	if ( is_file( 'webkeys.php')) foreach ( file( 'webkeys.php') as $v) if ( strpos( $v, $webkey) !== false) $good = true; 
	if ( ! $good) die( jsonsend( jsonerr( 'did not pass the authenticated form of this web API'))); 
	// actions: [wdir] is fixed/predefined  [action] is function name   others are [one,two,three,...]
	$O = new $CLASS( true); // does not pass [types], expects the user to run init() once locally before using it remotely 
	$p = array(); foreach ( ttl( 'one,two,three,four,five') as $k) if ( isset( $$k)) lpush( $p, $$k); $R = array();
	if ( count( $p) == 0) $R = $O->$action();
	if ( count( $p) == 1) $R = $O->$action( $one);
	if ( count( $p) == 2) $R = $O->$action( $one, $two);
	if ( count( $p) == 3) $R = $O->$action( $one, $two, $three);
	if ( count( $p) == 4) $R = $O->$action( $one, $two, $three, $four);
	if ( count( $p) == 5) $R = $O->$action( $one, $two, $three, $four, $five);
	die( jsonsend( $R));
}
if ( isset( $argv) && count( $argv)) { $L = explode( '/', $argv[ 0]); array_pop( $L); if ( count( $L)) chdir( implode( '/', $L)); } // WARNING! Some external callers may not like you jumping to current directory
// for raw input like JSON POST requests
//if ( ( ! isset( $_GET) && ! isset( $_POST)) || ( ! $_GET && ! $_POST)) { $h = @json_decode( @file_get_contents( 'php://input'), true); if ( $h) $_POST = $h; $out = fopen( 'input', 'w'); fwrite( $out, json_encode( $h)); fclose( $out); } 
?>
