<?php
/*
  ASD PRINT & SETVAR Module
  Handles variable declaration and printing
  Fully modular and line-by-line, no lexer
  Compatible with ASD main engine
  License: MIT
*/

// Only define if not already defined to avoid redeclaration errors
if (!function_exists('replace_vars')) {
    /**
     * Replace variables in a text using =(var) syntax
     *
     * @param string $text Text with =(var) placeholders
     * @param array $variables Current variables
     * @return string Text with variables replaced
     */
    function replace_vars($text, $variables) {
        return preg_replace_callback('/=\((\w+)\)/', function($matches) use ($variables) {
            return $variables[$matches[1]] ?? '';
        }, $text);
    }
}

/**
 * Process PRINT and SETVAR statements
 *
 * @param string $line Current line from ASD script
 * @param array &$variables Current variable state
 * @return bool True if line was handled
 */
function print_and_declarevars($line, &$variables) {
    $line = trim($line);
    
    // SETVAR var value
    if (preg_match('/^SETVAR\s+(\w+)\s+(.+)$/i', $line, $m)) {
        $var = $m[1];
        $value = trim($m[2]);

        // Handle READLINE()
        if (strtoupper($value) === 'READLINE()') {
            $variables[$var] = trim(fgets(STDIN));
        }
        // Handle RANDOM(min,max)
        else if (preg_match('/^RANDOM\((\d+),(\d+)\)$/i', $value, $matches)) {
            $min = intval($matches[1]);
            $max = intval($matches[2]);
            $variables[$var] = rand($min, $max);
        }
        // Handle regular value with variable interpolation
        else {
            $variables[$var] = replace_vars($value, $variables);
        }
        return true;
    }

    // PRINT statement - accept PRINT in any case (original behavior)
    if (preg_match('/^PRINT\s+(.+)$/i', $line, $m)) {
        $text = $m[1];
        // Replace variables using =(var) syntax
        $output = replace_vars($text, $variables);
        echo $output . "\n";
        return true;
    }
    
    // Reject lowercase print commands (original behavior)
    if (preg_match('/^[a-z]rint\s+/i', $line) && !preg_match('/^PRINT\s+/', $line)) {
        // This is a print command but not uppercase PRINT, so ignore it
        return false;
    }
    
    // DISPLAY VARS - show all variables (debug feature)
    if (preg_match('/^DISPLAY VARS$/i', $line)) {
        if (empty($variables)) {
            echo "No variables defined.\n";
        } else {
            echo "=== VARIABLES ===\n";
            foreach ($variables as $key => $value) {
                if (is_array($value)) {
                    echo "$key = Array\n";
                } else {
                    echo "$key = $value\n";
                }
            }
            echo "=================\n";
        }
        return true;
    }
    
    return false;
}