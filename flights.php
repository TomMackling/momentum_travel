<?php

date_default_timezone_set("UTC");

function db_name() {
   return "flights_db";
}
function max_num_layovers() {
   return 4;
}

function mins_min_layover() {
   return 20;
}

function mins_max_layover() {
   return 60*6;
}

function max_num_airports_destination_country() {
   return 20;
}
function max_num_airports_destination_any() {
   return 20;
}



function max_num_legs() {
   return 1+max_num_layovers();
}


echo("<pre>");

$src_airport = !empty($_REQUEST["src_airport"]) ? $_REQUEST["src_airport"] : "";
$dst_airport = !empty($_REQUEST["dst_airport"]) ? $_REQUEST["dst_airport"] : "";

$src_city = "";
if ( empty($src_airport) ) {
   $src_city = !empty($_REQUEST["src_city"]) ? $_REQUEST["src_city"] : "";
   if ( empty($src_city) ) {
      echo("Departure Airport/City not specified");
      exit();
   }
}


$dst_city = "";
if ( empty($dst_airport) ) {
   $dst_city = !empty($_REQUEST["dst_city"]) ? $_REQUEST["dst_city"] : "";
}

$s_time = "00:00";
$s_time_utc = strftime(s_time_fmt());
$departure_date = !empty($_REQUEST["departure_date"]) ? $_REQUEST["departure_date"] : "";
$s_date_today = strftime(s_date_fmt());


if ( !empty($departure_date) ) {
   $date_time_requested = new DateTime($departure_date . " 00:00:00");
   $date_time_now = new DateTime($s_date_today . " 00:00:00");
   if ( $date_time_requested <  $date_time_now ) {
      $departure_date = "";
   }
}

if ( empty($departure_date) ) {
//    $departure_date = strftime(s_date_fmt());
   
   //$date_time = new DateTime($s_date . " 00:00:00");
   
   $time = $s_date_today.",".$s_time_utc;
   $date_time = date_create_from_format(s_datetime_fmt(false), $time, new DateTimeZone("UTC"));
   $date_time->modify("+1 day");
   $departure_date = dt_2_s_date($date_time);
}
elseif ( $departure_date == $s_date_today ) {
   $s_time = $s_time_utc; 
}

$f_round_trip =  !empty($_REQUEST["round_trip"]);
$departure_date_rtn = !empty($_REQUEST["departure_date_rtn"]) ? $_REQUEST["departure_date_rtn"] : "";



if ( $f_round_trip ) {
   if ( empty($departure_date_rtn) ) {
      echo("ERROR: return date not specified");
      exit();
   }
   $dt_departure_date_chk = date_create_from_format(s_datetime_fmt(false), $departure_date, new DateTimeZone("UTC"));
   $dt_return_date_chk = date_create_from_format(s_datetime_fmt(false), $departure_date_rtn, new DateTimeZone("UTC"));
   if ( $dt_return_date_chk < $dt_departure_date_chk ) {
      echo("ERROR: return date before departure date");
      exit();
   }
}

$a = array();
if ( !empty($src_city) ) {
   $a["departure_city_code"] = $src_city;
}
if ( !empty($src_airport) ) {
   $a["departure_airport"] = $src_airport;
}
if ( !empty($dst_city) ) {
   $a["arrival_city_code"] = $dst_city;
}
if ( !empty($dst_airport) ) {
   $a["arrival_airport"] = $dst_airport;
}
if ( empty($dst_city) && empty($dst_airport) ) {
   if ( !empty($_REQUEST["dst_country"]) ) {
      $a["arrival_country_code"] = $_REQUEST["dst_country"];
   }
}


$a["s_date"] = $departure_date;
$a["s_time"] = $s_time;

if ($f_round_trip) {
   $a["departure_date_rtn"] = $departure_date_rtn;    
}


//$arr_airports_all = airports();
//$arr_flights = flights($a, $arr_airports_all);

$arr_flights = flights($a);

if ( empty($arr_flights) ) {
   exit();
}


//  echo(json_encode($arr_flights));

if ( $f_round_trip ) {
   foreach($arr_flights as $arr_pair) {
      foreach($arr_pair as $k => $owt) {
         echo('"'.$k.'":'."\n"."$owt"."\n");
      }   
   }
}

else {
   foreach($arr_flights as $owt) {
      echo("$owt"."\n");
   }
}


echo("</pre>");
exit();



function airports(array $a=null) {
   $airports = array();
   $mc = new MongoClient();
   $db_name = db_name();
   
   $db = $mc->$db_name;
   $collection = $db->airports;
   $cursor = $collection->find();
   
   foreach($cursor as $document) {
       if ( !empty($document["code"])
           && !empty($document["region_code"])
           && !empty($document["country_code"])
           && !empty($document["city_code"])
           && ( empty($a["code"]) || $a["code"] == $document["code"] )
           && ( empty($a["city_code"]) || $a["city_code"] == $document["city_code"] )
           && ( empty($a["city"]) || !empty($document["city"]) && $a["city"] == $document["city"] )
           && ( empty($a["region_code"])  || $a["region_code"] == $document["region_code"] )
           && ( empty($a["country_code"]) || $a["country_code"] == $document["country_code"] )
          ) {
           $airports[$document["code"]] = $document;		
       }    
   }
   return $airports;
}

function s_date_fmt($f=true) {
   return $f?"%Y-%m-%d":"Y-m-d";
}
function s_time_fmt($f=true) {
   return $f?"%H:%M":"G:i";
}

function s_datetime_fmt($f=true) {
   return s_date_fmt($f).",".s_time_fmt($f);
}

function dt_2_s_date($dt) {
   return $dt->format(s_date_fmt(false));
}

// function direct_flights(array $a, array $airports=null) {

//    if ( empty($airports) ) {
//       $airports = airports();
//    }
//    $date_time = null;
   
//    if ( !empty($a["date_time"]) ) {
//       $date_time = $a["date_time"];	
//    } 
//    else {

//       $s_date = !empty($a["s_date"]) ? $a["s_date"] : strftime(s_date_fmt());
//       $s_time_utc = !empty($a["s_time"]) ? $a["s_time"] : strftime(s_time_fmt());

//       $time = $s_date.",".$s_time_utc;
//       $date_time = date_create_from_format(s_datetime_fmt(false), $time, new DateTimeZone("UTC")); 
//    } 
   
//    $dt_now = new DateTime();
//    if ( $date_time < $dt_now ) {
//       $date_time = $dt_now;
//    }
 
//    $dt_now_nextyear = new DateTime();
//    $dt_now_nextyear->modify("+365 days");   
   
//    $flights = array();
//    $mc = new MongoClient();
//    $db_name = db_name();
   
//    $db = $mc->$db_name;
//    $collection = $db->flights;
//    $cursor = $collection->find();

//    foreach($cursor as $document) {
//        if ( !empty($document["airline"])
//            && !empty($document["number"])
//            && !empty($document["departure_airport"])
//            && !empty($document["departure_time"])
//            && !empty($document["arrival_airport"])
//            && !empty($document["arrival_time"])
//            && !empty($document["price"])
//           ) {
//            if (  !empty($a["departure_airport"]) && $a["departure_airport"] != $document["departure_airport"]
//               || !empty($a["arrival_airport"]) && $a["arrival_airport"] != $document["arrival_airport"]
//               || !empty($a["price_max"]) && (float)$document["price"] > (float)$a["price_max"]		              
//               ) {
//                continue;
//            }
//            $dt_departure = date_time_departure($document, $airports, $date_time);
           
//            if ( $dt_now_nextyear < $dt_departure ) {
//                continue;
//            } 
           
//            if ( $date_time > $dt_departure ) {
//                continue;
//            }
           
//            $document["departure_date_time"] = $dt_departure;
            
//            $dt_arrival = date_time_arrival($document, $airports, $dt_departure);
//            $document["arrival_date_time"] = $dt_arrival;
           
//            $flight_id = $document["airline"].":".$document["number"];
//            $document["flight_id"] = $flight_id;
	   	
//            $flights[$flight_id] = $document;		
//        }    
//    }
//    return $flights;
// }

function date_time_departure(array $a, array $airports, DateTime $date_time, $f_UTC=false) {
   return date_time_departure_arrival($a, "departure", $airports, $date_time, $f_UTC);
}

function date_time_arrival(array $a, array $airports, DateTime $date_time, $f_UTC=false) {
   return date_time_departure_arrival($a, "arrival", $airports, $date_time, $f_UTC);
}

function date_time_departure_arrival(array $a, $prefix, array $airports, DateTime $date_time,
   $f_UTC) {

   $tz_UTC = new DateTimeZone("UCT");
   $date_time->setTimezone($tz_UTC);

   $dt_now = new DateTime();

   if ( $date_time < $dt_now ) {
      $date_time->modify("+1 day");
   }
   $f = $date_time >= $dt_now;
   assert($f);
   if ( !$f ) {
      return null;
   }

   if ( $f_UTC ) {

      if ( empty($a["{$prefix}_time_UTC"]) ) {
         return null;
      }

      $s_date = dt_2_s_date($date_time);
      $time = $s_date.",".$a["{$prefix}_time_UTC"];
      $dt_ret = date_create_from_format(s_datetime_fmt(false), $time, $tz_UTC);
      if ( $dt_ret <  $date_time ) {
         $dt_ret->modify("+1 day");   
      }
      $f = $dt_ret >= $date_time;
      assert($f);
      if ( !$f ) {
         return null;
      }
      return $dt_ret;
   }
   
   // else
   if ( empty($a["{$prefix}_time"]) ) {
      return null;
   }
   if ( empty($a["{$prefix}_airport"]) ) {
      return null;
   }
   $airport = $a["{$prefix}_airport"];
   if ( !isset($airports[$airport]) ) {
      return null;
   }

   $airport_info = $airports[$airport];
   if ( empty($airport_info["timezone"]) ) {
      return null;
   }

   $tz = new DateTimeZone($airport_info["timezone"]);

   $date_time_tz = clone($date_time);
   $date_time_tz->setTimezone($tz);


   $s_date = dt_2_s_date($date_time_tz);
   $time = $s_date.",".$a["{$prefix}_time"];
   $dt_ret = date_create_from_format(s_datetime_fmt(false), $time, $tz);
   
   if ( $dt_ret < $date_time_tz ) {
      $dt_ret->modify("+1 day");   
   }
   $f = $dt_ret >= $date_time_tz;
   assert($f);
   if ( !$f ) {
      return null;
   }

   $dt_ret->setTimezone($tz_UTC);
   $f = $dt_ret >= $date_time;
   assert($f);
   if ( !$f ) {
      return null;
   }

   return $dt_ret;
} 


