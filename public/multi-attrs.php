<?php
  
  /* * * * * * * * * * * * * * * * * * * * * * * * * * * *
   multi-attrs.php
   ===============
   Jay Tolentino and Six Silberman
   5 Feb 2014 17:33
   ===============
   This file requires the package php5-mysqlnd!
   Before using this API, run:
     $ sudo apt-get install php5-mysqlnd
   ===============
   Returns information for one or more AMT task requesters
   * * * * * * * * * * * * * * * * * * * * * * * * * * * */

  header("Access-Control-Allow-Origin: *");

  include '../dbconn.php';

  /* Connection Error Handling (Procedural): */
  if (!$conn) {
     die('Could Not Connect: (' . mysqli_connect_error() . ') ');
  }

  /*
    FUNCTION: gather_requester_stats( $query_result )
    USAGE: Uses $query_result to create array of info about specific requester
  */
  function gather_requester_stats( $query_result ) {
    $row = mysqli_fetch_array($query_result, MYSQLI_BOTH);
    $stats_result = array();

    $stats_result['name'] = $row['amzn_requester_name'];
    $stats_result['attrs'] = array(
        'comm' => $row['av_comm'],
         'pay' => $row['av_pay'],
        'fair' => $row['av_fair'],
        'fast' => $row['av_fast']
      );
    $stats_result['reviews'] = $row['nrs'];
    $stats_result['tos_flags'] = $row['tos_flags'];

    return $stats_result;
  }

  if( $_GET['ids'] ) {
    $ids = explode(",", $_GET[ 'ids' ]);

    // Use $num_ids and $i to check if current ID is not the last ID
    $num_ids = count($ids);
    $i = 0;
    echo "{";

    $logfile = '../log/multi-attrs.php.log';
    $time = date('Y-m-j H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    file_put_contents($logfile, "[API v2014.02.01.1838] ", FILE_APPEND);
    file_put_contents($logfile, "[" . $time . "] ", FILE_APPEND);
    file_put_contents($logfile, "[" . $ip . "] ", FILE_APPEND);
    file_put_contents($logfile, $_GET['ids'] . "\n", FILE_APPEND);

    foreach ($ids as $id) {
      echo "\"". $id . "\":";
      if ( $from_cache = apc_fetch( $id ) ) {
        file_put_contents($logfile, "    " . $id . ": from the APC\n", FILE_APPEND);
        echo $from_cache;
      } else {
        $stmt = mysqli_stmt_init( $conn );
        
        // Create and execute a prepared statment to protect from SQL injection
        if ( mysqli_stmt_prepare($stmt, 'SELECT * FROM requesters WHERE amzn_requester_id=?') ) {
          mysqli_stmt_bind_param( $stmt, "s", $id );
          mysqli_stmt_execute( $stmt );
          $result = mysqli_stmt_get_result( $stmt );

          if (mysqli_num_rows($result) == 0) {

            $to_cache = "\"\"";
	    echo $to_cache;

            apc_add($id, $to_cache, 1200);    // third parameter is time to live
            file_put_contents($logfile, "    " . $id . ": from DB: no reports\n", FILE_APPEND);

          } else { /* assume mysqli_num_rows($result) not empty */

            $requester_stats = gather_requester_stats( $result );
            $to_cache = json_encode( $requester_stats );
            echo $to_cache;

            apc_add($id, $to_cache, 1200);    // third parameter is time to live
            file_put_contents($logfile, "    " . $id . ": from DB\n", FILE_APPEND);

	  }

          mysqli_stmt_close( $query );

         }
      }

      if ( ++$i < $num_ids ) {
        echo ",";
      }
    }
    echo "}";
  } else {
    echo "To get data, call this URL with an 'ids' parameter.";
  }
  mysqli_close($conn);
?>

