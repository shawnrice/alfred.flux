<?php

/**
 * Explanation
 * ===========
 *   This workflow does two things:
 *     (1) Control F.lux, and
 *     (2) Hack the hell out of F.lux.
 *  (1) It can control F.lux's settings by rewriting the preferences file that
 *       F.lux uses; however, only a bit of the information is stored there.
 *           --- Note: more might be stored in its sqlite cache databases, but
 *           --- I need to query those more manually to figure that out.
 *       That means we can set the main preferences that we need:
 *         (1) Day Color Temp
 *         (2) Night Color Temp
 *
 *         Past that, we also have access to:
 *         (1) Location (Latitude and Longitude);
 *         (2) An Extra Hour of Sleep;
 *         (3) Sleep in on Weekends/
 *         (4) Steptime (I think this has something to do with the transition speed,
 *             but my testing hasn't shown me exactly how);
 *         (5) Wake Time; and
 *         (6) Fast Fade at Sunset.
 *
 *         There are a few more, but they are mostly irrelevant for controlling F.lux.
 *
 *         This workflow lets you set those preferences without opening F.lux's prefs.
 *
 *   (2) In order to replicate F.lux's
 *           (1) Disable for an hour; and
 *           (2) Disable until sunset
 *       features, we have to first tell F.lux that night is day and day is night,
 *       and then setup a little script to run for the specified "break" and then
 *       stop lying to F.lux. Since this is a hack, enabling F.lux from the menubar
 *       will NOT do anything.
 *
 *       The reason why we need to implement these hacks is simply that F.lux does
 *       not let you control anything about it from the command line, so those
 *       fancy new features are not available to use without a bit of hacking.
 *           --- Not ever going to work:
 *                 (1) Movie Mode
 *                 (2) Disable for this App
 *
 *  Presets
 *  =======
 *  These are custom presets that extend the native ones.
 *  --- Note: All numbers are in Kelvin.
 *  --- [Color Temperature](https://en.wikipedia.org/wiki/Color_temperature)
 *    Dark Room            1000 --- Note: the transition takes for-damn-ever.
 *    Ember                1200
 *    Candle               1900
 *    Warm Incandescent    2300
 *    Incandescent         2700 --- Note: The transition below 3000 is slow.
 *    Halogen              3400
 *    Fluorescent          4200
 *    Daylight             5500
 *    Off                  6500
 *    Blue Period         27000
 *
 *    !!! Darkroom also inverts the screen colors, only if you have enabled it via
 *    accessibilty.
 *
 *    Presets from the [F.lux FAQ page](https://justgetflux.com/faq.html):
 *    For OSX
 *      Candle             2300
 *      Tungsten           2700
 *      Halogen            3400
 *      Fluorescent        4200
 *      Daylight           5000
 *
 *    For Windows
 *      Ember              1200
 *      Candle             1900
 *      Warm Incandescent  2300
 *      Incandescent       2700
 *      Halogen            3400
 *      Fluorescent        4200
 *      Daylight           5500
 *
 * Notes
 * =====
 *
 * Features not supported:
 *    1. Movie Mode
 *       -- Cannot because I don't know F.lux's settings.
 * Standard displays have their whitepoint at 6500K.
 *
 * Disable for an... is partially supported.
 * Darkroom is partially supported.
 *
 **/

// Workflow paths
$home = exec( 'echo $HOME' );
$data = "$home/Library/Application Support/Alfred 2/Workflow Data/com.spr.f.lux";

// F.lux Preferences file
$pref = "$home/Library/Preferences/org.herf.Flux.plist";

// Make the data directory if it doesn't exist.
if ( ! file_exists( $data ) ) {
  mkdir( $data );
}

require_once( 'workflows.php' );
require_once( 'control-functions.php' );

// Set Timezones and Lat/Lon if not set.
// This pulls Lat/Lon from F.lux preferences, not from location services.
preemptDateErrors();
$tz = str_replace( '/Time Zone: /' , '' , exec( '/usr/sbin/systemsetup -gettimezone' ) );
ini_set( 'date.default_timezone', $tz );
$w = new Workflows;

if ( file_exists( "$data/darkroom" ) ) {
  $w->result( 'restore', 'restore', 'Turn off darkroom mode.', 'The color transition back to normal will take a bit.', '', 'yes', 'restore' );
  echo $w->toxml();
  die();
}

if ( file_exists( "$data/mood" ) ) {
  $w->result( 'restore', 'restore', 'Turn off mood lighting mode.', 'The color transition back to normal will take a bit.', '', 'yes', 'restore' );
  echo $w->toxml();
  die();
}

