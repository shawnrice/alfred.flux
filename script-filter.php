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
 *    Dark Room             900 --- Note: the transition takes for-damn-ever.
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
 * Standard monitors output at
 *
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

$w = new Workflows;

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

// cat << EOB
//
// <?xml version="1.0"\?\>
// <items>
//   <item uid="flux.switchto.candle" arg="2300">
//     <title>Set to candlelight</title>
//     <icon>icon.png</icon>
//   </item>
//   <item uid="flux.switchto.tungsten" arg="2700">
//     <title>Set to tungsten</title>
//     <icon>icon.png</icon>
//   </item>
//   <item uid="flux.switchto.halogen" arg="3400">
//     <title>Set to halogen</title>
//     <icon>icon.png</icon>
//   </item>
//   <item uid="flux.switchto.fluorescent" arg="4100">
//     <title>Set to fluorescent</title>
//     <icon>icon.png</icon>
//   </item>
//   <item uid="flux.switchto.daylight" arg="6500">
//     <title>Set to daylight</title>
//     <icon>icon.png</icon>
//   </item>
// </items>
//
// EOB

$presets = array(
  'Dark Room'         =>    900,
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

ksort( $presets, SORT_NUMERIC );

$prefs = array(
  'Late Color' => 'lateColorTemp',
  'Night Color' => 'nightColorTemp',
  'Day Color' => 'dayColorTemp',
  'Wake Time' => 'wakeTime',
  'Location' => 'location'
);



// Set Timezones and Lat/Lon if not set.
// This pulls Lat/Lon from F.lux preferences, not from location services.
preemptDateErrors();

// Parse {query} if there is one.
if ( isset( $argv[1] ) ) {
  $q = $argv[1];
  $args = explode( ' ', $q );
}


$dayColorTemp = getPref( 'dayColorTemp', $pref );
if ( ! $dayColorTemp ) {
  exec( 'defaults write dayColorTemp -integer 6500' );
  $dayColorTemp = 6500;
}
$nightColorTemp = getPref( 'nightColorTemp', $pref );

if ( isDay() )
  $currentTemp = $dayColorTemp;
else
  $currentTemp = $nightColorTemp;

$invert = "osascript -e 'tell application \"System Events\"' -e 'tell application processes' -e 'key code 28 using {command down, option down, control down}' -e 'end tell' -e 'end tell'";

// Start Parsing Arguments
if ( ! isset( $q ) || $q == '' ) {
  // There are no arguments.
  $time = ucwords( getFluxTime() );
  $w->result( '',        '',         "Current Color Temp: $currentTemp" . "K ($time)", '',                                                    '', 'no', '' );
  $w->result( 'color',   'color',    'Set Current Color Temp',                         'Either as number or preset.',                         '', 'no', 'color');
  $w->result( 'set',     'set',      'Set Preference',                                 '',                                                    '', 'no', 'set');
  $w->result( 'disable', 'disable',  'Disable for an hour',                            '',                                                    '', 'yes', 'disable');
  $w->result( 'dark',    'darkroom', 'Enter Dark Room Mode',                           'You need to have the correct Accessibilty Settings.', '', 'yes', 'mood');
  $w->result( 'mood',    'mood',     'Enter Mood Lighting Mode',                       'Don\'t use this when actively using you computer.',   '', 'yes', 'mood');

  echo $w->toxml();

  // There is no argument, so let's just end here.
  die();
}

// $w->result( 'disable', 'disable', "\$q = '$q'\" & args[0]= \"" . $args[0] . "\" &args[1]=\"" . $args[1] . "\"", '', '', 'yes', 'disable');


// Set something
if ( strpos( $args[0] , 'se' ) !== FALSE ) {
  if ( ! isset( $args[1] ) ) {
    foreach ( $prefs as $k => $v ) {
      $value = getPref( $v, $pref );
      if ( $v == "location" ) {
        $value = str_replace( ',' , ', ', $value );
      }
      if ( $v == 'wakeTime' ) {
        $value = floor( $value / 60 ) . ":" . $value % 60;
        $w->result( '', $v, $k, "Current: $value (24 hour time)", '', 'yes', '');
      } else {
        $w->result( '', $v, $k, "Current: $value", '', 'yes', '');
      }

    }
  }
  // $key = $args[1];
  // $val = $args[2];

// Disable F.lux
} else if ( stripos( $args[0] , 'd' ) === 0 ) { // Disable
  $now = shell_exec( 'date +"%s"' );
  if ( count( $args ) > 1 ) {

    if ( ( count( $args ) >= 2 ) && ( ! is_numeric( $args[0] ) ) ) {
      unset( $args[0] );
      $time = implode( ' ', $args );
      $time = `./date.sh parseTime $time`;
      $readable = `./date.sh secondsToHumanTime $time`;
      $w->result( '', $time, "Disable F.lux for $readable.", '', '' , '', 'yes', 'disable');
    } else {
      $w->result( 'disable', 'disable', 'Disable for an hour',   '', '', 'yes', 'disable');
    }

    unset( $args[0] );
    $time = implode( ' ', $args );
    $time = `./date.sh parseTime "$time"`;

  } else {
    $val = 3600; // Sleep for one hour by default
    $w->result( 'disable', 'disable', 'Disable for an hour.', 'Sets temperature to Day mode. If you want it off, then set the color preference to Off.', '', 'yes', 'disable');
  }


} elseif ( stripos( $args[0] , 'c' ) !== FALSE ) {
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
        $w->result( '', 'disable', "Set current color temp to " . $temperature . "K",  $subtitle, '', 'yes', 'disable');
    } else {
      foreach ( $presets as $k => $v ) {
        if ( stripos( $k, $args[1] ) !== FALSE ) {
          $w->result( '', "$v", "Set current color temp to " . $k . ".", "Temperature: $v" . "K", '', 'yes', 'disable');
        }
      }
    }
  } else {
    foreach ( $presets as $preset => $temperature ) {
      if ( isDay() )
        $now = "Night";
      else
        $now = "Day";

      $w->result( '', "color-$preset", "Set $now color to $preset",
        "(Temperature: $temperature)", '', 'no', '' );
    }
  }
} elseif ( stripos( $args[0] , 're' ) !== FALSE ) {
  // Are these the correct defaults? I can't seem to find them.
  $defaults = "Day: 6500K, Night: 3400K, Late: 3400K.";
  $w->result( 'reset', 'reset', "Reset Temperatures to Defaults.",  $defaults, '', 'yes', 'disable');
} elseif ( stripos( $args[0] , 'm' ) !== FALSE ) {
  // Go to the "mood lighting"
  $w->result( 'mood', 'mood', "Enter into Mood Lighting Mode.",  "Note: don't actively use your computer with this option.", '', 'yes', 'disable');
} else {
  $w->result( '', 'set', 'Set Preference', '', '', 'no', 'set');
}
echo $w->toxml();