//function flights_airport2airport(array $a, array $airports=null) {
//   
//   if ( empty($a["departure_airport"]) ) {
//      return null;
//   }
//   if ( empty($a["arrival_airport"]) ) {
//      return null;
//   }
//
//   if ( empty($airports) ) {
//      $airports = airports();
//   }
//
//   $arr_direct_flights = direct_flights($a, $airports);
//
//   $best_cost = INF;
//   $cheapest_flight = null;
//   
//   $a_0 = $a;
//   unset($a_0["arrival_airport"]);
//   $a_1 = $a;
//   unset($a_1["departure_airport"]);
//
//   //$earliest_date = "";
//   if ( !empty($arr_direct_flights) ) {
//      $cheapest_flight = cheapest_flight($arr_direct_flights);
//      $best_cost = $cheapest_flight["price"];
//      $a_0["price_max"] = $best_cost;
//      $a_1["price_max"] = $best_cost;
//   }
//
//   $arr_flights_out = direct_flights($a_0, $airports);
//   $arr_flights_in = direct_flights($a_1, $airports);
//
//   $out = new ArrOneWayTrips();
//      
//   foreach($arr_direct_flights as $flight_id => $flight) {
//      unset($arr_flights_out[$flight_id]);
//      unset($arr_flights_in[$flight_id]);
//      
//      $arr_flights = array();
//      $arr_flights[$flight_id] = $flight;
//      $owt = new OneWayTrip($arr_flights);
//      $out->add_one_way_trip($owt);      
//   }
//
//
//   if ( max_num_layovers() >= 1 ) {
//   
//      foreach($arr_flights_out as $flight_out) {
//   
//         foreach($arr_flights_in as $flight_in) {      
//   
//            if ( OneWayTrip::can_connect($flight_out, $flight_in) ) {
//               $arr_flights = array();
//               $arr_flights[$flight_out["flight_id"]] = $flight_out;
//               $arr_flights[$flight_in["flight_id"]] = $flight_in;
//               $owt = new OneWayTrip($arr_flights);
//               $out->add_one_way_trip($owt);
//            }
//         }
//      }
//   }
//   
//   if ( max_num_layovers() >= 2 ) { 
//   
//      $arrLocations2goal = array();
//      $date_time = null;
//
//      $date_time = null;
//   
//      if ( !empty($a["date_time"]) ) {
//          $date_time = $a["date_time"];	
//      } 
//      else {
//   
//         $s_date = !empty($a["s_date"]) ? $a["s_date"] : strftime(s_date_fmt());
//         $s_time_utc = !empty($a["s_time"]) ? $a["s_time"] : strftime(s_time_fmt());
//   
//         $time = $s_date.",".$s_time_utc;
//         $date_time = date_create_from_format(s_datetime_fmt(false), $time, new DateTimeZone("UTC")); 
//      } 
//      
//      $curr_location = new Location($a["departure_airport"], $date_time, 0);
//      
//      $arrLocationsFrom = array();
//      $aowt_complete = new ArrOneWayTrips();
//      
//      flights_airport2airport_rec($curr_location, $arrLocationsFrom, $a["arrival_airport"], $airports, max_num_layovers(), $out);
//   }
//   
////    filter($arr_out);
//   return $out->arr_one_way_trips();    
//}


//function curr_location_in_from(Location $location_curr, array $arrLocationsFrom) {
//   
//   $airport_code_curr = $location_curr->airport_code();
//   $date_time_curr = $location_curr->date_time();
//
//   if ( !isset($arrLocationsFrom[$airport_code_curr]) ) {
//      return false;
//   }
//   $location_from = $arrLocationsFrom[$airport_code_curr];
//   $date_time_from = $location_from->date_time();
//   if ( $date_time_from <= $date_time_curr ) {
//      return true;
//   }
//   return false;
//}

//function flights_airport2airport_rec(Location $curr_location,
//      array $arrLocationsFrom,
//      $arrival_airport_code, array $airports, $max_legs, 
//      ArrOneWayTrips $aowt) {
//   
//   if ( empty($arrival_airport_code) ) {
//      return false;
//   }
//   
//   if ( 0 >= $max_legs ) {
//      return false;
//   }
//   $curr_airport_code = $curr_location->airport_code();
//   
//   if ( $curr_airport_code == $arrival_airport_code ) {
//      return true;
//   }
//   if ( curr_location_in_from($curr_location, $arrLocationsFrom) ) {
//      // no point
//      return false;
//   }
//
//   $location_org = $curr_location->location_org();
//   $arr_owt_org = null;
//
//   if ( !empty($location_org) ) {
//      
//      $airport_code_org = $location_org->airport_code();
//      assert($airport_code_org != $curr_airport_code);
//      
//      $arr_owt_org = $aowt->arr_one_way_trips($airport_code_org);
//      foreach($arr_owt_org as $owt) {
//         if ( $owt->price() <= $curr_location->cost_so_far()
//            && $owt->arrival_date_time() <= $curr_location->date_time()
//            ) {
//            return false;
//         }     
//      }
//   }
//   if ( !empty($aowt->arr_one_way_trips($curr_airport_code)) ) {
//      // we've already obtained all the best flights from the current airport
//
//
//      $arr_owt = $aowt->arr_one_way_trips($curr_airport_code);
//      foreach($arr_owt as $owt) {
//      
//         if ( $owt->departure_date_time() <= $curr_location->date_time() ) {
//            return;               
//         }
//      }
//
//
//      return true;
//   }
//
//
//   
//   if ( 1 == $max_legs ) {
//      
//      $arr_flights2location = $curr_location->arr_flights2here();
//
//      $a = array();
//      $a["date_time"] = $curr_location->date_time();
//      $a["arrival_airport"] = $arrival_airport_code;
//      $a["departure_airport"] = $curr_location->airport_code();
//      
//      $arr_direct_flights = direct_flights($a, $airports);
//   
//      foreach($arr_direct_flights as $flight_id => $flight) {
//         
//         $arr_flights = $arr_flights2location;
//         assert ( !isset($arr_flights[$flight_id]) );
//         
//         $arr_flights[$flight_id] = $flight;
//                  
//         $owt = new OneWayTrip($arr_flights);
//         
//         $aowt->add_one_way_trip($owt);
//      }
//
//      return true;
//   }
//   
//   $location = $curr_location;      
//      
//   $a = array();
//   $a["date_time"] = $location->date_time();
//   $a["departure_airport"] = $location->airport_code();
//   
//   $arr_flights2location = $location->arr_flights2here();
//   
//   $arr_flights_out = direct_flights($a, $airports);
//   
//   foreach($arr_flights_out as $flight_id => $flight) {
//
//      $arr_flights = $arr_flights2location;
//
//      assert ( !isset($arr_flights[$flight_id]) );
//      
//      $arr_flights[$flight_id] = $flight;
//      
//      $arrival_airport_ = $flight["arrival_airport"];
//      $cost_so_far = $curr_location->cost_so_far()+$flight["price"];
//
//      $f_skip = false;
//      foreach($arr_owt_org as $owt) {
//         if ( $owt->price() <= $cost_so_far
//            && $owt->arrival_date_time() <= $flight["arrival_date_time"]
//            ) {
//            $f_skip = true;
//            break;
//         }     
//      }
//
//
//      if ( $f_skip ) {
//         continue;
//      }
//      
//      $new_location = new Location($arrival_airport_, 
//         $flight["arrival_date_time"], 
//         $cost_so_far, $arr_flights);
//      
//      if ( $arrival_airport_ == $arrival_airport_code ) {
//                     
//         $owt = new OneWayTrip($arr_flights);
//         $aowt->add_one_way_trip($owt);
//         continue;
//      }
//      if ( curr_location_in_from($new_location,  $arrLocationsFrom) ) {
//         continue;
//      }
//      $arrLocationsFrom_ = $arrLocationsFrom;
//      $arrLocationsFrom_[] = $curr_location; 
//      
//      $f = flights_airport2airport_rec($new_location,
//         $arrLocationsFrom_,
//         $arrival_airport_code, $airports, $max_legs-1, 
//         $aowt);      
//
//      
//      HERE!
//   }   
//}


