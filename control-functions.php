<?php


// Control F.lux

function getPref( $key ) {
  global $pref;

  return exec( "defaults read $pref $key" );
}

function setPref( $key , $value , $interger = TRUE) {
  global $pref;

  $cmd = "defaults write $pref $key ";
  if ( $integer )
     $cmd .= "-integer ";
  $cmd .= "$value";

  exec( $cmd );

  return TRUE;
}

function setDay( $temperature ) {

  if ( $temperature = parseTemperature( $temperature ) )
    setPref( 'dayColorTemp', $temperature );
  else
    return FALSE;

  return TRUE;
}

function setNight( $temperature ) {
  if ( $temperature = parseTemperature( $temperature ) )
    setPref( 'dayNightTemp', $temperature );
  else
    return FALSE;

  return TRUE;
}

function setLate( $temperature ) {
  if ( $temperature = parseTemperature( $temperature ) )
    setPref( 'dayLateTemp', $temperature );
  else
    return FALSE;

  return TRUE;
}

function parseTemperature( $temperature ) {
  global $presets;

  // The temperature was called by a preset.
  // So convert it or send an error if not set.
  if ( ! _numeric( $temperature ) ) {
    if ( array_key_exists( $temperature, $presets ) )
      $temperature = $presets[ "$temperature" ];
    else
      return FALSE;
  }

  // I'm binding a range of 900K - 27000K. You really can't tell the difference
  // outside of this range.
  if ( $temperature < 900 )
    $temperature = 900;
  if ( $temperature > 27000 )
    $temperature = 27000;

  return $temperature;
}

// Time / Date functions

function dst( $time ) {
  $time = explode( ':', $time );
  if ( date( 'Z' ) ) {
    $time[0] += 1;
  }
  $time = ( $time[0] * 60 ) + ( $time[1] );
  return $time;
}

function isDay() {
  // Get the sunrise & sunset
  $now     = time();
  $sunset  = date_sunset( $now );
  $sunrise = date_sunrise( $now );

  // This checks for DST, I think that it shouldn't affect those who aren't in
  // timezones with DST, but, well, let's flag it as a potential problem.
  if ( date( 'Z' ) ) {
    $sunset = dst( $sunset );
    $sunrise = dst( $sunrise );
  }

  // Let's get the local time.
  $time = localtime( time() );
  $time = $time[1] + ( $time[2] * 60 );

  // Okay, now we need to know if it's daylight outside.
  if ( ( $sunrise < $time ) && ($time < $sunset ) )
    return TRUE;
  else
    return FALSE;
}

/**
 * [getSunrise description]
 */
function getSunrise() {
  // Get the sunrise & sunset
  $now     = time();
  $sunrise = date_sunrise( $now );

  // This checks for DST, I think that it shouldn't affect those who aren't in
  // timezones with DST, but, well, let's flag it as a potential problem.
  if ( date( 'Z' ) ) {
    $sunrise = dst( $sunrise );
  }

  return $sunrise;
}

function getSunset() {
  // Get the sunrise & sunset
  $now     = time();
  $sunset = date_sunset( $now );

  // This checks for DST, I think that it shouldn't affect those who aren't in
  // timezones with DST, but, well, let's flag it as a potential problem.
  if ( date( 'Z' ) ) {
    $sunset = dst( $sunset );
  }

  return $sunset;
}

function preemptDateErrors() {
  //// Make sure that timezones and lat/long are set
  if ( ! ini_get('date.timezone') ) {
    $tz = str_replace( '/Time Zone: /' , '' , exec( '/usr/sbin/systemsetup -gettimezone' ) );
    date_default_timezone_set( $tz );
  }

  $latlong = explode( ',', getPref( 'location' ) );
  ini_set( 'date.default_latitude',  $latlong[0] );
  ini_set( 'date.default_longitude', $latlong[1] );

  return TRUE;
}

function getFluxTime() {
  global $pref;

  $sleepLate = getPref( 'sleepLate' );
  $under18   = getPref( 'under18' );
  $wakeTime  = getPref( 'wakeTime' );
  $sunset    = getSunset();

  // Let's get the local time.
  $time = localtime( time() );
  $time = $time[1] + ( $time[2] * 60 );

  // Do you want to sleep in on a weekend? And, is it?
  if ( $sleepLate === 1 ) {
    $day = date( 'D', time() );
    if ( $day == 'Sun' || $day == 'Sat' ) {
      $wakeTime += 60;
    }
  }

  // Did you set 'extra hour for kids'?
  if ( $under18 === 1 )
    $wakeTime += 60;

  if ( ( $time > $wakeTime ) && ( $time < $sunset ) )
    return 'day';
  else if ( ( $time > $wakeTime ) && ( $time > $sunset ) && ( $time < ( $wakeTime - 9 ) ) )
    return 'sunset';
  else
    return 'late';

  // We shouldn't get here, so this is an error.
  return FALSE;

  // I need to figure out how it takes sunrise into account. I can't do that
  // until tomorrow when the sun rises... because I don't want to reset my
  // location data right now.

}
