-- Schedule Stations Data
-- This script inserts sample data for schedule_stations table
-- to be run after the main database schema is created

-- Clear existing data
TRUNCATE TABLE `schedule_stations`;

-- Insert sample data for schedule 1
-- Assuming schedule 1 exists and has a route with 3 stations
INSERT INTO `schedule_stations` (`schedule_id`, `station_id`, `arrival_time`, `departure_time`) 
VALUES 
(1, 1, NULL, '06:00:00'),            -- First station (no arrival time)
(1, 2, '06:10:00', '06:13:00'),      -- Second station
(1, 3, '06:23:00', NULL);            -- Last station (no departure time)

-- Insert sample data for schedule 2
-- Assuming schedule 2 exists and has a route with 4 stations
INSERT INTO `schedule_stations` (`schedule_id`, `station_id`, `arrival_time`, `departure_time`) 
VALUES 
(2, 5, NULL, '07:00:00'),            -- First station
(2, 4, '07:15:00', '07:18:00'),      -- Second station
(2, 3, '07:30:00', '07:33:00'),      -- Third station
(2, 2, '07:45:00', NULL);            -- Last station

-- Insert sample data for schedule 3
-- Assuming schedule 3 exists and has a route with 5 stations
INSERT INTO `schedule_stations` (`schedule_id`, `station_id`, `arrival_time`, `departure_time`) 
VALUES 
(3, 1, NULL, '08:30:00'),            -- First station
(3, 2, '08:40:00', '08:43:00'),      -- Second station
(3, 3, '08:53:00', '08:56:00'),      -- Third station
(3, 4, '09:10:00', '09:13:00'),      -- Fourth station
(3, 5, '09:25:00', NULL);            -- Last station

-- You can add more data as needed for other schedules
