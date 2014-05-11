Alfred.Flux
===========

This workflow controls [f.lux](https://justgetflux.com). Since it uses a PHP script filter to do the heavy lifting when it comes to manipulating dates, it isn't the most responsive thing, but it has quite a few features.

If you just want to disable F.lux for an hour, then just type `disflux`. It's faster that way.

Otherwise, the keyword `flux` will let you set the current color temperature, set preferences, disable for any specified period of time (the time filter is the same one as on my [Caffeinate Workflow](http://www.packal.org/workflow/caffeinate-control)). You can also disable until sunrise, which will, of course, disable until the next sunrise.

Whenever f.lux is disabled, the only option that you have is to re-enable it.

When you try to set the color, it always defaults to having you set the color for the current time period (day / night / late).

You can set the color temperatures for the other times via "flux set ...".

You can set the color far outside of Flux's normal range (2700-6500). You can go down to 1000 and up to 27000. Be warned: flux transitions to and from lower temperatures __very__ slowly, so expect it to take some time.

You can enter into "Darkroom Mode," which is a simulated version of what Flux's does. Basically, it sets the color to 1000K and inverts the screen colors.

You can also enter into "Mood Lighting" mode, which is something that f.lux doesn't offer! Mood lighting sets your computer temperature to candlelight, opens a blank page in a web browser, and then proceeds to randomly adjust the brightness of your screen to simulate a candle flickering. Don't use this when you're trying to work on your computer, but maybe turn it on a darker room to set a nice mood.

When you're in Darkroom or Mood Lighting mode, the only option that you have when you type `flux` is to restore f.lux's normal behavior.

If I release any future versions of this workflow, they will have better Icons, and I'll try to speed up the script filter.

Below are some other random notes.

 Explanation
 -----------
   This workflow does two things:
     (1) Control F.lux, and
     (2) Hack the hell out of F.lux.
  (1) It can control F.lux's settings by rewriting the preferences file that
       F.lux uses; however, only a bit of the information is stored there.
           --- Note: more might be stored in its sqlite cache databases, but
           --- I need to query those more manually to figure that out.
       That means we can set the main preferences that we need:
         (1) Day Color Temp
         (2) Night Color Temp

         Past that, we also have access to:
         (1) Location (Latitude and Longitude);
         (2) An Extra Hour of Sleep;
         (3) Sleep in on Weekends/
         (4) Steptime (I think this has something to do with the transition speed,
             but my testing hasn't shown me exactly how);
         (5) Wake Time; and
         (6) Fast Fade at Sunset.

         There are a few more, but they are mostly irrelevant for controlling F.lux.

         This workflow lets you set those preferences without opening F.lux's prefs.

   (2) In order to replicate F.lux's
           (1) Disable for an hour; and
           (2) Disable until sunset
       features, we have to first tell F.lux that night is day and day is night,
       and then setup a little script to run for the specified "break" and then
       stop lying to F.lux. Since this is a hack, enabling F.lux from the menubar
       will NOT do anything.

       The reason why we need to implement these hacks is simply that F.lux does
       not let you control anything about it from the command line, so those
       fancy new features are not available to use without a bit of hacking.
           --- Not ever going to work:
                 (1) Movie Mode
                 (2) Disable for this App

  Presets
  -------
  These are custom presets that extend the native ones.
  --- Note: All numbers are in Kelvin.
  --- [Color Temperature](https://en.wikipedia.org/wiki/Color_temperature)
    Dark Room             900 --- Note: the transition takes for-damn-ever.
    Ember                1200
    Candle               1900
    Warm Incandescent    2300
    Incandescent         2700 --- Note: The transition below 3000 is slow.
    Halogen              3400
    Fluorescent          4200
    Daylight             5500
    Off                  6500
    Blue Period         27000

    !!! Darkroom also inverts the screen colors, only if you have enabled it via
    accessibilty.

    Presets from the [F.lux FAQ page](https://justgetflux.com/faq.html):
    For OSX
      Candle             2300
      Tungsten           2700
      Halogen            3400
      Fluorescent        4200
      Daylight           5000

    For Windows
      Ember              1200
      Candle             1900
      Warm Incandescent  2300
      Incandescent       2700
      Halogen            3400
      Fluorescent        4200
      Daylight           5500

 Notes
 -----

 Features not supported:
    1. Movie Mode
       -- Cannot because I don't know F.lux's settings.
 Standard displays have their whitepoint at 6500K.

 Disable for an... is partially supported.
 Darkroom is partially supported.