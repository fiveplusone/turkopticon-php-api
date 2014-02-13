<?php

  header("Access-Control-Allow-Origin: *");

  include '../dbconn.php';

  function print_result_as_json($res) {
    if($row = mysql_fetch_array($res)) {
      echo "{";
      echo "\"name\": ";
      echo "\"" . $row["amzn_requester_name"] . "\"";
      echo ", ";
      echo "\"attrs\": ";
        echo "{";
        echo "\"comm\": ";
        echo $row["av_comm"];
        echo ", ";
        echo "\"pay\": ";
        echo $row["av_pay"];
        echo ", ";
        echo "\"fair\": ";
        echo $row["av_fair"];
        echo ", ";
        echo "\"fast\": ";
        echo $row["av_fast"];
        echo "}";
      echo ", ";
      echo "\"reviews\": ";
      echo $row["nrs"];
      echo ", ";
      echo "\"tos_flags\": ";
      echo $row["tos_flags"];
      echo "}";
    } else {
      echo "\"\"";
    }
  }

  if (!$conn) { die('Could not connect: ' . mysql_error()); }

  mysql_select_db("turkopticon_production", $conn);

  if ($_GET["ids"]) {
    $ids = explode(",", $_GET["ids"]);
    $num_ids = count($ids);
    $i = 0;

    $logfile = '../log/multi-attrs.php.log';
    $time = date('Y-m-j H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    file_put_contents($logfile, "[" . $time . "] ", FILE_APPEND);
    file_put_contents($logfile, "[" . $ip . "] ", FILE_APPEND);
    file_put_contents($logfile, $_GET['ids'] . "\n", FILE_APPEND);

    echo "{";
    foreach ($ids as $id) {
      if ($disp = apc_fetch($id)) {
        file_put_contents($logfile, "    " . $id . ": from APC\n", FILE_APPEND);
      } else {
        $escaped_id = mysql_real_escape_string($id);
        $query_base = "select * from requesters where amzn_requester_id='";
        $query = $query_base . $escaped_id . "';";
        $res = mysql_query($query);
        ob_start();
        print_result_as_json($res);
        $disp = ob_get_contents();
        ob_end_clean();
        apc_add($id, $disp, 300);    // third parameter is time to live
        file_put_contents($logfile, "    " . $id . ": from DB\n", FILE_APPEND);
        // file_put_contents($logfile, "    " . $disp . "\n", FILE_APPEND);
      } 

      echo "\"". $id . "\": ";
        echo $disp;
      if(++$i < $num_ids) {
        echo ", ";
      }
    }
    echo "}";

  } else {

    echo "To get data, call this URL with an 'ids' parameter.";

  }

  mysql_close($conn);

?>
