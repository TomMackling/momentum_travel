<?php

date_default_timezone_set("UTC");

function db_name() {
   return "flights_db";
}
function max_layovers() {
   return 5;
}

function mins_min_layover() {
   return 20;
}

function mins_max_layover() {
   return 60*3;
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

$departure_date = !empty($_REQUEST["departure_date"]) ? $_REQUEST["departure_date"] : "";
if ( empty($departure_date) ) {
//    $departure_date = strftime(s_date_fmt());
   
   $s_date = strftime(s_date_fmt());
   $s_time_utc = strftime(s_time_fmt());
   $date_time = new DateTime($s_date . " 00:00:00");
   
   //$time = $s_date.",".$s_time_utc;
   //$date_time = date_create_from_format(s_datetime_fmt(false), $time, new DateTimeZone("UTC"));
   $date_time->modify("+1 day");
   $departure_date = $date_time->format(s_date_fmt(false));
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

$a["s_date"] = $departure_date;
$a["s_time"] = "00:00";

if ($f_round_trip) {
   $a["departure_date_rtn"] = $departure_date_rtn;    
}


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

function direct_flights(array $a, array $airports=null) {

   if ( empty($airports) ) {
      $airports = airports();
   }
   $date_time = null;
   $s_date = null;	
   
   if ( !empty($a["date_time"]) ) {
       $date_time = $a["date_time"];	
       $s_date = $date_time->format(s_date_fmt(false));
   } 
   else {

      $s_date = !empty($a["s_date"]) ? $a["s_date"] : strftime(s_date_fmt());
      $s_time_utc = !empty($a["s_time"]) ? $a["s_time"] : strftime(s_time_fmt());

      $time = $s_date.",".$s_time_utc;
      $date_time = date_create_from_format(s_datetime_fmt(false), $time, new DateTimeZone("UTC")); 
   } 

   $flights = array();
   $mc = new MongoClient();
   $db_name = db_name();
   
   $db = $mc->$db_name;
   $collection = $db->flights;
   $cursor = $collection->find();
   foreach($cursor as $document) {
       if ( !empty($document["airline"])
           && !empty($document["number"])
           && !empty($document["departure_airport"])
           && !empty($document["departure_time"])
           && !empty($document["arrival_airport"])
           && !empty($document["arrival_time"])
           && !empty($document["price"])
          ) {
           if (  !empty($a["departure_airport"]) && $a["departure_airport"] != $document["departure_airport"]
              || !empty($a["arrival_airport"]) && $a["arrival_airport"] != $document["arrival_airport"]
              || !empty($a["price_max"]) && (float)$document["price"] > (float)$a["price_max"]		              
              ) {
               continue;
           }
           $dt = date_time_departure($document, $airports, $s_date);
           
           $dt_now_nextyear = new DateTime();
           $dt_now_nextyear->modify("+365 days");
           if ( $dt_now_nextyear < $dt ) {
               continue;
           } 
           
           if ( !is_null($date_time) ) {
               
               if ( $date_time > $dt ) {
                  continue;
               }
           }
           $document["departure_date_time"] = $dt;
            
           $dt_arrival = date_time_arrival($document, $airports, $s_date);
           if ( $dt_arrival < $dt ) {
               $dt_arrival->modify("+1 day");
           }
           $document["arrival_date_time"] = $dt_arrival;
           
           $flight_id = $document["airline"].":".$document["number"];
           $document["flight_id"] = $flight_id;
	   	
           $flights[$flight_id] = $document;		
       }    
   }
   return $flights;
}

function date_time_departure(array $a, array $airports, $s_date="") {
   $x = date_time_departure_arrival($a, "departure", $airports, $s_date);
   if (is_bool($x)) {
      echo "asdasd";
   }   
   return $x;
}

function date_time_arrival(array $a, array $airports, $s_date="") {
   return date_time_departure_arrival($a, "arrival", $airports, $s_date);
}

function date_time_departure_arrival(array $a, $prefix, array $airports, $s_date) {
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
    if ( empty($s_date) ) {
       $s_date = strftime("%Y-%m-%d");
    }
 
    $airport_info = $airports[$airport];
    if ( empty($airport_info["timezone"]) ) {
         return null;
    }

    $tz = new DateTimeZone($airport_info["timezone"]);
    $time = $s_date.",".$a["{$prefix}_time"];
    return date_create_from_format(s_datetime_fmt(false), $time, $tz);
} 


function flights_airport2airport(array $a, array $airports=null) {
   
   if ( empty($a["departure_airport"]) ) {
      return null;
   }
   if ( empty($a["arrival_airport"]) ) {
      return null;
   }

   if ( empty($airports) ) {
      $airports = airports();
   }

   $arr_direct_flights = direct_flights($a, $airports);

   $best_cost = INF;
   $cheapest_flight = null;
   
   $a_0 = $a;
   unset($a_0["arrival_airport"]);
   $a_1 = $a;
   unset($a_1["departure_airport"]);

   //$earliest_date = "";
   if ( !empty($arr_direct_flights) ) {
      $cheapest_flight = cheapest_flight($arr_direct_flights);
      $best_cost = $cheapest_flight["price"];
      $a_0["price_max"] = $best_cost;
      $a_1["price_max"] = $best_cost;
   }

   $arr_flights_out = direct_flights($a_0, $airports);
   $arr_flights_in = direct_flights($a_1, $airports);

   $out = new ArrOneWayTrips();
      
   foreach($arr_direct_flights as $flight_id => $flight) {
      unset($arr_flights_out[$flight_id]);
      unset($arr_flights_in[$flight_id]);
      
      $arr_flights = array();
      $arr_flights[$flight_id] = $flight;
      $owt = new OneWayTrip($arr_flights);
      $out->add_one_way_trip($owt);      
   }


   if ( max_layovers() >= 1 ) {
   
      foreach($arr_flights_out as $flight_out) {
   
         foreach($arr_flights_in as $flight_in) {      
   
            if ( OneWayTrip::can_connect($flight_out, $flight_in) ) {
               $arr_flights = array();
               $arr_flights[$flight_out["flight_id"]] = $flight_out;
               $arr_flights[$flight_in["flight_id"]] = $flight_in;
               $owt = new OneWayTrip($arr_flights);
               $out->add_one_way_trip($owt);
            }
         }
      }
   }
   
   if ( max_layovers() >= 2 ) { 
   
      $arrLocations2goal = array();
      $date_time = null;

      $date_time = null;
      $s_date = null;	
   
      if ( !empty($a["date_time"]) ) {
          $date_time = $a["date_time"];	
          $s_date = $date_time->format(s_date_fmt(false));
      } 
      else {
   
         $s_date = !empty($a["s_date"]) ? $a["s_date"] : strftime(s_date_fmt());
         $s_time_utc = !empty($a["s_time"]) ? $a["s_time"] : strftime(s_time_fmt());
   
         $time = $s_date.",".$s_time_utc;
         $date_time = date_create_from_format(s_datetime_fmt(false), $time, new DateTimeZone("UTC")); 
      } 
      
      $cost_so_far = number_format(0, 2, '.', '');
      $arrLocations2goal[] = new Location($a["departure_airport"], $date_time, $cost_so_far);
      
      $aowt_complete = new ArrOneWayTrips();
      
      flights_airport2airport_rec($arrLocations2goal, $a["arrival_airport"], $airports, max_layovers(), $out,
         $aowt_complete);
   }
   
//    filter($arr_out);
   return $out->arr_one_way_trips();    
}


function flights_airport2airport_rec(array $arrLocations2goal, $arrival_airport_code, array $airports, $max_legs, 
      ArrOneWayTrips $aowt, ArrOneWayTrips $aowt_complete) {
   
   if ( empty($arrival_airport_code) ) {
      return;
   }
   if ( 0 >= $max_legs ) {
      return;
   }
   $arrLocations2goal_keys2rmv = array();

   foreach($arrLocations2goal as $k => $location) {
      if ( $location->airport_code() == $arrival_airport_code ) {
         $arrLocations2goal_keys2rmv[$k] = 1;
      }
   }
   
   $arr_owt_complete = $aowt_complete->arr_one_way_trips();
   if ( !empty($arr_owt_complete) ) {
         
      foreach($arrLocations2goal as $k => $location) {
         if ( !isset($arrLocations2goal_keys2rmv[$k]) ) {
            foreach($arr_owt_complete as $owt) {
               if ( $owt->departure_airport() == $location->airport_code()
                  && $owt->departure_date_time() <= $location->date_time()   
                  ) {
                  $arrLocations2goal_keys2rmv[$k] = 1;               
               }
            }
         }      
      }
   }
        
   if ( !empty($arrLocations2goal_keys2rmv) ) {
      foreach($arrLocations2goal_keys2rmv as $k => $nop) {
         unset($arrLocations2goal[$k]);
      }
      $arrLocations2goal = array_values($arrLocations2goal);
   }
   
   if ( empty($arrLocations2goal) ) {
      return;
   }
   
   
   if ( 1 == $max_legs ) {
      
      foreach($arrLocations2goal as $location) {
         $a = array();
         $a["date_time"] = $location->date_time();
         $a["arrival_airport"] = $arrival_airport_code;
         $a["departure_airport"] = $location->airport_code();
         
         $arr_flights2location = $location->arr_flights2here();
         
         $arr_direct_flights = direct_flights($a, $airports);
      
         foreach($arr_direct_flights as $flight_id => $flight) {
            
            $arr_flights = $arr_flights2location;
            assert ( !isset($arr_flights[$flight_id]) );
            
            $arr_flights[$flight_id] = $flight;
                     
            $owt = new OneWayTrip($arr_flights);
            
            $aowt->add_one_way_trip($owt);
            
            $aowt_complete->add_one_way_trip($owt);
         }
      }   
      return;
   }
   $arrLocations2goal_cpy = $arrLocations2goal; 
   foreach($arrLocations2goal as $location) {
      
      $a = array();
      $a["date_time"] = $location->date_time();
      $a["departure_airport"] = $location->airport_code();
      
      $arr_flights2location = $location->arr_flights2here();
      
      $arr_flights_out = direct_flights($a, $airports);
   
      $arrLocations2goal_ = array();
      
      foreach($arr_flights_out as $flight_id => $flight) {

         $arr_flights = $arr_flights2location;
         assert ( !isset($arr_flights[$flight_id]) );
         
         $arr_flights[$flight_id] = $flight;
         
         $arrival_airport_ = $flight["arrival_airport"];
         if ( $arrival_airport_ == $arrival_airport_code ) {
                        
            $owt = new OneWayTrip($arr_flights);
            $aowt->add_one_way_trip($owt);
         }
         else {
            $f_dest_is_location2goal = false;
            foreach($arrLocations2goal_cpy as $location_) {
               if ( $location_->airport_code() == $arrival_airport_
                    && $location_->date_time() <= $flight["arrival_date_time"]
                    && $location_->cost_so_far() <= $location->cost_so_far() + $flight["price"]  
                   ) {
                  $f_dest_is_location2goal = true;
               }
            }
            if ( !$f_dest_is_location2goal ) {
               $arrLocations2goal_[] = new Location($arrival_airport_, $flight["arrival_date_time"], $location->cost_so_far() + $flight["price"],
                     $arr_flights);               
            }
         }
      }
      flights_airport2airport_rec($arrLocations2goal_, $arrival_airport_code, $airports, $max_legs-1, $aowt, $aowt_complete);
   }   
   
}



function flights(array $a, array $airports=null) {

   $airports_src = array();
   $airports_dst = array();
   
   if ( !isset($a["departure_city_code"]) 
     && !isset($a["departure_airport"])
      ) {
      return null;
   }
   $airports_all = airports();
   $departure_city_code = "";
   if ( isset($a["departure_airport"]) ) {
      
      unset($a["departure_city_code"]);
      $s_departure_airport = $a["departure_airport"];
      if ( !isset($airports_all[$s_departure_airport]) ) {
         return null;
      }
      $airports_src[$s_departure_airport] = $airports_all[$s_departure_airport];   
      $departure_city_code = $airports_src[$s_departure_airport]["city_code"];
   }
   else {
      $a_src = $a;
      $a_src["city_code"] = $a["departure_city_code"];
      $departure_city_code = $a_src["city_code"];
      unset($a_src["departure_city_code"]);   
      $airports_src = airports($a_src);             
   }

   if ( isset($a["arrival_airport"]) ) {
      
      unset($a["arrival_city_code"]);
      $s_arrival_airport = $a["arrival_airport"];
      if ( !isset($airports_all[$a["arrival_airport"]]) ) {
         return null;
      }
      $airports_dst[$s_arrival_airport] = $airports_all[$s_arrival_airport];   
   }
   elseif ( isset($a["arrival_city_code"]) ) {
      $a_dst = $a;
      $a_dst["city_code"] = $a["arrival_city_code"];
      unset($a_dst["arrival_city_code"]);
      $airports_dst = airports($a_dst);      
   }
   else {
      foreach($airports_all as $code => $airport) {
         if ( $airport["city_code"] != $departure_city_code ) {
            $airports_dst[$code] = $airport;        
         } 
      }        
   }
   
   if ( isset($a["departure_city_code"]) ) {
      if ( isset($a["arrival_city_code"])
         && $a["departure_city_code"] == $a["arrival_city_code"] 
         ) {
         return null;
      }
      if ( isset($a["arrival_airport"]) ) {
         $airports = $airports_all[$a["arrival_airport"]];
         if ( $a["departure_city_code"] == $airports["city_code"] ) {
            return null;
         }
      }
   }
   
   $f_round_trip = isset($a["departure_date_rtn"]);
   
   $out = new ArrOneWayTrips();
   $out_return_trip = new ArrOneWayTrips();
   
   $departure_date_rtn = $f_round_trip ? $a["departure_date_rtn"] : "";
   unset($a["departure_date_rtn"]);
   
   if ( empty($airports_src) || empty($airports_dst) ) {
      return null;
   }
   foreach($airports_src as $code_src => $airport_src) {
      foreach($airports_dst as $code_dst => $airport_dst) {
         $a_1 = $a;
         $a_1["departure_airport"] = $code_src;
         $a_1["arrival_airport"] = $code_dst;
         
         $arr = flights_airport2airport($a_1, $airports_all);

         if ( !empty($arr) 
             &&  $f_round_trip
            ) {

            $a_ret = $a;
            $a_ret["arrival_airport"] = $code_src;
            $a_ret["departure_airport"] = $code_dst;
            $a_ret["s_date"] = $departure_date_rtn; 
                     
            $arr_ret = flights_airport2airport($a_ret, $airports_all);
            
            if ( !empty($arr_ret) ) {
               
               foreach($arr as $owt) {
                  $out->add_one_way_trip($owt);
               }
               foreach($arr_ret as $owt_ret) {
                  $out_return_trip->add_one_way_trip($owt_ret);
               }
            }            
         }
         else {
            foreach($arr as $owt) {
               $out->add_one_way_trip($owt);
            }
         }
      }
   }
   
   if ( !$f_round_trip ) {
      return $out->arr_one_way_trips();
   }

   
   $arr_flight_plans = array();
   $arr_0  = $out->arr_one_way_trips();
   
   foreach($arr_0 as $owt) {
      $departure_airport = $owt->departure_airport();
      $arrival_airport = $owt->arrival_airport();
      $key = $departure_airport."_".$arrival_airport;
      
      if ( !isset($arr_flight_plans[$key]) ) {
         $arr_flight_plans[$key] = array();
      }
      $arr_flight_plans[$key][] = $owt;      
   }
   
   $arr_rtn_flight_plans = array();
   $arr_1  = $out_return_trip->arr_one_way_trips();
   foreach($arr_1 as $owt_rtn) {
      $departure_airport = $owt_rtn->departure_airport();
      $arrival_airport = $owt_rtn->arrival_airport();
      $key = $arrival_airport."_".$departure_airport;
      
      if ( !isset($arr_rtn_flight_plans[$key]) ) {
         $arr_rtn_flight_plans[$key] = array();
      }
      
      $arr_rtn_flight_plans[$key][] = $owt_rtn;      
   }
   
   $arr_ret = array();
   foreach($arr_flight_plans as $key => $arr) {
      assert(!empty($arr_rtn_flight_plans[$key]));
      foreach($arr as $owt) {
         $arr_pair_2_add = array();
         $arr_pair_2_add["flight"] = $owt;
         foreach($arr_rtn_flight_plans[$key] as $owt_rtn) {
            $arr_pair_2_add_x = $arr_pair_2_add; 
            $arr_pair_2_add_x["return"] = $owt_rtn;
            $arr_ret[] = $arr_pair_2_add_x;
         }
      }
   }
   return $arr_ret;
   
}

function cheapest_flight(array $arr_flights) {
   $best_cost = INF;
   $cheapest_flight = null; 
   foreach($arr_flights as $flight) {

      if ( $flight["price"] < $best_cost ) {
         $best_cost = $flight["price"];
         $cheapest_flight = $flight;
      }
   }
   return $cheapest_flight;
}

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
   
class OneWayTrip {
   
   private $arr_flights;
 
   public function __construct(array $arr_direct_flights) {
      $this->arr_flights = array();
      
      $prev = null;
      foreach($arr_direct_flights as $flight_id => $direct_flight) {
         if (is_null($prev)) {
            $direct_flight["total_price_prev"] = number_format(0, 2, '.', '');
         }
         else {
            $total_price_prev = $prev["total_price_prev"] + $prev["price"];
            $direct_flight["total_price_prev"] = number_format($total_price_prev, 2, '.', '');            
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

   public function departure_date_time() {
      return $this->first_leg()["departure_date_time"];
   }

   public function arrival_date_time() {
      return $this->last_leg()["arrival_date_time"];
   }
   
   public static function can_connect(array $direct_flight1, array $direct_flight2) {
      if ( $direct_flight1["arrival_airport"] != $direct_flight2["departure_airport"] ) {
         return false;
      }
      $dt_arrival_flight1  = $direct_flight1["arrival_date_time"];
      $dt_departure_flight2  = $direct_flight2["departure_date_time"];
      
      if ( $dt_departure_flight2 < $dt_arrival_flight1 ) {
         return false;
      }
      
      if ( $direct_flight1["airline"] == $direct_flight2["airline"]
          && $direct_flight1["number"] == $direct_flight2["number"]
         ) {
         return true;      
      }
      
      $dt_arrival_flight1_p_delay = clone($dt_arrival_flight1);
      $dt_arrival_flight1_p_delay->modify("+".mins_min_layover()." minutes");
      
      if ( $dt_departure_flight2 < $dt_arrival_flight1_p_delay ) {
         return false;
      }
      
      $dt_arrival_flight1_p_delay = clone($dt_arrival_flight1);
      $dt_arrival_flight1_p_delay->modify("+".mins_max_layover()." minutes");

      if ( $dt_arrival_flight1_p_delay < $dt_departure_flight2 ) {
         return false;
      }
      return true;
   }
   
   public function add_leg(array $direct_flight) {
      
      $last_leg = $this->last_leg();
      if ( !self::can_connect($last_leg, $direct_flight) ) {
          return;   
      }
      $direct_flight["total_price_prev"] = number_format($this->price(), 2, '.', '');
      $flight_id = $direct_flight["flight_id"];
      assert(!empty($flight_id));
      assert(!isset($this->arr_flights[$flight_id]));
      $this->arr_flights[$flight_id] = $direct_flight;
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
      
      $dt_arrival = $last_leg["arrival_date_time"];
      $dt_departure = $first_leg["departure_date_time"];

      $interval = $dt_arrival->diff($dt_departure);
      return $interval->i;
   }

   public function s_departure_date() {
      $first_leg = $this->first_leg();
      return $first_leg["departure_date_time"]->format(s_date_fmt(false));
   }

   // return 1 if departure dates are the same and
   // one-way-trip $owt1 seems clearly better than one-way-trip $owt2
   public static function cmp(OneWayTrip $owt1, OneWayTrip $owt2) {
      
      if ( $owt1->s_departure_date() != $owt2->s_departure_date() ) {
         return 0;
      }
      if ( $owt1->departure_airport() != $owt2->departure_airport() ) {
         return 0;   
      }
      if ( $owt1->arrival_airport() != $owt2->arrival_airport() ) {
         return 0;   
      }
      
      if ( $owt1->price() < $owt2->price() 
         && $owt1->flight_duration() <= $owt2->flight_duration()
         ||
         $owt1->price() <= $owt2->price() 
         && $owt1->flight_duration() < $owt2->flight_duration()
         ) {
         return 1;
      }
      if ( $owt2->price() < $owt1->price() 
         && $owt2->flight_duration() <= $owt1->flight_duration()
         ||
         $owt2->price() <= $owt1->price() 
         && $owt2->flight_duration() < $owt1->flight_duration()
         ) {
         return -1;
      }
      return 0;
   }
}

class ArrOneWayTrips {

   private $arr_one_way_trips;
   
   public function __construct() {
      $this->arr_one_way_trips = array();
   }
   
   public function arr_one_way_trips() {
      return $this->arr_one_way_trips; 
   }
   
   public function add_one_way_trip(OneWayTrip $owt) {
      
      $arr_to_remove = array();
      foreach($this->arr_one_way_trips as $k => $owt_) {
         if ( $owt_ == $owt ) {
            return;   
         }
         $cmp = OneWayTrip::cmp($owt_, $owt);
         if ( 1 == $cmp ) {
            return;
         }
         if ( -1 == $cmp ) {
            $arr_to_remove[] = $k;
         }
      }
      if ( !empty($arr_to_remove) ) {
         foreach($arr_to_remove as $k) {
            unset($this->arr_one_way_trips[$k]);
         }
         $this->arr_one_way_trips = array_values($this->arr_one_way_trips);
      } 
      $this->arr_one_way_trips[] = $owt;
   }
   
}

class Location {
   
   private $airport_code;
   private $date_time;
   private $cost_so_far;
   private $arr_flights2here;
   
   public function __construct($airport_code, DateTime $date_time, $cost_so_far, array $arr_flights2here=null) {
      $this->airport_code = $airport_code;
      $this->date_time = $date_time;
      $this->cost_so_far = $cost_so_far;
      $this->arr_flights2here = is_null($arr_flights2here) ? array() : $arr_flights2here;
   }
   
   public function airport_code() {
      return $this->airport_code;
   }
   public function date_time() {
      return $this->date_time;
   }
   public function cost_so_far() {
      return $this->cost_so_far;
   }
   public function arr_flights2here() {
      return $this->arr_flights2here;
   }
}



?>