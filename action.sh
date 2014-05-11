#!/bin/bash

q=$1
data="$HOME/Library/Application Support/Alfred 2/Workflow Data/com.spr.f.lux"

saveColors() {
  night=`defaults read $HOME/Library/Preferences/org.herf.Flux.plist nightColorTemp`
  day=`defaults read $HOME/Library/Preferences/org.herf.Flux.plist dayColorTemp`
  late=`defaults read $HOME/Library/Preferences/org.herf.Flux.plist lateColorTemp`

  # We're going to save the previous settings so that we can restore them later.
  echo "night=$night"  > "$data/reset"
  echo "day=$day"     >> "$data/reset"
  echo "late=$late"   >> "$data/reset"
}

setUniform() {
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist nightColorTemp -integer $1
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist dayColorTemp -integer $1
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist lateColorTemp -integer $1
}

restoreFlux() {
  while read line
  do
    arr=${line//=/ }
    for x in $arr
    do
      if [ -z $key ]; then
        key=$x
      else
        defaults write $HOME/Library/Preferences/org.herf.Flux.plist "$key"ColorTemp -integer "$x"
        key=''
      fi
    done
  done < "$data/reset"
}


# Arguments that need to be parsed.
if [[ $q =~ "color" ]]; then
  if [ -f "$data/mood" ]; then
    rm "$data/mood"
  fi
  if [ -f "$data/darkroom" ]; then
    rm "$data/darkroom"
  fi
  q=${q#color-}
  color=${q#*-}
  state=${q%-*}
  if [ "$state" = "sunset" ]; then
    state="night"
  fi
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist "$state"ColorTemp -integer "$color"
  echo "Color has been set to $color."
  exit 0
fi

# Disable
if [[ $q =~ "disable" ]]; then
  if [ -f "$data/mood" ]; then
    rm "$data/mood"
  fi
  if [ -f "$data/darkroom" ]; then
    rm "$data/darkroom"
  fi
  if [ $q = 'disable' ]; then
    nohup php disable.php 3600  > /dev/null 2>&1 &
    echo "F.lux has been disabled for one hour."
  elif [ $q = 'sunrise' ]; then
    nohup php disable.php sunrise  > /dev/null 2>&1 &
    echo "F.lux has been disabled until sunrise."
  else
    nohup php disable.php ${q##disable-} > /dev/null 2>&1 &
    readable=`./date.sh secondsToHumanTime ${q##disable-}`
    echo "F.lux has been disabled for $readable"
  fi
  exit 0
fi

if [[ $q =~ "set-" ]]; then
  q=${q#set-}
  key=${q%-*}
  value=${q#*-}
  if [ $key = "wakeTime" ]; then
    if [[ $value =~ : ]]; then
      hour=${value%":"*}
      minute=${value#*":"}
      value=$(( hour*60 + minute ))
    else
      echo "Error: 'wakeTime' must be set in HH:MM format."
      exit 0
    fi
  else
    if [[ $value < 1000 ]]; then
      value=1000
    elif [[ $value > 27000 ]]; then
      value=27000
    fi
  fi
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist "$key" -integer "$value"
  echo "Set $key."
  exit 0
fi

# Simple arguments... for now.

# Just open flux
if [[ $q = "open" ]]; then
  if [ -d "/Applications/Flux.app" ]; then
    open /Applications/Flux.app
  fi
  exit 0
fi

# Restore flux from mood/darkroom/disable. Might be overdoing it.
if [[ $q = "restore" ]]; then
  restoreFlux

  if [ -f "$data/mood" ]; then
    rm "$data/mood"
    restoreFlux
    echo "Restoring F.lux to normal. Please be patient."
  fi
  if [ -f "$data/darkroom" ]; then
    rm "$data/darkroom"
    restoreFlux
    osascript -e 'tell application "System Events"' \
      -e 'tell application processes' \
      -e 'key code 28 using {command down, option down, control down}' \
      -e 'end tell' \
      -e 'end tell'
    echo "Restoring F.lux to normal. Please be patient."
  fi
  if [ -f "$data/disable" ]; then
    rm "$data/disable"
    restoreFlux
  fi
  exit 0
fi

# Send it to darkroom mode
if [[ $q = "darkroom" ]]; then
  saveColors
  setUniform 1000

  osascript -e 'tell application "System Events"' \
    -e 'tell application processes' \
    -e 'key code 28 using {command down, option down, control down}' \
    -e 'end tell' \
    -e 'end tell'

  touch "$data/darkroom"

  echo Darkroom Mode has started.
  exit 0
fi

# Enter Mood lighting mode
if [[ $q = "mood" ]]; then
  saveColors
  setUniform 2400

# Options for flicker.php... we're only allowing dark and windy for now.
# dark, dim, normal, bright
# calm, breeze, windy, hurricane

  touch "$data/mood"

  nohup php flicker.php 'dark' 'windy' "$data/mood" > /dev/null 2>&1 &
  echo Mood lighting shall now commence.
  exit 0
fi

# Reset Values
if [[ $q = "reset" ]]; then
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist nightColorTemp -integer 3400
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist dayColorTemp -integer 6500
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist lateColorTemp -integer 3400
  echo "F.lux has been reset to defaults. (Day: 6500; Night: 3400; Late: 3400)"
  exit 0
fi
