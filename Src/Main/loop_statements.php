<?php
/*
  ASD Loop Module
  Handles LOOP N (...) and WHILE (...) blocks
  Fully modular and line-by-line
  License: MIT
*/

/**
 * Read a block enclosed in parentheses
 *
 * @param array &$lines All lines
 * @param int &$i Current line index (passed by reference)
 * @return array Lines inside the block
 */
function read_block(&$lines, &$i) {
    $block = [];
    $i++;
    $lineCount = count($lines);
    
    for (; $i < $lineCount; $i++) {
        $line = trim($lines[$i]);
        if ($line === ')') {
            break;
        }
        $block[] = $line;
    }
    return $block;
}

/**
 * Process a block of lines (needed for loop execution)
 *
 * @param array $block Lines to process
 * @param array &$variables Variables
 * @return void
 */
function process_block_lines($block, &$variables) {
    // Create a local copy of the block for processing
    $localLines = $block;
    
    for ($j = 0; $j < count($localLines); $j++) {
        // Use the main execute_line_full function if available
        if (function_exists('execute_line_full')) {
            execute_line_full($localLines[$j], $variables, $localLines, $j);
        } elseif (function_exists('execute_line')) {
            execute_line($localLines[$j], $variables);
        }
    }
}

/**
 * Evaluate a condition for WHILE loop
 *
 * @param string $cond Condition to evaluate
 * @param array &$variables Variables
 * @return bool True if condition is true
 */
function eval_condition($cond, &$variables) {
    // Replace variables with their values
    $eval_cond = $cond;
    foreach ($variables as $key => $val) {
        if (is_array($val)) {
            continue;
        }
        // Quote string values
        if (is_string($val) && !is_numeric($val)) {
            $eval_cond = str_replace($key, '"' . addslashes($val) . '"', $eval_cond);
        } else {
            $eval_cond = str_replace($key, (string)$val, $eval_cond);
        }
    }

    try {
        $result = eval('return (' . $eval_cond . ');');
        return (bool)$result;
    } catch (Throwable $e) {
        // Report error but continue execution
        if (function_exists('asd_error')) {
            asd_error(ASD_ERROR_RUNTIME, 'syntax_invalid', 0, 
                     "Invalid condition in WHILE: $cond", 
                     "Check your condition syntax");
        }
        return false;
    }
}

/**
 * Process LOOP N (...) block
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @param array &$lines All lines
 * @param int &$i Current line index
 * @return bool True if line was handled
 */
function loop_line($line, &$variables, &$lines, &$i) {
    if (!preg_match('/^LOOP\s+(\d+)\s*\($/i', $line, $m)) {
        return false;
    }
    
    $count = intval($m[1]);
    
    // Validate loop count
    if ($count <= 0) {
        if (function_exists('asd_error')) {
            asd_error(ASD_ERROR_RUNTIME, 'syntax_invalid', $i + 1, 
                     "Loop count must be positive: $count", 
                     "Use a number greater than 0");
        }
        // Skip the block
        read_block($lines, $i);
        return true;
    }
    
    // Read the block
    $block = read_block($lines, $i);
    
    // Execute the block N times
    for ($j = 0; $j < $count; $j++) {
        process_block_lines($block, $variables);
    }
    
    return true;
}

/**
 * Process WHILE (...) block
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @param array &$lines All lines
 * @param int &$i Current line index
 * @return bool True if line was handled
 */
function while_line($line, &$variables, &$lines, &$i) {
    if (!preg_match('/^WHILE\s+(.+)\s*\($/i', $line, $m)) {
        return false;
    }
    
    $cond = trim($m[1]);
    
    // Read the block
    $block = read_block($lines, $i);
    
    // Safety: prevent infinite loops (max 10000 iterations)
    $maxIterations = 10000;
    $iteration = 0;
    
    // Execute while condition is true
    while (eval_condition($cond, $variables)) {
        process_block_lines($block, $variables);
        
        $iteration++;
        if ($iteration > $maxIterations) {
            if (function_exists('asd_error')) {
                asd_error(ASD_ERROR_RUNTIME, 'runtime_exception', $i, 
                         "Infinite loop detected in WHILE (exceeded $maxIterations iterations)", 
                         "Check your condition to ensure it eventually becomes false");
            }
            break;
        }
    }
    
    return true;
}

/**
 * Process FOR loop (optional extension)
 * FOR var FROM start TO end STEP inc (...)
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @param array &$lines All lines
 * @param int &$i Current line index
 * @return bool True if line was handled
 */
function for_line($line, &$variables, &$lines, &$i) {
    if (!preg_match('/^FOR\s+(\w+)\s+FROM\s+(\d+)\s+TO\s+(\d+)(?:\s+STEP\s+(\d+))?\s*\($/i', $line, $m)) {
        return false;
    }
    
    $var = $m[1];
    $start = intval($m[2]);
    $end = intval($m[3]);
    $step = isset($m[4]) ? intval($m[4]) : 1;
    
    // Validate step
    if ($step == 0) {
        if (function_exists('asd_error')) {
            asd_error(ASD_ERROR_RUNTIME, 'syntax_invalid', $i + 1, 
                     "STEP cannot be zero", 
                     "Use a positive or negative number for step");
        }
        $step = 1;
    }
    
    // Read the block
    $block = read_block($lines, $i);
    
    // Determine direction
    if ($start <= $end) {
        // Ascending loop
        for ($variables[$var] = $start; $variables[$var] <= $end; $variables[$var] += $step) {
            process_block_lines($block, $variables);
        }
    } else {
        // Descending loop
        for ($variables[$var] = $start; $variables[$var] >= $end; $variables[$var] -= abs($step)) {
            process_block_lines($block, $variables);
        }
    }
    
    return true;
}