if ( file_exists( "$data/disable" ) ) {
  $w->result( 'restore', 'restore', 'Re-enable Flux.', 'Please wait for the color transition.', '', 'yes', 'restore' );
  echo $w->toxml();
  die();
}

// Check to see if f.lux is running.
if ( `ps aux | grep Contents/MacOS/Flux | grep -v grep` == '' ) {
  // It's not running, so show no options.
  $w->result( 'start', 'start', 'F.lux is not active.', 'Open F.lux.', '', 'yes', 'start');
  echo $w->toxml();

  // We're all done here, ladies and gents.
  die();
}

// Define the Presets
$presets = array(
  'Dark Room'         =>   1000,
  'Ember'             =>   1200,
  'Candle'            =>   1900,
  'Warm Incandescent' =>   2300,
  'Incandescent'      =>   2700,
  'Halogen'           =>   3400,
  'Fluorescent'       =>   4200,
  'Daylight'          =>   5500,
  'Off'               =>   6500,
  'Blue Period'       =>  27000
);

// These are appearing weird in Alfred. We'll just sort them to make them
// slightly less weird.
ksort( $presets, SORT_NUMERIC );

// Preferences available to set.
$prefs = array(
  'Late Color' => 'lateColorTemp',
  'Night Color' => 'nightColorTemp',
  'Day Color' => 'dayColorTemp',
  'Wake Time' => 'wakeTime',
  // 'Location' => 'location'
);



// Parse {query} if there is one.
if ( isset( $argv[1] ) ) {
  $q = $argv[1];
  $args = explode( ' ', $q );
}

// Get variables if defined, and set them if they are.
$dayColorTemp = getPref( 'dayColorTemp', $pref );
if ( ! $dayColorTemp ) {
  exec( "defaults write $pref dayColorTemp -integer 6500" );
  $dayColorTemp = 6500;
}
$nightColorTemp = getPref( 'nightColorTemp', $pref );
$lateColorTemp = getPref( 'lateColorTemp', $pref );
$state = getFluxTime();
switch( $state ) :
  case 'day':
    $currentTemp = $dayColorTemp;
    break;
  case 'sunset':
    $currentTemp = $nightColorTemp;
    break;
  case 'late':
    $currentTemp = $lateColorTemp;
    break;
  default:
    $borked = TRUE;
    break;
endswitch;

$invert = "osascript -e 'tell application \"System Events\"' -e 'tell application processes' -e 'key code 28 using {command down, option down, control down}' -e 'end tell' -e 'end tell'";

// Start Parsing Arguments
if ( ! isset( $q ) || $q == '' ) {
  // There are no arguments.
  $time = ucwords( getFluxTime() );
  $w->result( '',        '',         "Current Color Temp: $currentTemp" . "K ($time)", '',                                                    '', 'no', '' );
  $w->result( 'color',   'color',    'Set Current Color Temp',                         'Either as number or preset.',                         '', 'no', 'color');
  $w->result( 'set',     'set',      'Set Preference',                                 '',                                                    '', 'no', 'set');
  $w->result( 'disable', 'disable',  'Disable for an hour',                            '',                                                    '', 'yes', 'disable');
  $w->result( 'darkroom','darkroom', 'Enter Dark Room Mode',                           'You need to have the correct Accessibilty Settings.', '', 'yes', 'darkroom');
  $w->result( 'mood',    'mood',     'Enter Mood Lighting Mode',                       'Don\'t use this when actively using you computer.',   '', 'yes', 'mood');

  echo $w->toxml();
  // There is no argument, so let's just end here.
  die();
}