////*****************************************************************************
//function flights(FlightData $fd, array $a) {
//
//   $airports_src = array();
//   $airports_dst = array();
//   
//   if ( !isset($a["departure_city_code"]) 
//     && !isset($a["departure_airport"])
//      ) {
//      return null;
//   }
//
//   $airports_all = $fd->arr_airports;
//   $departure_city_code = "";
//   if ( isset($a["departure_airport"]) ) {
//      
//      unset($a["departure_city_code"]);
//      $s_departure_airport = $a["departure_airport"];
//      if ( empty($airports_all[$s_departure_airport]) ) {
//         return null;
//      }
//      $airports_src[$s_departure_airport] = $airports_all[$s_departure_airport];   
//      $departure_city_code = $airports_src[$s_departure_airport]["city_code"];
//   }
//   else {
//      $departure_city_code = $a["departure_city_code"];
//      if ( empty($fd->arr_city_code2arr_airports[$departure_city_code]) ) {
//         return null;
//      }
//      $airports_src = $fd->arr_city_code2arr_airports[$departure_city_code];
//   }
//
//   if ( isset($a["arrival_airport"]) ) {
//      
//      $s_arrival_airport = $a["arrival_airport"];
//      if ( empty($airports_all[$s_arrival_airport]) ) {
//         return null;
//      }
//
//      if ( $airports_all[$s_arrival_airport]["city_code"] == $departure_city_code ) {
//         return null;
//      }
//
//      $airports_dst[$s_arrival_airport] = $airports_all[$s_arrival_airport];   
//   }
//   elseif ( isset($a["arrival_city_code"]) ) {
//
//      $arrival_city_code = $a["arrival_city_code"];
//      if ( $arrival_city_code == $departure_city_code ) {
//         return null;
//      }
//      if ( empty($fd->arr_city_code2arr_airports[$arrival_city_code]) ) {
//         return null;
//      }
//      $airports_dst = $fd->arr_city_code2arr_airports[$arrival_city_code];
//   }
//   elseif ( isset($a["arrival_country_code"]) ) {
//
//      $country_code = $a["arrival_country_code"];
//      foreach( $airports_all as $code => $arr_airport_nfo ) {
//         if ( $arr_airport_nfo["country_code"] == $country_code
//            && $arr_airport_nfo["city_code"] != $departure_city_code
//            ) {
//            $airports_dst[$code] = $arr_airport_nfo;
//         }
//      }
//      if ( empty($airports_dst) ) {
//         return null;
//      }
//      if ( count($airports_dst) > max_num_airports_destination_country() ) {
//         $arr_keys = array_rand($airports_dst, max_num_airports_destination_country());
//         $arr_tmp = array();
//         foreach($arr_keys as $airport_code) {
//            $arr_tmp[$airport_code] = $airports_dst[$airport_code];
//         }
//         $airports_dst = $arr_tmp;
//      }
//   }
//   else {
//      foreach($airports_all as $code => $airport) {
//         if ( $airport["city_code"] != $departure_city_code ) {
//            $airports_dst[$code] = $airport;        
//         } 
//      }
//      if ( empty($airports_dst) ) {
//         return null;
//      }
//      if ( count($airports_dst) > max_num_airports_destination_any() ) {
//         $arr_keys = array_rand($airports_dst, max_num_airports_destination_any());
//         $arr_tmp = array();
//         foreach($arr_keys as $airport_code) {
//            $arr_tmp[$airport_code] = $airports_dst[$airport_code];
//         }
//         $airports_dst = $arr_tmp;
//      }       
//   }
//
//   
//   if ( isset($a["departure_city_code"]) ) {
//      if ( isset($a["arrival_city_code"])
//         && $a["departure_city_code"] == $a["arrival_city_code"] 
//         ) {
//         return null;
//      }
//      if ( isset($a["arrival_airport"]) ) {
//         $airport = $airports_all[$a["arrival_airport"]];
//         if ( $a["departure_city_code"] == $airport["city_code"] ) {
//            return null;
//         }
//      }
//   }
//   
//   $f_round_trip = isset($a["departure_date_rtn"]);
//   
//   $out = new ArrOneWayTrips();
//   $out_return_trip = new ArrOneWayTrips();
//   
//   $departure_date_rtn = $f_round_trip ? $a["departure_date_rtn"] : "";
//   unset($a["departure_date_rtn"]);
//   
//   if ( empty($airports_src) || empty($airports_dst) ) {
//      return null;
//   }
//   
//   
//   foreach($airports_src as $code_src => $airport_src) {
//      foreach($airports_dst as $code_dst => $airport_dst) {
//         $a_1 = $a;
//         $a_1["departure_airport"] = $code_src;
//         $a_1["arrival_airport"] = $code_dst;
//         
//         $arr = flights_airport2airport($a_1, $airports_all);
//
//         if ( !empty($arr) 
//             && $f_round_trip
//            ) {
//
//            $a_ret = $a;
//            $a_ret["arrival_airport"] = $code_src;
//            $a_ret["departure_airport"] = $code_dst;
//            $a_ret["s_date"] = $departure_date_rtn; 
//                     
//            $arr_ret = flights_airport2airport($a_ret, $airports_all);
//            
//            if ( !empty($arr_ret) ) {
//               
//               foreach($arr as $owt) {
//                  $out->add_one_way_trip($owt);
//               }
//               foreach($arr_ret as $owt_ret) {
//                  $out_return_trip->add_one_way_trip($owt_ret);
//               }
//            }            
//         }
//         else {
//            foreach($arr as $owt) {
//               $out->add_one_way_trip($owt);
//            }
//         }
//      }
//   }
//   
//   if ( !$f_round_trip ) {
//      return $out->arr_one_way_trips();
//   }
//
//   
//   $arr_flight_plans = array();
//   $arr_0  = $out->arr_one_way_trips();
//   
//   foreach($arr_0 as $owt) {
//      $departure_airport = $owt->departure_airport();
//      $arrival_airport = $owt->arrival_airport();
//      $key = $departure_airport."_".$arrival_airport;
//      
//      if ( !isset($arr_flight_plans[$key]) ) {
//         $arr_flight_plans[$key] = array();
//      }
//      $arr_flight_plans[$key][] = $owt;      
//   }
//   
//   $arr_rtn_flight_plans = array();
//   $arr_1  = $out_return_trip->arr_one_way_trips();
//   foreach($arr_1 as $owt_rtn) {
//      $departure_airport = $owt_rtn->departure_airport();
//      $arrival_airport = $owt_rtn->arrival_airport();
//      $key = $arrival_airport."_".$departure_airport;
//      
//      if ( !isset($arr_rtn_flight_plans[$key]) ) {
//         $arr_rtn_flight_plans[$key] = array();
//      }
//      
//      $arr_rtn_flight_plans[$key][] = $owt_rtn;      
//   }
//   
//   $arr_ret = array();
//   foreach($arr_flight_plans as $key => $arr) {
//      assert(!empty($arr_rtn_flight_plans[$key]));
//      foreach($arr as $owt) {
//         $arr_pair_2_add = array();
//         $arr_pair_2_add["flight"] = $owt;
//         foreach($arr_rtn_flight_plans[$key] as $owt_rtn) {
//            $arr_pair_2_add_x = $arr_pair_2_add; 
//            $arr_pair_2_add_x["return"] = $owt_rtn;
//            $arr_ret[] = $arr_pair_2_add_x;
//         }
//      }
//   }
//   return $arr_ret;
//}

//
//function cheapest_flight(array $arr_flights) {
//   $best_cost = INF;
//   $cheapest_flight = null; 
//   foreach($arr_flights as $flight) {
//
//      if ( $flight["price"] < $best_cost ) {
//         $best_cost = $flight["price"];
//         $cheapest_flight = $flight;
//      }
//   }
//   return $cheapest_flight;
//}

// function filter(array &$arr) {
   
//    $arr_cpy = $arr;
//    $arr_skip = array();
//    foreach($arr as $k_1 => $owt) {
//       $f_saw_better = false;
//       foreach($arr_cpy as $k_2 => $owt2) {   
//          if ( $k_2 == $k_1 ) {
//             continue;
//          }
//          $cmp = OneWayTrip::cmp($owt, $owt2);
//          if ( 1 == $cmp ) {
//             $arr_skip[$k_2] = 1;
//          }
//          elseif ( -1 == $cmp ) {
//             $arr_skip[$k_1] = 1;
//          }
//       }
//    }
//    $arr_new = array();
//    foreach($arr as $k_1 => $owt) {
//       if ( !isset($arr_skip[$k_1]) ) {
//          $arr_new[] = $owt;
//       }
//    }
//    $arr = $arr_new;
// }
   

