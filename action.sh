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
  echo $q
fi

if [[ $q =~ "disable" ]]; then
  if [ -f "$data/mood" ]; then
    rm "$data/mood"
  fi
  if [ -f "$data/darkroom" ]; then
    rm "$data/darkroom"
  fi
  echo $q
fi

if [[ $q =~ "set-" ]]; then
  echo $q
fi

# Simple arguments... for now.

if [[ $q = "open" ]]; then
  open /Applications/Flux.app
fi

if [[ $q = "restore" ]]; then
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






fi

if [[ $q = "darkroom" ]]; then
  saveColors
  setUniform 1000

  osascript -e 'tell application "System Events"' \
    -e 'tell application processes' \
    -e 'key code 28 using {command down, option down, control down}' \
    -e 'end tell' \
    -e 'end tell'

  touch "$data/darkroom"
  nohup ./darkroom.sh > /dev/null 2>&1 &

  echo Darkroom Mode has started.
fi

if [[ $q = "mood" ]]; then
  saveColors
  setUniform 2400

# dark, dim, normal, bright
# calm, breeze, windy, hurricane

  touch "$data/mood"
  osascript open-canvas.scpt
  nohup php flicker.php > /dev/null 2>&1 &
  echo Mood lighting shall now commence.
fi

if [[ $q = "reset" ]]; then
  # defaults read $HOME/Library/Preferences/org.herf.Flux.plist nightColorTemp
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist nightColorTemp -integer 6000
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist dayColorTemp -integer 6500
  defaults write $HOME/Library/Preferences/org.herf.Flux.plist lateColorTemp -integer 3400
  echo "F.lux has been reset to defaults. (Day: 6500; Night: 3400; Late: 3400)"
fi
