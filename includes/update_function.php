/**
 * Update data in a table
 * 
 * @param string $table
 * @param array $data
 * @param string $where
 * @param array $whereParams
 * @return bool
 */
function update($table, $data, $where, $whereParams = []) {
    global $conn;
    
    try {
        $setParts = [];
        
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = ?";
        }
        
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $error = "Prepare statement failed: " . $conn->error;
            error_log($error);
            return false;
        }
        
        $types = '';
        $bindParams = [];
        
        // Add data params
        foreach ($data as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            
            $bindParams[] = $param;
        }
        
        // Add where params
        foreach ($whereParams as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            
            $bindParams[] = $param;
        }
        
        // Create an array of references
        $bindParamsRef = [];
        $bindParamsRef[0] = $types;
        
        for ($i = 0; $i < count($bindParams); $i++) {
            $bindParamsRef[$i+1] = &$bindParams[$i];
        }
        
        // Call bind_param with references
        if (!empty($bindParams)) {
            call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
        }
        
        $result = $stmt->execute();
        
        if (!$result) {
            $error = "Execute failed: " . $stmt->error;
            error_log($error);
            return false;
        }
        
        // For debugging purposes
        file_put_contents('C:/xampp/htdocs/metro-rail/debug_log.txt', 
            date('Y-m-d H:i:s') . " - UPDATE SQL: $sql, Affected rows: " . $stmt->affected_rows . "\n", 
            FILE_APPEND
        );
        
        return $stmt->affected_rows > 0 || $stmt->affected_rows === 0;
        
    } catch (Exception $e) {
        error_log("Exception in update function: " . $e->getMessage());
        return false;
    }
}
