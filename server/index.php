<?php

  header('Content-Type: application/json; charset=utf-8');

  include("ical.php");
  include("config.php");

  // Set the timezone
  global $timezone;
  date_default_timezone_set($timezone);


  // ------------------------------------------------------------------------------------
  // HELPER FUNCTIONS

  function cURLRequest($url, $body = "") {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($body != "") {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    $result = json_decode(curl_exec($ch));
    curl_close($ch);
    
    return $result;
  }



  // ------------------------------------------------------------------------------------
  // CALENDAR FUNCTIONS

  // Download events from Google
  function getEvents() {
    global $ical_url, $days_to_display;
    
    $iCal = new iCal($ical_url);
    
    $schedule = $iCal->eventsByDateUntil('+' . ($days_to_display - 1) . ' days');
    
    $temp = [];
    
    for ($i = 0; $i <= $days_to_display; $i++) {
      $temp[date("Y-m-d", strtotime("+" . $i . " day"))] = [];
    }
    
    foreach($schedule as $day) {
      if (is_array($day)) {
        foreach($day as $event) {
          $dateDiff = daysBetween($event->{'dateStart'}, $event->{'dateEnd'});
          
          if ($dateDiff == 0) {
            $start = date_format(date_create($event->{'dateStart'}), "Y-m-d");
            
            if (array_key_exists($start, $temp)) {
              $temp[$start][] = formatEvent($event);
            }
          } else {
            for ($i = 0; $i < $dateDiff; $i++) {
              $day = date("Y-m-d", strtotime($event->{'dateStart'} . " +" . $i . " day"));
              
              if (array_key_exists($day, $temp)) {
                $temp[$day][] = formatEvent($event, true);
              }
            }
          }
        }
      }
    }
    
    return $temp;
  }


  // Calculate days between 2 dates
  function daysBetween($dt1, $dt2) {
    return date_diff(
      date_create($dt2),  
      date_create($dt1)
    )->format('%a');
  }


  // Remove unnecessary event information
  function formatEvent($event, $fullday = false) {
    $temp = (object)[];
    $temp->{"title"} = $event->{'summary'};
    
    if ($fullday) {
      $temp->{"fullday"} = "true";
      $temp->{"start"} = date_format(date_create($event->{'dateStart'}), "Y-m-d");
      $temp->{"end"} = date_format(date_create($event->{'dateEnd'}), "Y-m-d");
    } else {
      $temp->{"fullday"} = "false";
      $temp->{"start"} = date_format(date_create($event->{'dateStart'}), "H:i");
      $temp->{"end"} = date_format(date_create($event->{'dateEnd'}), "H:i");
    }
    
    // Location data cleanup
    if (isset($event->{'location'}) != null) {
      $temp->{"location"} = $event->{'location'};
    }
    
    return $temp;
  }


  // Important family events
  function getCountdown() {
    global $importantEvents;
      
    $today = new DateTime();
    $closestEvent = null;
    $minDays = null;

    foreach ($importantEvents as $md => $title) {
      $eventDate = DateTime::createFromFormat('Y-m-d', $today->format('Y') . '-' . $md);
      
      // If the date is in the past, use next year
      if ($eventDate < $today) {
        $eventDate->modify('+1 year');
      }
    
      $interval = $today->diff($eventDate)->days;
    
      if ($minDays === null || $interval < $minDays) {
        $minDays = $interval;
        $closestEvent = (object)[
          'title' => $title,
          'date' => $eventDate->format('Y-m-d'),
          'days_remaining' => $interval,
        ];
      }
    }
    
    return $closestEvent;
  }


  // ------------------------------------------------------------------------------------
  // WEATHER FUNCTIONS

  function getWeather() {
    global $latitude, $longitude, $days_to_display, $timezone;

    $weather_url = "https://api.open-meteo.com/v1/forecast?latitude=" . $latitude . "&longitude=" . $longitude . "&daily=precipitation_sum,precipitation_probability_max,weather_code,temperature_2m_min,temperature_2m_max&current=temperature_2m,weather_code,is_day&timezone=" . urlencode($timezone) . "&forecast_days=" . $days_to_display;
    
    return cURLRequest($weather_url);
  }



  // ------------------------------------------------------------------------------------
  // MAIN FUNCTIONS

  function run() {
    global $days, $months, $today, $tomorrow, $host_url;


    // Get events and weather data
    $temp = getEvents();
    
    $weather = getWeather();
    
    $events = [];
    
    foreach($temp as $key => $value) {
      $dayObj = (object)[];
      $dayObj->{"date"} = $key;
      
      $forecastKey = array_search($key, $weather->{"daily"}->{"time"});
      if ($forecastKey !== false) {
        $dayObj->{"forecast"} = round($weather->{"daily"}->{"temperature_2m_max"}[$forecastKey]) . "º";
        
        $code = $weather->{"daily"}->{"weather_code"}[$forecastKey];
        $dayObj->{"forecast_icon"} = $host_url . "icons/" . $code . ".svg";
        
      }
      
      $day = $days[date("w", strtotime($key))];
      $dayDate = date("j", strtotime($key)) . " " . $months[intval(date("n", strtotime($key))) - 1];
      $niceDate = $day . ", " . $dayDate;
      
      if (daysBetween(date("Y-m-d"), $key) == 0) {
        $niceDate = $today;
        $dayObj->{"day"} = $day;
        $dayObj->{"date"} = $dayDate;
      } else if (daysBetween(date("Y-m-d"), $key) == 1) {
        $niceDate = $tomorrow;
      }
      $dayObj->{"niceDate"} = $niceDate;
      $dayObj->{"events"} = $value;
      
      if ((count($value) !== 0) || ($niceDate == $today)) {
        $events[] = $dayObj;
      }
    }
    
    $data = (object)[];
    foreach ($events as &$event) {
      if ($event->{"niceDate"} == $today) {
        $data->{"today"} = $event;
      } else {
        $data->{"schedule"}[] = $event;
      }
    }
    
    $data->{"weather"} = $weather;
    $data->{"weather"}->{"current"}->{"temperature_2m"} = round($data->{"weather"}->{"current"}->{"temperature_2m"});
    $data->{"weather"}->{"daily"}->{"temperature_2m_min"} = array_map(function($v) { return round($v); }, $data->{"weather"}->{"daily"}->{"temperature_2m_min"});
    $data->{"weather"}->{"daily"}->{"temperature_2m_max"} = array_map(function($v) { return round($v); }, $data->{"weather"}->{"daily"}->{"temperature_2m_max"});
    

    // Get countdown towards the next important event
    $countdown = getCountdown();
    
    $data->{"countdown"} = $countdown;
    

    // Output the data as JSON
    echo json_encode($data, JSON_PRETTY_PRINT);
  }

  run();

?>