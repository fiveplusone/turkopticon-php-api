<?php

/* * * * * * * * * * * * * * * * * *
 search.php
 ===================================
 Jay Tolentino and Six Silberman
 ===================================
 Searches Turkopticon databases for
 information
 * * * * * * * * * * * * * * * * * */

header("Access-Control-Allow-Origin");
include "../../dbconn.php";

if( !$conn ) {
    die ("Could not connect: (" . mysqli_connect_error() . ") ");
}

if ( $_GET["field"] && $_GET["type"] && $_GET["query"]) {
    // Start timing query
    $start_time = microtime(true);

    $search_for = $_GET["query"];
    $search_field = $_GET["field"];
    $search_type = $_GET["type"];

    // Types of search field:
    // - NAME : search through requester names
    // -   ID : search through amzn requester ids
    switch ($search_field) { 
        case "name":
            $search_attribute = "requesters.amzn_requester_name";
            break;
        case "id" :
            $search_attribute = "requesters.amzn_requester_id";
            break;
        default : // TODO does this ensure security of $search_attribute?
            break;
    }


    // Types of search types:
    // - CONTAIN : contains the query string
    // -   START : starts with query string
    // -     END : ends with query string
    switch ($search_type) {
        case "contain":
            $search_with_wildcard = "%{$search_for}%";
            break;
        case "start":
            $search_with_wildcard = "{$search_for}%";
            break;
        case "end":
            $search_with_wildcard = "%{$search_for}";
            break;
    }

    // Create and execute a prepared statement
    $stmt = mysqli_stmt_init( $conn );

    $query = "SELECT requesters.amzn_requester_id,
                     requesters.amzn_requester_name,
                     requesters.id AS to_requester_id,
                     reports.id AS to_report_id,
                     reports.fair,
                     reports.fast,
                     reports.pay,
                     reports.comm,
                     reports.description AS text,
                     reports.person_id AS review_id,
                     reports.created_at AS created_on";
    $query .= " FROM requesters, reports";
    $query .= " WHERE " . $search_attribute; // TODO secure usage of $search_attribute?
    $query .= " LIKE ?";
    $query .= " AND requesters.amzn_requester_id = reports.amzn_requester_id";
    $query .= " ORDER BY requesters.amzn_requester_name";

    if ( mysqli_stmt_prepare( $stmt, $query ) ) {
        mysqli_stmt_bind_param( $stmt, 's', $search_with_wildcard );

        mysqli_stmt_execute( $stmt );
        $result = mysqli_stmt_get_result( $stmt );

        $all_reviews = array();

        // Record query timing, start render timing
        $query_time = (microtime(true) - $start_time);
        $start_render_time = microtime(true);

        if ( mysqli_num_rows( $result ) == 0 ) {
            echo "No search results for <strong>" . $search_for . "</strong>";
        } else {

            while( $row = mysqli_fetch_assoc( $result ) ) {
                array_push($all_reviews, $row);
            }
        }
    }

    // Record rendering time
    $render_time = ( (microtime(true)) - $start_render_time );

    $json_results["reviews"] = $all_reviews;
    $json_results["query"] = "Searching for a " . $search_field . " that " . $search_type . "s " . $search_for;
    $json_results["results_count"] = mysqli_num_rows( $result );
    $json_results["query_time"] = $query_time;
    $json_results["render_time"] = $render_time;

    echo json_encode( $json_results );

} else {
    echo "<p>Put some parameters to start searching!</p>";
}
