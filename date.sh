################################################################################
# Converts seconds to a nicely formatted string.
secondsToHumanTime() {
  total="$1"
  if [[ -z "$total" ]]; then
    # empty
    text="indefinitely."
  else
    # We now have the amount of time left.
    # left=`expr $total - $s`

    # Let's reset some variables.
    h=0; m=0; s=0;
    ((hours=$total/3600))
    ((minutes=($total%3600)/60))
    ((seconds=$total%60))

    # Now we're going to make the remaining time look pretty.
    if [[ $hours -gt 0 ]]; then
      if [[ $hours -gt 1 ]]; then
        string="$hours hours"
      else
        string="$hours hour"
      fi
      # Some nice formatting glue
      if [[ $minutes -gt 0 ]]; then
        if [[ $seconds -gt 0 ]]; then
          string="$string, "
        else
          string="$string and "
        fi
      else
        string="$string."
      fi
    fi

    if [[ $minutes -gt 0 ]]; then
      if [[ $minutes -gt 1 ]]; then
        string="$string $minutes minutes"
      else
        string="$string $minutes minute"
      fi
      if [[ $hours -gt 0 ]]; then
        if [[ $seconds -gt 0 ]]; then
          string="$string, and "
        else
          string="$string."
        fi
      elif [[ $seconds -gt 0 ]]; then
        string="$string and "
      else
        string="$string."
      fi
    fi

    if [[ $seconds -gt 0 ]]; then
      if [[ $seconds -gt 1 ]]; then
        string="$string $seconds seconds."
      else
        string="$string $seconds second."
      fi
    fi

    # Cleanup the string. This shouldn't be necessary anymore, but, whatever.
    string=`echo $string | sed 's/ ,/, /g' | sed 's/  */ /g'`
    echo $string
  fi
}

################################################################################
# Parses non-standard arguments
parseTime() {
  arg=$*
  arg=`echo "$arg"|sed 's/^ *//g'|sed 's/ *$//g'`

  args=(${arg// / })
  count=${#args[*]}

  if [[ $count -eq 1 ]]; then
    echo `parseTimeArg $arg`
    exit
  elif [[ $count -eq 2 ]]; then

   t1=`parseTimeArg ${args[0]}m`
   t2=`parseTimeArg ${args[1]}h`
   (( time=$t1+$t2 ))
   echo $time
   exit
 else
   # there are more than three arguments, so just make this indefinite
   echo "0"
   exit
 fi
}

################################################################################
# Subhandler to process times with just numbers or m/h afterward
parseTimeArg() {
  arg=$1
  if [[ $arg =~ ([0-9]{1,})$ ]]; then
    (( arg=$arg*60 ))
    echo $arg
    exit
  else
    [[ $arg =~ ([0-9]{1,})([hHmM]{1,}) ]]
    time=${BASH_REMATCH[1]}; unit=${BASH_REMATCH[2]}
    if [[ $unit =~ ^([hH]{1,}) ]]; then
      (( arg=$time*60*60))
      echo $arg # return the hours in seconds
      exit
    elif [[ $unit =~ ^([mM]{1,}) ]]; then
      (( arg=$time*60 ))
      echo $arg #return the minutes in seconds
      exit
    else
      # default to minutes... we shouldn't get here
      (( arg=$time*60 ))
      echo $arg #return the minutes in seconds
      exit
    fi
  fi
}

function="$1"

args=`echo $* | sed -e 's|'$function' ||g'`

cmd="$function $args"

${cmd}
