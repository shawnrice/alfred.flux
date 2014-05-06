<?php
// cat << EOB
//
// <?xml version="1.0"\?\>
// <items>
//   <item uid="flux.disable.hour" autocomplete="hour" arg="3600">
//     <title>Disable for an hour</title>
// 	<icon>2BDE72FE-FE79-462B-9B22-A67A6426DFAB.png</icon>
//   </item>
//   <item uid="flux.disable.minute" autocomplete="minute" arg="60">
//     <title>Disable for a minute</title>
// 	<icon>2BDE72FE-FE79-462B-9B22-A67A6426DFAB.png</icon>
//   </item>
// </items>
//
// EOB
//

set_error_handler(function ($errno, $errstr){
    throw new Exception($errstr);
    return false;
});
try{
    date_default_timezone_get();
}
catch(Exception $e){
    date_default_timezone_set('UTC'); // Sets to UTC if not specified anywhere in .ini
}
restore_error_handler();


date_default_timezone_set('America/Los_Angeles');

date_default_timezone_set('America/New_York');
$date = getdate();
$date = date("D") . ", " . date("d F") . ", " . date("G:i");
echo $date;

$q = $argv[1];
$args = explode( ' ', $q );
$home = exec( 'echo $HOME' );
$pref = "$home/Library/Preferences/org.herf.Flux.plist";

echo
echo
echo getPref( 'lateColorTemp', $pref );
echo getPref( 'wakeTime', $pref );
echo getPref( 'version', $pref );
echo getPref( 'location', $pref );
echo getPref( 'locationTextField', $pref );

$day = getPref( 'dayColorTemp', $pref );
$night = getPref( 'nightColorTemp', $pref );

if ( strpos( $args[0] , 'set' ) ) {
  $key = $args[1];
  $val = $args[2];



} else if ( strpos( $args[0] , 'disa' ) !== FALSE ) { // Disable
  if ( count( $args ) > 1 ) {
    unset( $args[0] );
    $time = implode( ' ', $args );
    $time = `./date.sh parseTime "$time"`;
    echo $time;
  }
} else {
  print_r($args);
}


function getPref( $key, $pref ) {

  return exec( "defaults read $pref $key" );

}
