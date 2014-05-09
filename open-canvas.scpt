tell application (path to frontmost application as Unicode text)
  activate
  tell application "System Events"
    keystroke "f" using {command down, control down}
  end tell
end tell
