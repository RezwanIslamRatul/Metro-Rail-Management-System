<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Check if the user is an admin
if (!isAdmin()) {
    die("You must be an admin to access this page");
}

echo "<h2>Schedule Stations Data</h2>";

// Get all schedule stations
$scheduleStations = fetchRows("SELECT ss.*, s.name as station_name, s.code as station_code, sc.id as schedule_id 
                               FROM schedule_stations ss
                               JOIN stations s ON ss.station_id = s.id
                               JOIN schedules sc ON ss.schedule_id = sc.id
                               ORDER BY ss.schedule_id, 
                                        CASE WHEN ss.arrival_time IS NULL THEN ss.departure_time 
                                        ELSE ss.arrival_time END");

if (empty($scheduleStations)) {
    echo "<p>No schedule stations data found. Let's insert some sample data:</p>";
    
    // Get all schedules
    $schedules = fetchRows("SELECT s.*, r.id as route_id, r.name as route_name 
                           FROM schedules s 
                           JOIN routes r ON s.route_id = r.id");
    
    if (!empty($schedules)) {
        echo "<h3>Inserting sample schedule stations data...</h3>";
        
        foreach ($schedules as $schedule) {
            echo "<h4>Schedule ID: {$schedule['id']} (Route: {$schedule['route_name']})</h4>";
            
            // Get all stations for this route
            $routeStations = fetchRows("SELECT rs.*, s.name as station_name, s.code as station_code 
                                       FROM route_stations rs
                                       JOIN stations s ON rs.station_id = s.id
                                       WHERE rs.route_id = ?
                                       ORDER BY rs.stop_order ASC", [$schedule['route_id']]);
            
            if (!empty($routeStations)) {
                // Calculate time intervals (simple approach - 10 minutes between stations)
                $baseTime = strtotime($schedule['departure_time']);
                $interval = 10 * 60; // 10 minutes in seconds
                
                foreach ($routeStations as $index => $station) {
                    $arrivalTime = null;
                    $departureTime = null;
                    
                    // First station has no arrival time
                    if ($index === 0) {
                        $departureTime = date('H:i:s', $baseTime);
                    } 
                    // Last station has no departure time
                    elseif ($index === count($routeStations) - 1) {
                        $arrivalTime = date('H:i:s', $baseTime + ($index * $interval));
                    }
                    // Middle stations have both arrival and departure times
                    else {
                        $arrivalTime = date('H:i:s', $baseTime + ($index * $interval));
                        $departureTime = date('H:i:s', $baseTime + ($index * $interval) + 180); // 3 minutes stop
                    }
                    
                    // Insert into schedule_stations
                    $data = [
                        'schedule_id' => $schedule['id'],
                        'station_id' => $station['station_id'],
                        'arrival_time' => $arrivalTime,
                        'departure_time' => $departureTime
                    ];
                    
                    $insertId = insert('schedule_stations', $data);
                    
                    if ($insertId) {
                        echo "<p>Added station {$station['station_name']} ({$station['station_code']}) to schedule: ";
                        echo "Arrival: " . ($arrivalTime ?: 'N/A') . ", Departure: " . ($departureTime ?: 'N/A') . "</p>";
                    } else {
                        echo "<p>Failed to add station {$station['station_name']}</p>";
                    }
                }
            } else {
                echo "<p>No route stations found for this schedule</p>";
            }
        }
    } else {
        echo "<p>No schedules found</p>";
    }
} else {
    echo "<p>Found " . count($scheduleStations) . " schedule stations records:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Schedule ID</th><th>Station</th><th>Arrival</th><th>Departure</th></tr>";
    
    foreach ($scheduleStations as $station) {
        echo "<tr>";
        echo "<td>" . $station['id'] . "</td>";
        echo "<td>" . $station['schedule_id'] . "</td>";
        echo "<td>" . $station['station_name'] . " (" . $station['station_code'] . ")</td>";
        echo "<td>" . ($station['arrival_time'] ?: 'N/A') . "</td>";
        echo "<td>" . ($station['departure_time'] ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
