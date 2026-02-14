<?php
/*
  ASD Other Statements Module
  Handles miscellaneous statements like SLEEP, RANDOM, LEN, UPPER, LOWER, REPLACE, READFILE, DATE, TIME, EXIT
  License: MIT
*/

/**
 * Sleep for specified seconds
 *
 * @param string $line Current line
 * @return bool True if line was handled
 */
function sleep_line($line) {
    if (preg_match('/^SLEEP\s+(\d+)$/i', trim($line), $matches)) {
        $seconds = intval($matches[1]);
        if ($seconds > 0) {
            sleep($seconds);
        }
        return true;
    }
    return false;
}

/**
 * Generate random number or random element
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if line was handled
 */
function random_line($line, &$variables) {
    $line = trim($line);
    
    // Handle RANDOM(min, max) as standalone command
    if (preg_match('/^RANDOM\((\d+),(\d+)\)$/i', $line, $matches)) {
        $min = intval($matches[1]);
        $max = intval($matches[2]);
        if ($min > $max) {
            // Swap if min > max
            $temp = $min;
            $min = $max;
            $max = $temp;
        }
        $result = rand($min, $max);
        echo $result . "\n";
        return true;
    }
    
    // Handle RANDOM min max (space-separated)
    if (preg_match('/^RANDOM\s+(\d+)\s+(\d+)$/i', $line, $matches)) {
        $min = intval($matches[1]);
        $max = intval($matches[2]);
        if ($min > $max) {
            $temp = $min;
            $min = $max;
            $max = $temp;
        }
        $result = rand($min, $max);
        echo $result . "\n";
        return true;
    }
    
    // Handle RANDOM FROM array
    if (preg_match('/^RANDOM\s+FROM\s+(\w+)$/i', $line, $matches)) {
        $var = $matches[1];
        if (isset($variables[$var]) && is_array($variables[$var]) && !empty($variables[$var])) {
            $result = $variables[$var][array_rand($variables[$var])];
            echo $result . "\n";
        } else {
            echo "0\n";
        }
        return true;
    }
    
    return false;
}

/**
 * Get length of string or array
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if line was handled
 */
function len_line($line, &$variables) {
    if (preg_match('/^LEN\((\w+)\)$/i', trim($line), $matches)) {
        $var = $matches[1];
        if (isset($variables[$var])) {
            if (is_array($variables[$var])) {
                echo count($variables[$var]) . "\n";
            } else {
                echo strlen((string)$variables[$var]) . "\n";
            }
        } else {
            echo "0\n";
        }
        return true;
    }
    
    // Handle LEN var (without parentheses)
    if (preg_match('/^LEN\s+(\w+)$/i', trim($line), $matches)) {
        $var = $matches[1];
        if (isset($variables[$var])) {
            if (is_array($variables[$var])) {
                echo count($variables[$var]) . "\n";
            } else {
                echo strlen((string)$variables[$var]) . "\n";
            }
        } else {
            echo "0\n";
        }
        return true;
    }
    
    return false;
}

/**
 * Convert to uppercase and print
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if line was handled
 */
function upper_line($line, &$variables) {
    $line = trim($line);
    
    if (preg_match('/^UPPER\s+PRINT\s+(.+)$/i', $line, $matches)) {
        $text = $matches[1];
        // Replace variables if function exists
        if (function_exists('replace_vars')) {
            $text = replace_vars($text, $variables);
        }
        echo strtoupper($text) . "\n";
        return true;
    }
    
    // Handle UPPER var (store uppercase in variable)
    if (preg_match('/^UPPER\s+(\w+)$/i', $line, $matches)) {
        $var = $matches[1];
        if (isset($variables[$var]) && is_string($variables[$var])) {
            $variables[$var] = strtoupper($variables[$var]);
        }
        return true;
    }
    
    return false;
}

/**
 * Convert to lowercase and print
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if line was handled
 */
function lower_line($line, &$variables) {
    $line = trim($line);
    
    if (preg_match('/^LOWER\s+PRINT\s+(.+)$/i', $line, $matches)) {
        $text = $matches[1];
        // Replace variables if function exists
        if (function_exists('replace_vars')) {
            $text = replace_vars($text, $variables);
        }
        echo strtolower($text) . "\n";
        return true;
    }
    
    // Handle LOWER var (store lowercase in variable)
    if (preg_match('/^LOWER\s+(\w+)$/i', $line, $matches)) {
        $var = $matches[1];
        if (isset($variables[$var]) && is_string($variables[$var])) {
            $variables[$var] = strtolower($variables[$var]);
        }
        return true;
    }
    
    return false;
}

/**
 * Replace text in a variable
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if line was handled
 */