//*****************************************************************************
function flights(array $a) {

   $airports_src = array();
   $airports_dst = array();
   
   if ( !isset($a["departure_city_code"]) 
     && !isset($a["departure_airport"])
      ) {
      return null;
   }
   $fd = new FlightData();

   $airports_all = $fd->arr_airports;
   $departure_city_code = "";
   if ( isset($a["departure_airport"]) ) {
      
      unset($a["departure_city_code"]);
      $s_departure_airport = $a["departure_airport"];
      if ( empty($airports_all[$s_departure_airport]) ) {
         return null;
      }
      $airports_src[$s_departure_airport] = $airports_all[$s_departure_airport];   
      $departure_city_code = $airports_src[$s_departure_airport]["city_code"];
   }
   else {
      $departure_city_code = $a["departure_city_code"];
      if ( empty($fd->arr_city_code2arr_airports[$departure_city_code]) ) {
         return null;
      }
      $airports_src = $fd->arr_city_code2arr_airports[$departure_city_code];
   }

   if ( isset($a["arrival_airport"]) ) {
      
      $s_arrival_airport = $a["arrival_airport"];
      if ( empty($airports_all[$s_arrival_airport]) ) {
         return null;
      }

      if ( $airports_all[$s_arrival_airport]["city_code"] == $departure_city_code ) {
         return null;
      }

      $airports_dst[$s_arrival_airport] = $airports_all[$s_arrival_airport];   
   }
   elseif ( isset($a["arrival_city_code"]) ) {

      $arrival_city_code = $a["arrival_city_code"];
      if ( $arrival_city_code == $departure_city_code ) {
         return null;
      }
      if ( empty($fd->arr_city_code2arr_airports[$arrival_city_code]) ) {
         return null;
      }
      $airports_dst = $fd->arr_city_code2arr_airports[$arrival_city_code];
   }
   elseif ( isset($a["arrival_country_code"]) ) {

      $country_code = $a["arrival_country_code"];
      foreach( $airports_all as $code => $arr_airport_nfo ) {
         if ( $arr_airport_nfo["country_code"] == $country_code
            && $arr_airport_nfo["city_code"] != $departure_city_code
            ) {
            $airports_dst[$code] = $arr_airport_nfo;
         }
      }
      if ( empty($airports_dst) ) {
         return null;
      }
      if ( count($airports_dst) > max_num_airports_destination_country() ) {
         $arr_keys = array_rand($airports_dst, max_num_airports_destination_country());
         $arr_tmp = array();
         foreach($arr_keys as $airport_code) {
            $arr_tmp[$airport_code] = $airports_dst[$airport_code];
         }
         $airports_dst = $arr_tmp;
      }
   }
   else {
      foreach($airports_all as $code => $airport) {
         if ( $airport["city_code"] != $departure_city_code ) {
            $airports_dst[$code] = $airport;        
         } 
      }
      if ( empty($airports_dst) ) {
         return null;
      }
      if ( count($airports_dst) > max_num_airports_destination_any() ) {
         $arr_keys = array_rand($airports_dst, max_num_airports_destination_any());
         $arr_tmp = array();
         foreach($arr_keys as $airport_code) {
            $arr_tmp[$airport_code] = $airports_dst[$airport_code];
         }
         $airports_dst = $arr_tmp;
      }       
   }

   
   if ( isset($a["departure_city_code"]) ) {
      if ( isset($a["arrival_city_code"])
         && $a["departure_city_code"] == $a["arrival_city_code"] 
         ) {
         return null;
      }
      if ( isset($a["arrival_airport"]) ) {
         $airport = $airports_all[$a["arrival_airport"]];
         if ( $a["departure_city_code"] == $airport["city_code"] ) {
            return null;
         }
      }
   }
   
   $f_round_trip = isset($a["departure_date_rtn"]);

   
   $departure_date_rtn = $f_round_trip ? $a["departure_date_rtn"] : "";
   unset($a["departure_date_rtn"]);
   
   if ( empty($airports_src) || empty($airports_dst) ) {
      return null;
   }

   $arr_OWTs = array();
   $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT = array();
   $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_rtn = array();


   $flight_builder = new FlightBuilder_MD($fd, $airports_dst, $airports_src);
   $flight_builder->build($fd);
   $flight_builder->extract_found_flight_plans($fd);

   foreach( $flight_builder->arr_airport_code_dst_2_flight_builder_sd
      as $airport_code_dst => $flight_builder_sd ) {

      foreach( $flight_builder_sd->arr_airport_code_src_2_arr_OWT
         as $airport_code_src => $arr_one_way_trips ) {

         if ( !empty($arr_one_way_trips) ) {
            if ( !isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src]) ) {
               $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src] = array();
            }
            if ( !isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src][$airport_code_dst]) ) {
               $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src][$airport_code_dst] = array();
            }
            foreach($arr_one_way_trips as $owt) {
               $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src][$airport_code_dst][] = $owt;
               $arr_OWTs[] = $owt;
            }
         }
      }
   }

   if ( !$f_round_trip ) {
      return $arr_OWTs;
   }


   $flight_builder_rtn = new FlightBuilder_MD($fd, $airports_src, $airports_dst);
   $flight_builder_rtn->build($fd);
   $flight_builder_rtn->extract_found_flight_plans($fd);

   foreach( $flight_builder_rtn->arr_airport_code_dst_2_flight_builder_sd
      as $airport_code_src => $flight_builder_sd ) {

      foreach( $flight_builder_sd->arr_airport_code_src_2_arr_OWT
         as $airport_code_dst => $arr_one_way_trips_rtn ) {

         if ( !empty($arr_one_way_trips_rtn) ) {

            // we have return trips from $airport_code_dst to $airport_code_src
            // 
            if ( isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src])
               && !empty($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src][$airport_code_dst])
               ) {
               // we had a trip from $airport_code_src to $airport_code_dst


               if ( !isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_rtn[$airport_code_src]) ) {
                  $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_rtn[$airport_code_src] = array();
               }
               if ( !isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_rtn[$airport_code_src][$airport_code_dst]) ) {
                  $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_rtn[$airport_code_src][$airport_code_dst] = array();
               }
               foreach($arr_one_way_trips_rtn as $owt_rtn) {
                  $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_rtn[$airport_code_src][$airport_code_dst][] = $owt_rtn;
               }
            }
            else {
            }
         }
         else {
            if ( isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src])
               && isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src][$airport_code_dst])
               ) {
               unset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src][$airport_code_dst]);
            }
            if ( isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src])
               && empty($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src])
               ) {
               unset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT[$airport_code_src]);
            }
         }
      }
   }


   $arr_arprt_cd_src_dst_2_arr_OWT = array();
   $arr_arprt_cd_src_dst_2_arr_OWT_rtn = array();

   foreach($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT as 
      $airport_code_src => $arr_airport_code_dst_2_arr_OWT) {

      foreach($arr_airport_code_dst_2_arr_OWT 
         as $airport_code_dst => $arr_OWT ) {

         if ( !empty($arr_OWT) ) {
            
            assert( isset($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_ret[$airport_code_src]) );

            assert( !empty($arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_ret[$airport_code_src][$airport_code_dst]) );

            $key = $airport_code_src."_".$airport_code_dst;

            if ( !isset($arr_arprt_cd_src_dst_2_arr_OWT[$key]) ) {
               $arr_arprt_cd_src_dst_2_arr_OWT[$key] = array();
            }

            foreach($arr_OWT as $owt) {
               assert( $airport_code_src == $owt->departure_airport() );
               assert( $airport_code_dst == $owt->arrival_airport() );
               $arr_arprt_cd_src_dst_2_arr_OWT[$key][] = $owt;
            }

            $arr_OWT_ret = $arr_arprt_cd_src_2_arr_arprt_cd_dst_2_arr_OWT_ret[$airport_code_src][$airport_code_dst];
   
            if ( !isset($arr_arprt_cd_src_dst_2_arr_OWT_rtn[$key]) ) {
               $arr_arprt_cd_src_dst_2_arr_OWT_rtn[$key] = array();
            }
            foreach($arr_OWT_ret as $owt_ret) {
               assert( $airport_code_src == $owt_ret->arrival_airport() );
               assert( $airport_code_dst == $owt_ret->departure_airport() );
               $arr_arprt_cd_src_dst_2_arr_OWT_rtn[$key][] = $owt_ret;
            }
         }

      }
   }

   $arr_ret = array();
   foreach($arr_arprt_cd_src_dst_2_arr_OWT as $key => $arr) {
      assert(!empty($arr_arprt_cd_src_dst_2_arr_OWT_rtn[$key]));
      foreach($arr as $owt) {
         $arr_pair_2_add = array();
         $arr_pair_2_add["flight"] = $owt;
         foreach($arr_arprt_cd_src_dst_2_arr_OWT_rtn[$key] as $owt_rtn) {
            $arr_pair_2_add_x = $arr_pair_2_add; 
            $arr_pair_2_add_x["return"] = $owt_rtn;
            $arr_ret[] = $arr_pair_2_add_x;
         }
      }
   }
   return $arr_ret;
}


class OneWayTrip {
   
   private $arr_flights;
 
   public function __construct(array $arr_direct_flights) {
      $this->arr_flights = array();
      
      $prev = null;
      foreach($arr_direct_flights as $flight_id => $direct_flight) {
         if (is_null($prev)) {
            $direct_flight["total_price_prev"] = number_format(0, 2, '.', '');
            $direct_flight["total_minutes_prev"] = 0;
         }
         else {
            $mins_layover = 0;
            $f = self::can_connect($prev, $direct_flight, $mins_layover);
            assert($f);

            $total_price_prev = $prev["total_price_prev"] + $prev["price"];
            $direct_flight["total_price_prev"] = number_format($total_price_prev, 2, '.', '');
            
            $total_minutes_prev = $prev["total_minutes_prev"] + $prev["mins_duration"] + $mins_layover;
            $direct_flight["total_minutes_prev"] = $total_minutes_prev;
         }
         assert ( !isset($this->arr_flights[$flight_id]) );
         $this->arr_flights[$flight_id] = $direct_flight;
         $prev = $direct_flight;
      }
   }
   
   public function __toString() {
      $s = json_encode($this->arr_flights, JSON_PRETTY_PRINT);
      
//       $s = "\n".print_r($this->arr_flights, true);
      
      return $s;   
   }
   public function price() {
      return $this->last_leg()["total_price_prev"] + $this->last_leg()["price"];
   }
   public function arr_flights() {
      return $this->arr_flights;
   }
   public function num_legs() {
      return count($this->arr_flights);
   }
   
   public function first_leg() {
      return $this->arr_flights[array_keys($this->arr_flights)[0]];
   }
   
   public function last_leg() {
      return $this->arr_flights[array_keys($this->arr_flights)[count($this->arr_flights)-1]];
   }
   
   public function departure_airport() {
      return $this->first_leg()["departure_airport"];
   }

   public function arrival_airport() {
      return $this->last_leg()["arrival_airport"];
   }

//    public function departure_date_time() {
//       return $this->first_leg()["departure_date_time"];
//    }

//    public function arrival_date_time() {
//       return $this->last_leg()["arrival_date_time"];
//    }
   
   public static function can_connect(array $direct_flight1, array $direct_flight2,
      &$mins_layover=null) {

      if ( !is_null($mins_layover) ) {
         $mins_layover = 0;
      }
      if ( $direct_flight1["arrival_airport"] != $direct_flight2["departure_airport"] ) {
         return false;
      }
      $dt_arrival_flight1 = null;
      $dt_departure_flight2 = null;

//       if ( isset($direct_flight1["arrival_date_time"]) ) {
         
//          assert( isset($direct_flight2["departure_date_time"]) );

//          $dt_arrival_flight1 = $direct_flight1["arrival_date_time"];
//          $dt_departure_flight2 = $direct_flight2["departure_date_time"];
//       }
//       else {

         assert( isset($direct_flight1["arrival_time_UTC"]) );
         assert( isset($direct_flight2["departure_time_UTC"]) );

         $dt_now = new DateTime();
         $array = array();
         
         $dt_arrival_flight1 = date_time_arrival($direct_flight1,$array,$dt_now,true);
         $dt_departure_flight2 = date_time_departure($direct_flight2,$array,$dt_arrival_flight1,true);
//      }

      if ( $dt_departure_flight2 < $dt_arrival_flight1 ) {
         return false;
      }
      
      $ts_departure_flight2 = $dt_departure_flight2->getTimestamp();
      $ts_arrival_flight1 = $dt_arrival_flight1->getTimestamp();
      
      if ( $ts_departure_flight2 < $ts_arrival_flight1 ) {
         return false;
      }
      
      $mins_layover = round((float)($ts_departure_flight2 - $ts_arrival_flight1)/60.0);
      
      if ( mins_max_layover() < $mins_layover ) {
         return false;
      }

      if ( $direct_flight1["airline"] == $direct_flight2["airline"]
          && $direct_flight1["number"] == $direct_flight2["number"]
         ) {
         return true;      
      }
      
      if ( $mins_layover < mins_min_layover() ) {
         return false;
      }

      return true;
   }

   
   public function add_leg(array $direct_flight) {
      
      $last_leg = $this->last_leg();
      if ( !self::can_connect($last_leg, $direct_flight) ) {
          return false;   
      }
      $direct_flight["total_price_prev"] = number_format($this->price(), 2, '.', '');
      $flight_id = $direct_flight["flight_id"];
      assert(!empty($flight_id));
      assert(!isset($this->arr_flights[$flight_id]));
      $this->arr_flights[$flight_id] = $direct_flight;
      return true;
   }
   
   public function prefix_leg(array $direct_flight) {
      $first_leg = $this->first_leg();
      assert ( self::can_connect($direct_flight, $first_leg) );
      
      $arr_flights_new = array();
      $direct_flight["total_price_prev"] = number_format(0, 2, '.', '');
      $flight_id = $direct_flight["flight_id"];
      $arr_flights_new[$flight_id] = $direct_flight;
      
      foreach($this->arr_flights as $flight_id => $flight) {
         assert ( !isset($arr_flights_new[$flight_id]) );
         $flight["total_price_prev"] += $direct_flight["price"];
         $arr_flights_new[$flight_id] = $flight; 
      }
      $this->arr_flights = $arr_flights_new;
   }

