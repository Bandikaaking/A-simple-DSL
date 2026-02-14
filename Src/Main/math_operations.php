<?php
/*
  ASD Math Operations Module
  Handles ADD, SUB, MULT, DIV, MOD, POW, INC, DEC
  Fully modular and line-by-line
  License: MIT
*/

/**
 * Get numeric value from a token (variable or literal)
 *
 * @param string $token Token to evaluate
 * @param array &$variables Current variables
 * @return float|int Numeric value
 */
function get_numeric_value($token, &$variables) {
    // Check if it's a variable
    if (isset($variables[$token])) {
        $val = $variables[$token];
        // Handle array values
        if (is_array($val)) {
            if (function_exists('asd_error')) {
                asd_error(ASD_ERROR_TYPE, 'type_array_string', 0, 
                         "Array used in math operation", 
                         "Use individual array elements instead");
            }
            return 0;
        }
        return is_numeric($val) ? $val + 0 : 0;
    }
    
    // Check if it's a literal number
    if (is_numeric($token)) {
        return $token + 0;
    }
    
    // Try to parse as number
    if (is_numeric(trim($token))) {
        return trim($token) + 0;
    }
    
    return 0;
}

/**
 * Process arithmetic operations on variables or literals
 *
 * Supported operations:
 *   ADD a b        -> addition (a + b)
 *   SUB a b        -> subtraction (a - b)
 *   MULT a b       -> multiplication (a * b)
 *   DIV a b        -> integer division (a / b)
 *   MOD a b        -> modulo (a % b)
 *   POW a b        -> power (a ^ b)
 *   INC var        -> increment variable by 1
 *   DEC var        -> decrement variable by 1
 *   ADD a TO var   -> add a to variable
 *   SUB a FROM var -> subtract a from variable
 *
 * @param string $line Current line from ASD script
 * @param array &$variables Current variable state
 * @return bool True if line was handled
 */
function arithmetic_line($line, &$variables) {
    $line = trim($line);
    
    // Handle INC (increment)
    if (preg_match('/^INC\s+(\w+)$/i', $line, $m)) {
        $var = $m[1];
        if (!isset($variables[$var])) {
            $variables[$var] = 0;
        }
        $current = get_numeric_value($var, $variables);
        $variables[$var] = $current + 1;
        echo $variables[$var] . "\n";
        return true;
    }
    
    // Handle DEC (decrement)
    if (preg_match('/^DEC\s+(\w+)$/i', $line, $m)) {
        $var = $m[1];
        if (!isset($variables[$var])) {
            $variables[$var] = 0;
        }
        $current = get_numeric_value($var, $variables);
        $variables[$var] = $current - 1;
        echo $variables[$var] . "\n";
        return true;
    }
    
    // Handle ADD x TO var
    if (preg_match('/^ADD\s+(.+?)\s+TO\s+(\w+)$/i', $line, $m)) {
        $value = get_numeric_value(trim($m[1]), $variables);
        $var = $m[2];
        if (!isset($variables[$var])) {
            $variables[$var] = 0;
        }
        $current = get_numeric_value($var, $variables);
        $variables[$var] = $current + $value;
        echo $variables[$var] . "\n";
        return true;
    }
    
    // Handle SUB x FROM var
    if (preg_match('/^SUB\s+(.+?)\s+FROM\s+(\w+)$/i', $line, $m)) {
        $value = get_numeric_value(trim($m[1]), $variables);
        $var = $m[2];
        if (!isset($variables[$var])) {
            $variables[$var] = 0;
        }
        $current = get_numeric_value($var, $variables);
        $variables[$var] = $current - $value;
        echo $variables[$var] . "\n";
        return true;
    }
    
    // Handle standard operations: ADD a b, SUB a b, etc.
    if (!preg_match('/^(ADD|SUB|MULT|DIV|MOD|POW)\s+(\S+)\s+(\S+)$/i', $line, $m)) {
        return false;
    }
    
    $op = strtoupper($m[1]);
    $token1 = trim($m[2]);
    $token2 = trim($m[3]);
    
    // Get numeric values
    $a = get_numeric_value($token1, $variables);
    $b = get_numeric_value($token2, $variables);
    
    // Perform operation
    $res = 0;
    $error = null;
    
    switch ($op) {
        case 'ADD':
            $res = $a + $b;
            break;
            
        case 'SUB':
            $res = $a - $b;
            break;
            
        case 'MULT':
            $res = $a * $b;
            break;
            
        case 'DIV':
            if ($b == 0) {
                $error = "Division by zero";
                if (function_exists('asd_error')) {
                    asd_error(ASD_ERROR_RUNTIME, 'runtime_division_zero', 0, 
                             "Cannot divide $a by zero", 
                             "Check your DIV operation to ensure divisor is not zero");
                }
                $res = 0;
            } else {
                // Integer division
                $res = (int)($a / $b);
            }
            break;
            
        case 'MOD':
            if ($b == 0) {
                $error = "Modulo by zero";
                if (function_exists('asd_error')) {
                    asd_error(ASD_ERROR_RUNTIME, 'runtime_division_zero', 0, 
                             "Cannot calculate modulo with zero", 
                             "Check your MOD operation to ensure divisor is not zero");
                }
                $res = 0;
            } else {
                $res = $a % $b;
            }
            break;
            
        case 'POW':
            $res = pow($a, $b);
            break;
    }
    
    // Store result back in first operand if it's a variable
    if (isset($variables[$token1]) && $error === null) {
        $variables[$token1] = $res;
    }
    
    // Output result (unless there was an error that already output something)
    if ($error === null) {
        echo $res . "\n";
    }
    
    return true;
}

