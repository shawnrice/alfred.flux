<?php

$home = exec( 'echo $HOME' );
$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.spr.f.lux";

$levels = array(  'dark'     => array( 'lower'  =>        1,
                                      'upper'   =>   200000
                  ),
                  'dim'      => array( 'lower'  =>     1000,
                                       'upper'  =>   400000
                  ),
                  'normal'   => array( 'lower'  =>   500000,
                                       'upper'  =>   850000
                  ),
                  'bright'   => array( 'lower'  =>   750000,
                                       'upper'  =>  1000000
                  ),
);

$speeds = array( 'calm'       => array( 'lower' =>   5000000,
                                        'upper' => 100000000
                 ),
                 'breeze'     => array( 'lower' =>   4000000,
                                        'upper' =>   7000000
                 ),
                 'windy'      => array( 'lower' =>   3000000,
                                        'upper' =>   6000000
                 ),
                 'hurricane'  => array( 'lower' =>         1,
                                        'upper' =>   2000000
                 )
);

if ( isset( $argv[1] ) && in_array( $argv[1], $levels ) )
  $level = $argv[1];
else
  $level = 'normal';

if ( isset( $argv[2] ) && in_array( $argv[2], $speeds ) )
  $speed = $argv[2];
else
  $speed = 'breeze';

if ( isset( $argv[3] ) ) {
  if ( is_numeric( $argv[3] ) )
    // So this means we'll go for a certain number of seconds.
    $duration = $argv[3];
  else if ( file_exists( $argv[3] ) ) {
    $duration = $argv[3];
  }
} else {
  $duration = 250;
}



$preset = array( $level , $speed );

// Open a nice blank canvas.
`open background.html`;
time_nanosleep( 0, 1000000 );
// Send the web browser into full screen
`osascript "open-canvas.scpt"`;
// `defaults write $HOME/Library/Preferences/org.herf.Flux.plist nightColorTemp -integer 2300`;

// Wait for the transition
time_nanosleep( 0, 1000000 );

// Flicker!
flicker( $levels[$preset[0]], $speeds[$preset[1]], $duration );

// Take it out of full screen.
`osascript "close-canvas.scpt"`;

function flicker( $levels = array( 'lower' => 0 , 'upper' => 1000000 ),
  $speed = array( 'lower' => 0, 'upper' => 1000000000 ) , $duration = 250 ) {

  $brightness = explode( "\n" , `./screenbrightness -l` );
  $brightness = str_replace( "display 0: brightness " , '' , $brightness[1] );

  $continue = TRUE;
  $start = mktime();

  while ( $continue == TRUE ) {

    if ( is_numeric( $duration ) ) {
      if ( ( mktime() - $start ) > $duration )
        $continue = FALSE;
    } else {
      if ( ! file_exists( $duration ) )
        $continue = FALSE;
    }

    if ( mt_rand( 0, 100 ) > 85 )
      $sleep = mt_rand( 5000, 10000 );
    else
      $sleep = mt_rand( $speed[ 'lower' ],  $speed[ 'upper' ]  );
    if ( mt_rand( 0, 100) > 90 ) {
      $lower = mt_rand( 1, 10000 );
      $upper = mt_rand( 1, 100000 );
      if ( $lower > $upper ) {
        $upper = $lower + mt_rand( 100, 10000 );
        $level = mt_rand( $lower, $upper );
      } else {
        $level = mt_rand( $lower, $upper );
      }
    }
    else {
      $level = mt_rand( $levels[ 'lower' ], $levels[ 'upper' ] ) / 1000000;
      if ( $level <= 0 ) {
        $level = $levels[ 'lower' ] + mt_rand( $levels['lower'], $levels[ 'upper'] );
      }
    }
    if ( $level > 1 )
      $level = 1;
    if ( $level == 0 ) {
      $level = .01;
    }

    `./screenbrightness $level`;

    time_nanosleep( 0 , $sleep );
  }

  // Set the screen brightness back to normal.
  `./screenbrightness $brightness`;
}