   public function flight_duration() {
      
      $first_leg = $this->first_leg();
      $last_leg = $this->last_leg();
      
      $ts_arrival = $last_leg["ts_arrival"];
      $ts_departure = $first_leg["ts_departure"];
      
      assert( $ts_departure < $ts_arrival );
      
      return round((float)($ts_arrival - $ts_departure)/60.0);
   }

   public function s_departure_date() {
      $first_leg = $this->first_leg();
      return dt_2_s_date($first_leg["departure_date_time"]);
   }

//    // return 1 if departure dates are the same and
//    // one-way-trip $owt1 seems clearly better than one-way-trip $owt2
//    public static function cmp(OneWayTrip $owt1, OneWayTrip $owt2) {
      
//       if ( $owt1->s_departure_date() != $owt2->s_departure_date() ) {
//          return 0;
//       }
//       if ( $owt1->departure_airport() != $owt2->departure_airport() ) {
//          return 0;   
//       }
//       if ( $owt1->arrival_airport() != $owt2->arrival_airport() ) {
//          return 0;   
//       }
      
//       if ( $owt1->price() < $owt2->price() 
//          && $owt1->flight_duration() <= $owt2->flight_duration()
//          ||
//          $owt1->price() <= $owt2->price() 
//          && $owt1->flight_duration() < $owt2->flight_duration()
//          ) {
//          return 1;
//       }
//       if ( $owt2->price() < $owt1->price() 
//          && $owt2->flight_duration() <= $owt1->flight_duration()
//          ||
//          $owt2->price() <= $owt1->price() 
//          && $owt2->flight_duration() < $owt1->flight_duration()
//          ) {
//          return -1;
//       }
//       return 0;
//    }
}

//class ArrOneWayTrips {
//
//   private $arr_one_way_trips;
//
//   
//   public function __construct() {
//      $this->arr_one_way_trips = array();
//   }
//   
//   public function arr_one_way_trips($departure_airport_code="") {
//      
//      if ( !empty($departure_airport_code) ) {
//         return isset($this->arr_one_way_trips[$departure_airport_code]) ?
//            $this->arr_one_way_trips[$departure_airport_code] : array();
//      }
//      $arr_ret = array();
//      foreach($this->arr_one_way_trips as $airport_code => $arr) {
//         foreach($arr as $owt) {
//            $arr_ret[] = $owt;
//         }
//      }
//      return $arr_ret; 
//   }
//   
//   public function add_one_way_trip(OneWayTrip $owt) {
//      
//      $departure_airport_code = $owt->departure_airport();
//
//      if ( empty($this->arr_one_way_trips[$departure_airport_code]) ) {
//         $this->arr_one_way_trips[$departure_airport_code] = array();
//         $this->arr_one_way_trips[$departure_airport_code][] = $owt;
//         return;
//      }
//
//      $arr_to_remove = array();
//      foreach($this->arr_one_way_trips[$departure_airport_code] as $k => $owt_) {
//         if ( $owt_ == $owt ) {
//            return;   
//         }
//         $cmp = OneWayTrip::cmp($owt_, $owt);
//         if ( 1 == $cmp ) {
//            return;
//         }
//         if ( -1 == $cmp ) {
//            $arr_to_remove[] = $k;
//         }
//      }
//      if ( !empty($arr_to_remove) ) {
//         foreach($arr_to_remove as $k) {
//            unset($this->arr_one_way_trips[$departure_airport_code][$k]);
//         }
//         $this->arr_one_way_trips[$departure_airport_code] = 
//            array_values($this->arr_one_way_trips[$departure_airport_code]);
//      } 
//      $this->arr_one_way_trips[$departure_airport_code][] = $owt;
//   }
//   
//}

class Location {
   
   public $flight_id;
   
   public $min_cost_2_dst;
   public $min_time_2_dst;
   public $min_comb_2_dst;

   // arr_flight_ids_min_cost, e.g. is an array whose keys are flight id's, 
   // (where the associated values are the dummy value of 1), of THOSE 
   // flights, f, out which connect with the flight with flight_id, SUCH THAT
   // f is the next flight, in a sequence of flights, s, such that amongst all 
   // such sequences ( starting with flight with flight_id and which terminates
   // in a flight id for a flight whose arrival airport has code 'airport_code_dst',
   // where 'airport_code_dst' is the airport code of the (final) destination 
   // airport for this (search) location object
   // 
   public $arr_flight_ids_min_cost;
   public $arr_flight_ids_min_time;
   public $arr_flight_ids_min_comb;
   
   public function __construct($flight_id=null) {
      $this->flight_id = !empty($flight_id) ? $flight_id : "";
      $this->min_cost_2_dst = null;
      $this->min_time_2_dst = null;
      $this->min_comb_2_dst = null;
      $this->arr_flight_ids_min_cost = array();
      $this->arr_flight_ids_min_time = array();
      $this->arr_flight_ids_min_comb = array();
   }

   // "minutes to dollars" used for calculation the "comb" (combination) cost
   // which calculates the minimal (multi-stage) trip "cost" to
   // be the one for which the 'total price' + 'total trip time in minutes'
   // is minimal... i.e. with this function as it currently stands, we're 
   // essentially saying that, as far as finding an optimal trip plan wrt
   // 'comb' is concerned, "every hour saved is worth 60 dollars"
   // 
   public static function mins2price($mins) { 
      return $mins;
   }

   public function possibly_update(FlightData $fd, LOcation $loc_nxt) {

      assert( !empty($this->flight_id) );
      
      $flight_id_next = $loc_nxt->flight_id;
      assert ( isset($fd->arr_flights[$flight_id_next]) );
      assert ( isset($fd->arr_flights[$this->flight_id]) );

      $flight_next = $fd->arr_flights[$flight_id_next];
      $flight_curr = $fd->arr_flights[$this->flight_id];

      $mins_layover = 0;

      if ( !OneWayTrip::can_connect($flight_curr, $flight_next, $mins_layover) ) {
         return false;
      }

      $cost2dest = $loc_nxt->min_cost_2_dst + $flight_curr["price"];

      if ( !isset($this->min_cost_2_dst) ) {
         assert ( empty($this->arr_flight_ids_min_cost) );

         $this->min_cost_2_dst = $cost2dest;
         $this->arr_flight_ids_min_cost[$flight_id_next] = 1;
      }
      else {

         assert ( !empty($this->arr_flight_ids_min_cost) );

         $curr_min = $this->min_cost_2_dst;
         if ( $cost2dest < $curr_min ) {
            $this->min_cost_2_dst = $cost2dest;
            $this->arr_flight_ids_min_cost = array();
            $this->arr_flight_ids_min_cost[$flight_id_next] = 1;
         }
         elseif ( $cost2dest == $curr_min ) {
            $this->arr_flight_ids_min_cost[$flight_id_next] = 1;
         }
      }
      
      $time2dest = $loc_nxt->min_time_2_dst + $flight_curr["mins_duration"] + $mins_layover;  

      if ( !isset($this->min_time_2_dst) ) {
         assert ( empty($this->arr_flight_ids_min_time) );

         $this->min_time_2_dst = $time2dest;
         $this->arr_flight_ids_min_time[$flight_id_next] = 1;
      }
      else {

         assert ( !empty($this->arr_flight_ids_min_time) );

         $curr_min = $this->min_time_2_dst;
         if ( $time2dest < $curr_min ) {
            $this->min_time_2_dst = $time2dest;
            $this->arr_flight_ids_min_time = array();
            $this->arr_flight_ids_min_time[$flight_id_next] = 1;
         }
         elseif ( $time2dest == $curr_min ) {
            $this->arr_flight_ids_min_time[$flight_id_next] = 1;
         }
      }  

      $comb2dest = $loc_nxt->min_comb_2_dst + $flight_curr["price"]
         + self::mins2price($flight_curr["mins_duration"] + $mins_layover);

      if ( !isset($this->min_comb_2_dst) ) {
         assert ( empty($this->arr_flight_ids_min_comb) );

         $this->min_comb_2_dst = $comb2dest;
         $this->arr_flight_ids_min_comb[$flight_id_next] = 1;
      }
      else {

         assert ( !empty($this->arr_flight_ids_min_comb) );

         $curr_min = $this->min_comb_2_dst;
         if ( $comb2dest < $curr_min ) {
            $this->min_comb_2_dst = $comb2dest;
            $this->arr_flight_ids_min_comb = array();
            $this->arr_flight_ids_min_comb[$flight_id_next] = 1;
         }
         elseif ( $comb2dest == $curr_min ) {
            $this->arr_flight_ids_min_comb[$flight_id_next] = 1;
         }
      }
      return true;  
   }


   public function fill_final(FlightData $fd) {

      assert( !empty($this->flight_id) );
      assert ( isset($fd->arr_flights[$this->flight_id]) );

      $flight_curr = $fd->arr_flights[$this->flight_id];

      assert ( empty($this->min_cost_2_dst) );
      $this->min_cost_2_dst = $flight_curr["price"];

      assert ( empty($this->min_time_2_dst) );
      $this->min_time_2_dst = $flight_curr["mins_duration"];

      assert ( empty($this->min_comb_2_dst) );
      $this->min_comb_2_dst = $flight_curr["price"] + self::mins2price($flight_curr["mins_duration"]);
   }


}

class LocationLayer {
   public $arrLocations;
   public function __construct(array $arrLocations=null) {
      $this->arrLocations = is_null($arrLocations) ? array() : $arrLocations;
   }
}

class FlightBuilder_SD { // 'SD' for 'Single Destination' 
   public $airport_code_dst;
   
   public $arr_airport_codes_src;
   
   public $arr_airport_codes_src_2_min_cost;
   public $arr_airport_codes_src_2_min_time;
   public $arr_airport_codes_src_2_min_comb;

   public $arr_airport_codes_src_2_arr_flight_ids_min_cost;
   public $arr_airport_codes_src_2_arr_flight_ids_min_time;
   public $arr_airport_codes_src_2_arr_flight_ids_min_comb;

   public $max_legs;
   public $arr_location_layers;
   public $arr_flight_ids_src;
   public $arr_airport_code_src_2_arr_OWT;

