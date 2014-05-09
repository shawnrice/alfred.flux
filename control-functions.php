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

function alterTime( $time ) {
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
    $sunset = alterTime( $sunset );
    $sunrise = alterTime( $sunrise );
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
    $sunrise = alterTime( $sunrise );
  }

  return $sunrise;
}

function preemptDateErrors() {
  //// Make sure that timezones and lat/long are set
  if ( ! ini_get('date.timezone') ) {
    $tz = str_replace( '/Time Zone: /' , '' , exec( '/usr/sbin/systemsetup -gettimezone' ) );
    date_default_timezone_set( $tz );
  }

  $latlong = explode( ',', getPref( 'location', $pref ) );
  ini_set( 'date.default_latitude',  $latlong[0] );
  ini_set( 'date.default_longitude', $latlong[1] );

  return TRUE;
}
