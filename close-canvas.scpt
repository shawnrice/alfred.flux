tell application (path to frontmost application as Unicode text)
  activate
  tell application "System Events"
    keystroke "w" using {command down}
    keystroke "f" using {command down, control down}
    delay .4
  end tell
end tell