// Set something
if ( strpos( $args[0] , 's' ) === 0 ) {
  if ( ! isset( $args[1] ) || $args[1] == '' ) {
    foreach ( $prefs as $k => $v ) {
      $value = getPref( $v, $pref );
      if ( $v == "location" ) {
        $value = str_replace( ',' , ', ', $value );
      }
      if ( $v == 'wakeTime' ) {
        $value = floor( $value / 60 ) . ":" . $value % 60;
        $w->result( "set-$v", $v, $k, "Current: $value (24 hour time)", '', 'no', "set $v");
      } else {
        $w->result( "set-$v", $v, $k, "Current: $value", '', 'no', "set $v");
      }

    }
  } else if ( ( ! isset( $args[2] ) ) || ( $args[2] == '' ) ) {
    $value = getPref( $args[1], $pref );
    $v = $args[1];
    if ( in_array( $v, $prefs ) )
      $w->result( "set-$v", "$v", "Set $v", "Current: $value", '', 'no', "set $v" );
    else
      $w->result( "", "", "There is no preference by that name.", "", '', 'no', "" );
  } else {
    $value = getPref( $args[1], $pref );
    $v = $args[1];
    $new = $args[2];
    if ( in_array( $v, $prefs ) ) {
      $numeric = array('lateColorTemp', 'dayColorTemp', 'nightColorTemp');
      if ( in_array( $v, $numeric ) ) {
        if ( $new > 27000 || $new < 1000 )
          $w->result( "set-$v", "set-$v-1000", "Set $v to 1000", "Value must be between 1000 and 27000.", '', 'yes', '' );
        else
          $w->result( "set-$v", "set-$v-$new", "Set $v to $new", "Current: $value", '', 'yes', '' );
      } else {
        $w->result( "set-$v", "set-$v-$new", "Set $v to $new", "Value must be in 24hr time (hh:mm).", '', 'yes', '' );
      }
    } else {
      $w->result( "", "", "There is no preference by that name.", "", '', 'no', "" );
    }
  }

// Disable F.lux
} else if ( stripos( $args[0] , 'di' ) === 0 ) { // Disable
  $now = shell_exec( 'date +"%s"' );
  if ( count( $args ) > 1 ) {

    if ( ( count( $args ) >= 2 ) && ( ! is_numeric( $args[0] ) ) ) {
      unset( $args[0] );
      $time = implode( ' ', $args );
      $time = `./date.sh parseTime $time`;
      $readable = `./date.sh secondsToHumanTime $time`;
      $w->result( '', "disable-$time", "Disable F.lux for $readable.", 'This sets night to day, temporarily.', '' , '', 'yes', 'disable');
    }

    unset( $args[0] );
    $time = implode( ' ', $args );
    $time = `./date.sh parseTime "$time"`;

  } else {
    // Sleep for one hour by default
    $w->result( 'disable-3600', 'disable', 'Disable for an hour.', 'Sets temperature to Day mode. If you want it off, then set the color preference to Off.', '', 'yes', 'disable');
  }

// Set the Color temperature for the current state
} elseif ( stripos( $args[0] , 'c' ) === 0 ) {
  $state = getFluxTime();
  if ( count( $args ) >= 2 && $args[1] != '' ) {
    if ( is_numeric( $args[1] ) ) {
      $temperature = $args[1];
      if ( $args[1] < 1000 || $args[1] > 27000 ) {
        $subtitle = "The color temperature must be between 1000K and 27000K";
        if ( $temperature < 1000 )
          $temperature = 1000;
        else
          $temperature = 27000;
      } else
        $subtitle = "Set color temperature for \"" . ucwords( getFluxTime() ) . ".\"";
        $w->result( "color-$state-$temperature", "color-$state-$temperature", "Set current color temp to " . $temperature . "K",  $subtitle, '', 'yes', $temperature);
    } else {
      foreach ( $presets as $k => $v ) {
        if ( stripos( $k, $args[1] ) !== FALSE ) {
          $w->result( "color-$state-$k", "color-$state-$v", "Set current color temp to " . $k . ".", "Temperature: $v" . "K", '', 'yes', "$k");
        }
      }
    }
  } else {
    foreach ( $presets as $preset => $temperature ) {
      switch( getFluxTime() ) :
        case 'day':
          $now = 'Day';
          break;
        case 'sunset':
          $now = 'Sunset';
          break;
        case 'late':
          $now = 'Late';
          break;
        default:
          $borked = TRUE;
          break;
      endswitch;

      $w->result( 'color-$state-$temperature', "color-$state-$temperature", "Set $now color to $preset", "(Temperature: $temperature)", '', 'yes', "$preset" );
    }
  }

// Reset f.lux to default values.
} elseif ( stripos( $args[0] , 're' ) === 0 ) {
  // Are these the correct defaults? I can't seem to find them.
  $defaults = "Day: 6500K, Night: 3400K, Late: 3400K.";
  $w->result( 'reset', 'reset', "Reset Temperatures to Defaults.",  $defaults, '', 'yes', 'disable');

// Go to mood lighting.
} elseif ( stripos( $args[0] , 'm' ) === 0 ) {
  $w->result( 'mood', 'mood', "Enter into Mood Lighting Mode.",  'Note: don\'t actively use your computer with this option.', '', 'yes', 'disable');

// Go to Dark Room.
} elseif ( stripos( $args[0] , 'da' ) === 0 ) {
  $w->result( 'darkroom', 'darkroom', "Enter into Dark Room Mode.",  'You need to have the correct Accessibilty Settings.', '', 'yes', 'darkroom');

// Whoops. Fallback.
} else {
  $w->result( '', 'set', 'Set Preference', '', '', 'no', 'set');
}
echo $w->toxml();