   public function __construct(FlightData $fd, $airport_code_dst, array $arr_airport_codes_src, $max_legs=0) {
      assert ( !empty($airport_code_dst) );
      assert ( !empty($arr_airport_codes_src) );
      assert ( !empty($fd->arr_airports) );
      assert ( !empty($fd->arr_airports[$airport_code_dst]) );
      assert ( !empty($fd->arr_airport_code_dst2flight) );
      assert ( !empty($fd->arr_airport_code_dst2flight[$airport_code_dst]) );

      $this->airport_code_dst = $airport_code_dst;
      
      $this->arr_airport_codes_src = array();
      
      foreach($arr_airport_codes_src as $airport_code_src => $nop) {
         assert ( !empty($fd->arr_airports[$airport_code_src]) );
         assert ( !empty($fd->arr_airport_code_src2flight[$airport_code_src]) );
         
         if ( !empty($fd->arr_airport_code_src2flight[$airport_code_src]) ) {
            $this->arr_airport_codes_src[$airport_code_src] = 1;
         }
      }
      $arr_airport_codes_src_v = array_keys($arr_airport_codes_src);

      $this->arr_airport_codes_src_2_min_cost = 
         array_fill_keys($arr_airport_codes_src_v, INF);
      $this->arr_airport_codes_src_2_min_time = 
         array_fill_keys($arr_airport_codes_src_v, INF);
      $this->arr_airport_codes_src_2_min_comb = 
         array_fill_keys($arr_airport_codes_src_v, INF);


      $this->arr_airport_codes_src_2_arr_flight_ids_min_cost = 
         array_fill_keys($arr_airport_codes_src_v, array());
      $this->arr_airport_codes_src_2_arr_flight_ids_min_time =  
         array_fill_keys($arr_airport_codes_src_v, array());
      $this->arr_airport_codes_src_2_arr_flight_ids_min_comb = 
         array_fill_keys($arr_airport_codes_src_v, array());


      $this->max_legs = empty($max_legs) ? max_num_legs() : $max_legs;
      $this->arr_location_layers = array();
      $this->arr_flight_ids_src = array();
      $this->arr_airport_code_src_2_arr_OWT = array();
   }

   public function update_opt_src2dst_data(FlightData $fd, Location $loc) {
      
      $flight_id = $loc->flight_id;
      assert( !empty($flight_id) );

      assert( !empty($fd->arr_flights) );
      assert( !empty($fd->arr_flights[$flight_id]) );

      $arr_flight_nfo = $fd->arr_flights[$flight_id];

      assert( !empty($arr_flight_nfo["departure_airport"]) );
      $departure_airport = $arr_flight_nfo["departure_airport"];
      
      if ( !isset($this->arr_airport_codes_src[$departure_airport]) ) {
         return false;
      }
      $this->arr_flight_ids_src[$flight_id] = 1;

      $airport_code_src = $departure_airport;

      assert ( isset($this->arr_airport_codes_src_2_min_cost[$airport_code_src]) );
      assert ( isset($this->arr_airport_codes_src_2_min_time[$airport_code_src]) );
      assert ( isset($this->arr_airport_codes_src_2_min_comb[$airport_code_src]) );

      assert ( isset($this->arr_airport_codes_src_2_arr_flight_ids_min_cost[$airport_code_src]) );
      assert ( isset($this->arr_airport_codes_src_2_arr_flight_ids_min_time[$airport_code_src]) );
      assert ( isset($this->arr_airport_codes_src_2_arr_flight_ids_min_comb[$airport_code_src]) );


      $price = $loc->min_cost_2_dst;
      $time = $loc->min_time_2_dst;
      $comb = $loc->min_comb_2_dst;

      if ( $price < $this->arr_airport_codes_src_2_min_cost[$airport_code_src] ) {
         $this->arr_airport_codes_src_2_min_cost[$airport_code_src] = $price;
         $this->arr_airport_codes_src_2_arr_flight_ids_min_cost[$airport_code_src] = array();
         $this->arr_airport_codes_src_2_arr_flight_ids_min_cost[$airport_code_src][$flight_id] = 1;
      }
      elseif ( $price == $this->arr_airport_codes_src_2_min_cost[$airport_code_src] ) {
         $this->arr_airport_codes_src_2_arr_flight_ids_min_cost[$airport_code_src][$flight_id] = 1;
      }

      if ( $time < $this->arr_airport_codes_src_2_min_time[$airport_code_src] ) {
         $this->arr_airport_codes_src_2_min_time[$airport_code_src] = $time;
         $this->arr_airport_codes_src_2_arr_flight_ids_min_time[$airport_code_src] = array();
         $this->arr_airport_codes_src_2_arr_flight_ids_min_time[$airport_code_src][$flight_id] = 1;
      }
      elseif ( $time == $this->arr_airport_codes_src_2_min_time[$airport_code_src] ) {
         $this->arr_airport_codes_src_2_arr_flight_ids_min_time[$airport_code_src][$flight_id] = 1;
      }

      if ( $comb < $this->arr_airport_codes_src_2_min_comb[$airport_code_src] ) {
         $this->arr_airport_codes_src_2_min_comb[$airport_code_src] = $comb;
         $this->arr_airport_codes_src_2_arr_flight_ids_min_comb[$airport_code_src] = array();
         $this->arr_airport_codes_src_2_arr_flight_ids_min_comb[$airport_code_src][$flight_id] = 1;
      }
      elseif ( $comb == $this->arr_airport_codes_src_2_min_comb[$airport_code_src] ) {
         $this->arr_airport_codes_src_2_arr_flight_ids_min_comb[$airport_code_src][$flight_id] = 1;
      }
      return true;
   }
   
   public function build(FlightData $fd) {
      
      assert ( is_numeric($this->max_legs) && 1 <= $this->max_legs );

      if ( 1 == $this->max_legs ) {

         $arr_flights2dst = $fd->arr_airport_code_dst2flight[$this->airport_code_dst];

         foreach($arr_flights2dst as $flight_id => $arr_flight_nfo) {

            $loc = new Location($flight_id);
            $loc->fill_final($fd);

            $this->update_opt_src2dst_data($fd, $loc);
         }

         return;
      }

      for($i=0; $i < $this->max_legs; $i++) {
         $f = $this->add_location_layer($fd);
         if ( !$f ) {
            return;
         }
      }
   }

   //public function extract_one_way_flight_min_cost(FlightData $fd, $flight_id) {
   //
   //   $layer_idx = $this->location_layer_idx_4_flight_id($flight_id);
   //   assert( isset($this->arr_location_layers[$layer_idx]) );
   //   assert( isset($this->arr_location_layers[$layer_idx]->arrLocations[$flight_id]) );
   //   
   //   $loc = $this->arr_location_layers[$layer_idx]-.arrLocations[$flight_id];
   //
   //   if ( 0 == $layer_idx ) {
   //      assert( empty($loc->arr_flight_ids_min_cost) );
   //
   //      $arr_flights = array();
   //      $arr_flights[$flight_id] = $fd->arr_flights[$flight_id];
   //
   //      return new OneWayTrip($arr_flights);
   //   }
   //   assert( !empty($loc->arr_flight_ids_min_cost) );
   //   
   //   // for now, simply select the first min cost flight option
   //   $flight_id_nxt = array_keys($loc->arr_flight_ids_min_cost)[0];
   //
   //   $layer_idx_nxt = $this->location_layer_idx_4_flight_id($flight_id_nxt);
   //   assert( isset($this->arr_location_layers[$layer_idx_nxt]) );
   //   assert( isset($this->arr_location_layers[$layer_idx_nxt][$flight_id_nxt]) );
   //   assert( $layer_idx_nxt + 1 == $layer_idx );
   //   
   //   $ret = $this->extract_one_way_flight_min_cost($fd, $flight_id_nxt);
   //   
   //   $first_leg = $ret->first_leg();
   //   $direct_flight = $fd->arr_flights[$flight_id];
   //
   //   assert ( self::can_connect($direct_flight, $first_leg) );
   //
   //   $ret->prefix_leg($direct_flight);
   //   return $ret;
   //}
   //
   //public function extract_one_way_flight_min_time(FlightData $fd, $flight_id) {
   //
   //   $layer_idx = $this->location_layer_idx_4_flight_id($flight_id);
   //   assert( isset($this->arr_location_layers[$layer_idx]) );
   //   assert( isset($this->arr_location_layers[$layer_idx]->arrLocations[$flight_id]) );
   //   
   //   $loc = $this->arr_location_layers[$layer_idx]->arrLocations[$flight_id];
   //
   //   if ( 0 == $layer_idx ) {
   //      assert( empty($loc->arr_flight_ids_min_time) );
   //
   //      $arr_flights = array();
   //      $arr_flights[$flight_id] = $fd->arr_flights[$flight_id];
   //
   //      return new OneWayTrip($arr_flights);
   //   }
   //   assert( !empty($loc->arr_flight_ids_min_time) );
   //   
   //   // for now, simply select the first min cost flight option
   //   $flight_id_nxt = array_keys($loc->arr_flight_ids_min_time)[0];
   //
   //   $layer_idx_nxt = $this->location_layer_idx_4_flight_id($flight_id_nxt);
   //   assert( isset($this->arr_location_layers[$layer_idx_nxt]) );
   //   assert( isset($this->arr_location_layers[$layer_idx_nxt][$flight_id_nxt]) );
   //   assert( $layer_idx_nxt + 1 == $layer_idx );
   //   
   //   $ret = $this->extract_one_way_flight_min_time($fd, $flight_id_nxt);
   //   
   //   $first_leg = $ret->first_leg();
   //   $direct_flight = $fd->arr_flights[$flight_id];
   //
   //   assert ( self::can_connect($direct_flight, $first_leg) );
   //
   //   $ret->prefix_leg($direct_flight);
   //   return $ret;
   //}
   //
   //public function extract_one_way_flight_min_comb(FlightData $fd, $flight_id) {
   //
   //   $layer_idx = $this->location_layer_idx_4_flight_id($flight_id);
   //   assert( isset($this->arr_location_layers[$layer_idx]) );
   //   assert( isset($this->arr_location_layers[$layer_idx]->arrLocations[$flight_id]) );
   //   
   //   $loc = $this->arr_location_layers[$layer_idx]->arrLocations[$flight_id];
   //
   //   if ( 0 == $layer_idx ) {
   //      assert( empty($loc->arr_flight_ids_min_comb) );
   //
   //      $arr_flights = array();
   //      $arr_flights[$flight_id] = $fd->arr_flights[$flight_id];
   //
   //      return new OneWayTrip($arr_flights);
   //   }
   //   assert( !empty($loc->arr_flight_ids_min_comb) );
   //   
   //   // for now, simply select the first min cost flight option
   //   $flight_id_nxt = array_keys($loc->arr_flight_ids_min_comb)[0];
   //
   //   $layer_idx_nxt = $this->location_layer_idx_4_flight_id($flight_id_nxt);
   //   assert( isset($this->arr_location_layers[$layer_idx_nxt]) );
   //   assert( isset($this->arr_location_layers[$layer_idx_nxt][$flight_id_nxt]) );
   //   assert( $layer_idx_nxt + 1 == $layer_idx );
   //   
   //   $ret = $this->extract_one_way_flight_min_comb($fd, $flight_id_nxt);
   //   
   //   $first_leg = $ret->first_leg();
   //   $direct_flight = $fd->arr_flights[$flight_id];
   //
   //   assert ( self::can_connect($direct_flight, $first_leg) );
   //
   //   $ret->prefix_leg($direct_flight);
   //   return $ret;
   //}