function replace_line($line, &$variables) {
    $line = trim($line);
    
    // REPLACE var "search" "replace"
    if (preg_match('/^REPLACE\s+(\w+)\s+"([^"]*)"\s+"([^"]*)"$/i', $line, $matches)) {
        $var = $matches[1];
        $search = $matches[2];
        $replace = $matches[3];
        
        if (isset($variables[$var]) && is_string($variables[$var])) {
            $variables[$var] = str_replace($search, $replace, $variables[$var]);
            echo $variables[$var] . "\n";
        }
        return true;
    }
    
    // REPLACE var 'search' 'replace' (single quotes)
    if (preg_match('/^REPLACE\s+(\w+)\s+\'([^\']*)\'\s+\'([^\']*)\'$/i', $line, $matches)) {
        $var = $matches[1];
        $search = $matches[2];
        $replace = $matches[3];
        
        if (isset($variables[$var]) && is_string($variables[$var])) {
            $variables[$var] = str_replace($search, $replace, $variables[$var]);
            echo $variables[$var] . "\n";
        }
        return true;
    }
    
    return false;
}

/**
 * Read file contents into __FILE__ variable
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if line was handled
 */
function readfile_line($line, &$variables) {
    $line = trim($line);
    
    // READFILE(filename)
    if (preg_match('/^READFILE\((.+)\)$/i', $line, $m)) {
        $filename = trim($m[1]);
        
        // Remove quotes if present
        $filename = trim($filename, '"\'');
        
        if (file_exists($filename)) {
            $content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $variables['__FILE__'] = $content !== false ? $content : [];
            echo "Read " . count($variables['__FILE__']) . " lines from $filename\n";
        } else {
            if (function_exists('asd_error')) {
                asd_error(ASD_ERROR_FILE, 'file_not_found', 0, "File '$filename' not found");
            } else {
                echo "Error: File '$filename' not found\n";
            }
            $variables['__FILE__'] = [];
        }
        return true;
    }
    
    // READFILE filename (without parentheses)
    if (preg_match('/^READFILE\s+(.+)$/i', $line, $m)) {
        $filename = trim($m[1]);
        
        // Remove quotes if present
        $filename = trim($filename, '"\'');
        
        if (file_exists($filename)) {
            $content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $variables['__FILE__'] = $content !== false ? $content : [];
            echo "Read " . count($variables['__FILE__']) . " lines from $filename\n";
        } else {
            if (function_exists('asd_error')) {
                asd_error(ASD_ERROR_FILE, 'file_not_found', 0, "File '$filename' not found");
            } else {
                echo "Error: File '$filename' not found\n";
            }
            $variables['__FILE__'] = [];
        }
        return true;
    }
    
    return false;
}

/**
 * Get current date
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if line was handled
 */
function date_line($line, &$variables) {
    $line = trim($line);
    
    // DATE() - print current date
    if (preg_match('/^DATE\(\)$/i', $line)) {
        echo date('Y-m-d') . "\n";
        return true;
    }
    
    // DATE(format) - print formatted date
    if (preg_match('/^DATE\((.+)\)$/i', $line, $m)) {
        $format = trim($m[1], '"\'');
        echo date($format) . "\n";
        return true;
    }
    
    // SETVAR var = DATE() - store in variable (handled by SETVAR module)
    return false;
}

/**
 * Get current time
 *
 * @param string $line Current line
 * @param array &$variables Variables
 * @return bool True if line was handled
 */
function time_line($line, &$variables) {
    $line = trim($line);
    
    // TIME() - print current time
    if (preg_match('/^TIME\(\)$/i', $line)) {
        echo date('H:i:s') . "\n";
        return true;
    }
    
    // TIME(format) - print formatted time
    if (preg_match('/^TIME\((.+)\)$/i', $line, $m)) {
        $format = trim($m[1], '"\'');
        echo date($format) . "\n";
        return true;
    }
    
    return false;
}

/**
 * Exit program with optional message
 *
 * @param string $line Current line
 * @return bool True if line was handled
 */
function exit_line($line) {
    $line = trim($line);
    
    // EXIT
    if (preg_match('/^EXIT$/i', $line)) {
        exit(0);
    }
    
    // EXIT "message"
    if (preg_match('/^EXIT\s+(.+)$/i', $line, $m)) {
        $message = trim($m[1], '"\'');
        echo $message . "\n";
        exit(0);
    }
    
    return false;
}

/**
 * Helper function to replace variables in text (if not already defined elsewhere)
 * This is a fallback in case the main replace_vars isn't available
 */
if (!function_exists('replace_vars')) {
    function replace_vars($text, $variables) {
        return preg_replace_callback('/=\((\w+)\)/', function($matches) use ($variables) {
            return $variables[$matches[1]] ?? '';
        }, $text);
    }
}