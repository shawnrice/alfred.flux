<?php

// Workflow paths
$home = exec( 'echo $HOME' );
$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.spr.f.lux";

// F.lux Preferences file
$pref = "$home/Library/Preferences/org.herf.Flux.plist";

// Make the data directory if it doesn't exist.
if ( ! file_exists( $data ) ) {
  mkdir( $data );
}

require_once( 'control-functions.php' );

if ( $argv[1] == 'sunrise' ) {
  // Find sunset time.
  $sunset = getSunset();

  // Let's get the local time.
  $time = localtime( time() );
  $time = $time[1] + ( $time[2] * 60 );

  if ( $time > $sunset ) {
    // get sunrise for tomorrow and disable until then.
    $sunrise  = getSunrise( strtotime( 'tomorrow' ) );
    $sunrise += 1440 - $time;
  } else {
    // get sunrise for today and disable until then.
    $sunrise = getSunrise();
  }

  $disable = time() + ( $sunrise * 60 );
} else if ( is_numeric( $argv[1] ) ) {
  $disable = time() + $argv[1];
} else {
  echo "Son of a Holy Mother. We got a bad argument.";
  die();
}

// Record the time that we need to sleep until.
file_put_contents( "$data/disable", $disable);

// Save the current state of F.lux
exec( "./action.sh saveColors");

// Set all the colors to be the same -- the normal 6500K
exec( 'defaults write $HOME/Library/Preferences/org.herf.Flux.plist nightColorTemp -integer 6500' );
exec( 'defaults write $HOME/Library/Preferences/org.herf.Flux.plist dayColorTemp -integer 6500' );
exec( 'defaults write $HOME/Library/Preferences/org.herf.Flux.plist lateColorTemp -integer 6500' );

// Wait until the file is gone
while ( file_exists( "$data/disable" ) ) {

  // If we're done sleeping, then remove the file, and we'll wake up.
  if ( file_get_contents( "$data/disable" ) < time() )
    unlink( "$data/disable" );

  sleep( 5 );

}

// Restore Colors.
exec( "./action.sh restore" );