   public function extract_one_way_flight_min_key(FlightData $fd, $flight_id, $key) {

      assert( "cost" == $key || "time" == $key || "comb" == $key );

      $layer_idx = $this->location_layer_idx_4_flight_id($flight_id);

      $memb = "arr_flight_ids_min_".$key;

      $arr_flights = array();
      
      while( 0 < $layer_idx ) {

         assert( isset($this->arr_location_layers[$layer_idx]) );
         assert( isset($this->arr_location_layers[$layer_idx]->arrLocations[$flight_id]) );

         $loc = $this->arr_location_layers[$layer_idx]->arrLocations[$flight_id];

         assert( !empty($fd->arr_flights[$flight_id]) );

         assert( !isset($arr_flights[$flight_id]) );

         $arr_flights[$flight_id] = $fd->arr_flights[$flight_id];
         
         assert( !empty($loc->$memb) );

         // for now, simply select the first min cost flight option
         $flight_id_nxt = array_keys($loc->$memb)[0];

         $layer_idx_nxt = $this->location_layer_idx_4_flight_id($flight_id_nxt);
         assert( $layer_idx_nxt + 1 == $layer_idx );

         $layer_idx = $layer_idx_nxt;
         $flight_id = $flight_id_nxt;
      }
      assert ( 0 == $layer_idx );

      assert( isset($this->arr_location_layers[$layer_idx]) );
      assert( isset($this->arr_location_layers[$layer_idx]->arrLocations[$flight_id]) );

      $loc = $this->arr_location_layers[$layer_idx]->arrLocations[$flight_id];

      assert( empty($loc->$memb) );

      assert( !empty($fd->arr_flights[$flight_id]) );

      assert( !isset($arr_flights[$flight_id]) );

      $arr_flights[$flight_id] = $fd->arr_flights[$flight_id];

      return new OneWayTrip($arr_flights);
   }


   public function extract_found_flight_plans(FlightData $fd) {
      
      if ( 1 == $this->max_legs ) {

         $arr_flights2dst = $fd->arr_airport_code_dst2flight[$this->airport_code_dst];

         $arr_flight_ids = array();

         foreach($this->arr_airport_codes_src_2_arr_flight_ids_min_cost as $airport_code_src => $arr_flight_ids_min_cost) {
            if ( !empty($arr_flight_ids_min_cost) ) {
            
               if ( !isset($this->arr_airport_code_src_2_arr_OWT[$airport_code_src]) ) {
                  $this->arr_airport_code_src_2_arr_OWT[$airport_code_src] = array();
               }
               foreach($arr_flight_ids_min_cost as $flight_id => $nop) {

                  $arr_flight_ids[$flight_id] = 1;

                  $arr_flights = array();
                  $arr_flights[$flight_id] = $arr_flights2dst[$flight_id];

                  $this->arr_airport_code_src_2_arr_OWT[$airport_code_src][] 
                     = new OneWayTrip($arr_flights);
               }
               
            }
         }

         foreach($this->arr_airport_codes_src_2_arr_flight_ids_min_time as $airport_code_src => $arr_flight_ids_min_time) {
            
            if ( !empty($arr_flight_ids_min_time) ) {
            
               if ( !isset($this->arr_airport_code_src_2_arr_OWT[$airport_code_src]) ) {
                  $this->arr_airport_code_src_2_arr_OWT[$airport_code_src] = array();
               }

               foreach($arr_flight_ids_min_time as $flight_id => $nop) {

                  if ( !isset($arr_flight_ids[$flight_id]) ) {

                     $arr_flight_ids[$flight_id] = 1;
                     
                     $arr_flights = array();
                     $arr_flights[$flight_id] = $arr_flights2dst[$flight_id];

                     $this->arr_airport_code_src_2_arr_OWT[$airport_code_src][]
                        = new OneWayTrip($arr_flights);
                  }
               }
            }
         }

         foreach($this->arr_airport_codes_src_2_arr_flight_ids_min_comb as $airport_code_src => $arr_flight_ids_min_comb) {

            if ( !empty($arr_flight_ids_min_comb) ) {
            
               if ( !isset($this->arr_airport_code_src_2_arr_OWT[$airport_code_src]) ) {
                  $this->arr_airport_code_src_2_arr_OWT[$airport_code_src] = array();
               }

               foreach($arr_flight_ids_min_comb as $flight_id => $nop) {

                  if ( !isset($arr_flight_ids[$flight_id]) ) {

                     $arr_flight_ids[$flight_id] = 1;
                     
                     $arr_flights = array();
                     $arr_flights[$flight_id] = $arr_flights2dst[$flight_id];

                     $this->arr_airport_code_src_2_arr_OWT[$airport_code_src][]
                        = new OneWayTrip($arr_flights);
                  }
               }
            }
         }
         return;
      }

      foreach($this->arr_airport_codes_src_2_arr_flight_ids_min_cost as $airport_code_src => $arr_flight_ids_min_cost) {
         if ( !empty($arr_flight_ids_min_cost) ) {
         
            if ( !isset($this->arr_airport_code_src_2_arr_OWT[$airport_code_src]) ) {
               $this->arr_airport_code_src_2_arr_OWT[$airport_code_src] = array();
            }

            // for now, simply select the first min cost flight option
            $flight_id = array_keys($arr_flight_ids_min_cost)[0];
            //$owt = $this->extract_one_way_flight_min_cost($fd, $flight_id);

            $owt = $this->extract_one_way_flight_min_key($fd, $flight_id, "cost");
            
            $f_redundant = false;
            foreach($this->arr_airport_code_src_2_arr_OWT[$airport_code_src] as $owt_) {
               if ( $owt == $owt_ ) {
                  $f_redundant = true;
                  break;
               }
            }
            if ( !$f_redundant ) {
               $this->arr_airport_code_src_2_arr_OWT[$airport_code_src][]
                  = $owt;
            }   
         }
      }

      foreach($this->arr_airport_codes_src_2_arr_flight_ids_min_time as $airport_code_src => $arr_flight_ids_min_time) {
         
         if ( !empty($arr_flight_ids_min_time) ) {
         
            if ( !isset($this->arr_airport_code_src_2_arr_OWT[$airport_code_src]) ) {
               $this->arr_airport_code_src_2_arr_OWT[$airport_code_src] = array();
            }

            // for now, simply select the first min cost flight option
            $flight_id = array_keys($arr_flight_ids_min_time)[0];
            //$owt = $this->extract_one_way_flight_min_time($fd, $flight_id);

            $owt = $this->extract_one_way_flight_min_key($fd, $flight_id, "time");

            $f_redundant = false;
            foreach($this->arr_airport_code_src_2_arr_OWT[$airport_code_src] as $owt_) {
               if ( $owt == $owt_ ) {
                  $f_redundant = true;
                  break;
               }
            }
            if ( !$f_redundant ) {
               $this->arr_airport_code_src_2_arr_OWT[$airport_code_src][]
                  = $owt;
            }   
         }
      }

      foreach($this->arr_airport_codes_src_2_arr_flight_ids_min_comb as $airport_code_src => $arr_flight_ids_min_comb) {

         if ( !empty($arr_flight_ids_min_comb) ) {
         
            if ( !isset($this->arr_airport_code_src_2_arr_OWT[$airport_code_src]) ) {
               $this->arr_airport_code_src_2_arr_OWT[$airport_code_src] = array();
            }

            // for now, simply select the first min cost flight option
            $flight_id = array_keys($arr_flight_ids_min_comb)[0];
            //$owt = $this->extract_one_way_flight_min_comb($fd, $flight_id);

            $owt = $this->extract_one_way_flight_min_key($fd, $flight_id, "comb");


            $f_redundant = false;
            foreach($this->arr_airport_code_src_2_arr_OWT[$airport_code_src] as $owt_) {
               if ( $owt == $owt_ ) {
                  $f_redundant = true;
                  break;
               }
            }
            if ( !$f_redundant ) {
            
               $this->arr_airport_code_src_2_arr_OWT[$airport_code_src][]
                  = $owt;
            }      
         }
      }
   }