/**
 * Process comparison operations
 * EQ a b -> check if a == b
 * GT a b -> check if a > b
 * LT a b -> check if a < b
 * GTE a b -> check if a >= b
 * LTE a b -> check if a <= b
 * NE a b -> check if a != b
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if handled
 */
function comparison_line($line, &$variables) {
    if (!preg_match('/^(EQ|GT|LT|GTE|LTE|NE)\s+(\S+)\s+(\S+)$/i', trim($line), $m)) {
        return false;
    }
    
    $op = strtoupper($m[1]);
    $val1 = $m[2];
    $val2 = $m[3];
    
    // Get values (can be strings or numbers)
    $a = isset($variables[$val1]) ? $variables[$val1] : $val1;
    $b = isset($variables[$val2]) ? $variables[$val2] : $val2;
    
    $result = false;
    
    switch ($op) {
        case 'EQ':
            $result = ($a == $b);
            break;
        case 'GT':
            $result = ($a > $b);
            break;
        case 'LT':
            $result = ($a < $b);
            break;
        case 'GTE':
            $result = ($a >= $b);
            break;
        case 'LTE':
            $result = ($a <= $b);
            break;
        case 'NE':
            $result = ($a != $b);
            break;
    }
    
    echo ($result ? "true" : "false") . "\n";
    return true;
}

/**
 * Calculate mathematical expression (for future enhancement)
 *
 * @param string $expr Expression to evaluate
 * @param array &$variables Variables
 * @return float|int Result
 */
function calculate_expression($expr, &$variables) {
    // Simple implementation - can be extended
    $expr = trim($expr);
    
    // Replace variables with their values
    foreach ($variables as $key => $val) {
        if (is_numeric($val)) {
            $expr = str_replace($key, (string)$val, $expr);
        }
    }
    
    // Basic safety: only allow numbers and basic operators
    if (!preg_match('/^[0-9\s\+\-\*\/\(\)]+$/', $expr)) {
        return 0;
    }
    
    try {
        eval('$result = ' . $expr . ';');
        return $result ?? 0;
    } catch (Throwable $e) {
        return 0;
    }
}