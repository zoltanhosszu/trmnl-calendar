<?php


  // URL for the host
  // Don't forget the trailing slash!
  // This is used to generate the correct URL for the forecast icon
  // E.g. https://example.com/trmnl-calendar/icons/0_0.svg
  $host_url = "https://example.com/trmnl-calendar/";


  // URL for the iCal file
  $ical_url = "https://calendar.google.com/calendar/ical/...";


  // Number of days to display in the schedule (including today)
  $days_to_display = 7;


  // Location for the weather API
  $latitude = 50.7344;
  $longitude = 7.0955;


  // Set the timezone
  // This is used to display the correct date and time in weather data
  $timezone = 'Europe/Berlin';


  // Define days and months names
  // This helps with localization, because PHP's locale names are unreliable
  $days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
  $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
  $today = "Today";
  $tomorrow = "Tomorrow";


  // Define significant yearly recurring events here
  // A countdown towards the next event is be displayed at the bottom of the screen
  // E.g. "10 days until Christmas Day"
  $importantEvents = (object)[
    "01-01" => "New Year's Day",
    "12-24" => "Christmas Eve",
    "12-25" => "Christmas Day",
    "12-31" => "New Year's Eve"
  ];

?>