   public function add_location_layer(FlightData $fd) {
      $max_legs = $this->max_legs;
      if ( $max_legs <= 1 ) {
         return false;
      }
      $num_layers = count($this->arr_location_layers);
      if ( $num_layers >= $max_legs ) {
         return false;
      }
      if ( 0 == $num_layers ) {

         $arr_flights2dst = $fd->arr_airport_code_dst2flight[$this->airport_code_dst];

         $location_layer_0 = new LocationLayer();

         foreach( $arr_flights2dst as $flight_id => $arr_flight_nfo) {

            $loc = new Location($flight_id);
            $loc->fill_final($fd);

            $location_layer_0->arrLocations[$flight_id] = $loc;

            $this->update_opt_src2dst_data($fd, $loc);

         }
         $this->arr_location_layers[] = $location_layer_0;
         return true;
      }
      assert( 1 <= $num_layers );
      assert( $num_layers + 1 <= $max_legs );

      $f_final_layer = $num_layers + 1 == $max_legs;

      assert( $f_final_layer || 3 <= $max_legs );
      $last_layer = $this->arr_location_layers[$num_layers-1];
      if ( empty($last_layer->arrLocations) ) {
         return false;
      }

      $new_layer = new LocationLayer();

      $max_min_cost = 0;
      $max_min_time = 0;
      $max_min_comb = 0;
      
      foreach ($this->arr_airport_codes_src as $airport_code_src => $nop) {

         $max_min_cost = max($this->arr_airport_codes_src_2_min_cost[$airport_code_src],
            $max_min_cost);
         
         $max_min_time = max($this->arr_airport_codes_src_2_min_time[$airport_code_src],
            $max_min_time);

         $max_min_comb = max($this->arr_airport_codes_src_2_min_comb[$airport_code_src],
            $max_min_comb);
      }


      foreach($last_layer->arrLocations as $flight_id_nxt => $loc_nxt) {

         if ( isset($this->arr_flight_ids_src[$flight_id_nxt]) ) {
            // don't step back from, i.e. don't look for flights leading
            // to, a source flight, i.e. for flights leading to an initial
            // depature airport
            continue;
         }

         if ( $loc_nxt->min_cost_2_dst > $max_min_cost
            && $loc_nxt->min_time_2_dst > $max_min_time
            && $loc_nxt->min_comb_2_dst > $max_min_comb
            ) {
            // don't step back
            continue;
         }



         $flight_nfo_nxt = $fd->arr_flights[$flight_id_nxt];
         $departure_airport_nxt = $flight_nfo_nxt["departure_airport"];

         $arr_flights_in = $fd->arr_airport_code_dst2flight[$departure_airport_nxt];
         foreach($arr_flights_in as $flight_id => $flight_nfo) {

            if ( $f_final_layer ) {
               $departure_airport = $flight_nfo["departure_airport"];
               if ( !isset($this->arr_airport_codes_src[$departure_airport]) ) {
                  continue;
               }
            }
            
            $layer_idx = $this->location_layer_idx_4_flight_id($flight_id);
            if ( $layer_idx >= 0 ) {
               continue;   
            }
            $f_already_added = isset($new_layer->arrLocations[$flight_id]);
            $loc_new = $f_already_added ? $new_layer->arrLocations[$flight_id] :
               new Location($flight_id);

            $f = $loc_new->possibly_update($fd, $loc_nxt);
            if ( $f && !$f_already_added ) {

               if ( $loc_new->min_cost_2_dst > $max_min_cost
                  && $loc_new->min_time_2_dst > $max_min_time
                  && $loc_new->min_comb_2_dst > $max_min_comb
                  ) {
                  // don't add
               }
               else {
                  $new_layer->arrLocations[$flight_id] = $loc_new;
               }
            }
         }
      }
      foreach( $new_layer->arrLocations as $loc) {
         $this->update_opt_src2dst_data($fd, $loc);
      }               
      $this->arr_location_layers[] = $new_layer;
      return true;
   }

   public function location_layer_idx_4_flight_id($flight_id) {
      foreach($this->arr_location_layers as $index => $location_layer) {
         if ( isset($location_layer->arrLocations[$flight_id]) ) {
            return $index;
         }
      }
      return -1;
   }

}

class FlightBuilder_MD { // 'MD' for 'Multiple Destination' 
   
   public $arr_airport_code_dst_2_flight_builder_sd;

   public function __construct(FlightData $fd, array $arr_airport_codes_dst, 
      array $arr_airport_codes_src, $max_legs=0) {

      assert ( !empty($arr_airport_codes_dst) );
      assert ( !empty($arr_airport_codes_src) );
      assert ( !empty($fd->arr_airports) );

      $this->arr_airport_code_dst_2_flight_builder_sd = array();
      foreach($arr_airport_codes_dst as $airport_code_dst => $nop) {

         assert ( !empty($fd->arr_airports[$airport_code_dst]) );
         assert ( !empty($fd->arr_airport_code_dst2flight[$airport_code_dst]) );
         
         if ( !empty($fd->arr_airport_code_dst2flight[$airport_code_dst]) ) {
            $this->arr_airport_code_dst_2_flight_builder_sd[$airport_code_dst] = 
               new FlightBuilder_SD($fd, $airport_code_dst, $arr_airport_codes_src, $max_legs);
         }

      }

   }
   public function build(FlightData $fd) {
      foreach($this->arr_airport_code_dst_2_flight_builder_sd
         as $airport_code_dst => $flight_builder_sd) {
         $flight_builder_sd->build($fd);
      }
   }

   public function extract_found_flight_plans(FlightData $fd) {
      foreach($this->arr_airport_code_dst_2_flight_builder_sd
         as $airport_code_dst => $flight_builder_sd) {
         $flight_builder_sd->extract_found_flight_plans($fd);
      }
   }

}




class FlightData {
   
   public $arr_airlines;
   public $arr_city_code2arr_airports;
   public $arr_airports;

   public $arr_flights;
   public $arr_airport_code_src2flight;
   public $arr_airport_code_dst2flight;

   public function __construct() {
      $this->arr_airlines = array();
      $this->arr_airports = array();
      $this->arr_city_code2arr_airports = array();
      $this->arr_flights = array();
      $this->arr_airport_code_src2flight = array();
      $this->arr_airport_code_dst2flight = array();


      $mc = new MongoClient();
      $db_name = db_name();
      $db = $mc->$db_name;

      $collection = $db->airlines;
      $cursor = $collection->find();
      foreach($cursor as $document) {
         $code = $document["code"];
         assert( !isset($this->arr_airlines[$code]));
         $this->arr_airlines[$code] = $document["name"];
      }

      $collection = $db->airports;
      $cursor = $collection->find();
      foreach($cursor as $document) {

         assert( !empty($document["code"]) );
         assert( !empty($document["city_code"]) );
         assert( !empty($document["region_code"]) );
         assert( !empty($document["country_code"]) );

         $code = $document["code"];
         $city_code = $document["city_code"];

         assert( !isset($this->arr_airlines[$code]) );
         $this->arr_airports[$code] = $document;

         if ( !isset($this->arr_city_code2arr_airports[$city_code]) ) {
            $this->arr_city_code2arr_airports[$city_code] = array();
         }
         assert( !isset($this->arr_city_code2arr_airports[$city_code][$code]) );
         $this->arr_city_code2arr_airports[$city_code][$code] = $document;
      }


      $collection = $db->flights;
      $cursor = $collection->find();
      foreach($cursor as $document) {

         assert( !empty($document["airline"]) );
         assert( !empty($document["number"]) );
         assert( !empty($document["departure_airport"]) );
         assert( !empty($document["departure_time"]) );
         assert( !empty($document["arrival_airport"]) );
         assert( !empty($document["arrival_time"]) );
         assert( !empty($document["price"]) );

         $date_time = new DateTime();

         $dt_departure = date_time_departure($document, $this->arr_airports, $date_time);
         $dt_arrival = date_time_arrival($document, $this->arr_airports, $dt_departure);
         
         $ts_departure = $dt_departure->getTimestamp();
         $ts_arrival = $dt_arrival->getTimestamp();
         

         $mins_duration = round((float)($ts_arrival-$ts_departure)/60.0);
         
         assert( !empty($this->arr_airports[$document["departure_airport"]]) );
         assert( !empty($this->arr_airports[$document["arrival_airport"]]) );
         
         $departure_airport = $this->arr_airports[$document["departure_airport"]];
         $arrival_airport = $this->arr_airports[$document["arrival_airport"]];

         assert( !empty($departure_airport["latitude"]) );
         assert( !empty($departure_airport["longitude"]) );
         
         assert( !empty($arrival_airport["latitude"]) );
         assert( !empty($arrival_airport["longitude"]) );
         
         
         $lat1 = $departure_airport["latitude"];
         $lon1 = $departure_airport["longitude"];
         
         $lat2 = $arrival_airport["latitude"];
         $lon2 = $arrival_airport["longitude"];
         
         $min_aprox_time = min_aprox_time($lat1, $lon1, $lat2, $lon2);
         while($mins_duration < $min_aprox_time) {
            $dt_arrival->modify("+1 day");
            $ts_arrival = $dt_arrival->getTimestamp();
            $mins_duration = round((float)($ts_arrival-$ts_departure)/60.0);  
         }

         $document["mins_duration"] = $mins_duration;
                     
         $dt_departure->setTimezone(new DateTimeZone("UTC"));
         $dt_arrival->setTimezone(new DateTimeZone("UTC"));

         $document["departure_time_UTC"] = $dt_departure->format(s_time_fmt(false));
         $document["arrival_time_UTC"] = $dt_arrival->format(s_time_fmt(false));


         $flight_id = $document["airline"].":".$document["number"].":".$document["departure_airport"];

         $document["flight_id"] = $flight_id;

         assert( !isset($this->arr_flights[$flight_id]) );
         $this->arr_flights[$flight_id] = $document;

         $departure_airport = $document["departure_airport"];
         assert( isset($this->arr_airports[$departure_airport]) ) ;
         if ( !isset($this->arr_airport_code_src2flight[$departure_airport]) ) {
            $this->arr_airport_code_src2flight[$departure_airport] = array();
         }
         assert( !isset($this->arr_airport_code_src2flight[$departure_airport][$flight_id]) );
         $this->arr_airport_code_src2flight[$departure_airport][$flight_id] = $document;


         $arrival_airport = $document["arrival_airport"];
         assert( isset($this->arr_airports[$arrival_airport]) ) ;
         if ( !isset($this->arr_airport_code_dst2flight[$arrival_airport]) ) {
            $this->arr_airport_code_dst2flight[$arrival_airport] = array();
         }
         assert( !isset($this->arr_airport_code_dst2flight[$arrival_airport][$flight_id]) );
         $this->arr_airport_code_dst2flight[$arrival_airport][$flight_id] = $document;
      }
   }
}

// class SearchData {
//    public $arr_airport_codes_src;
//    public $arr_airport_codes_dst;

//    public function __construct(array $arr_airport_codes_src, array $arr_airport_codes_dst) {
//       $this->arr_airport_codes_src = $arr_airport_codes_src;
//       $this->arr_airport_codes_dst = $arr_airport_codes_dst;
//    }
// }



function min_aprox_distance($lat1, $lon1, $lat2, $lon2) {
  if (($lat1 == $lat2) && ($lon1 == $lon2)) {
    return 0;
  }
  else {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return $dist * 111.18957696; 
  }
}

function min_aprox_time($lat1, $lon1, $lat2, $lon2) {
   $k = min_aprox_distance($lat1, $lon1, $lat2, $lon2);
   return round(60.0*$k/1234.8);
}


?>