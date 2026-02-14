<?php
/*
  ASD Error Handling Module
  Provides consistent error reporting for the interpreter
  License: MIT
*/

// Error types
define('ASD_ERROR_SYNTAX', 'Syntax Error');
define('ASD_ERROR_RUNTIME', 'Runtime Error');
define('ASD_ERROR_TYPE', 'Type Error');
define('ASD_ERROR_FILE', 'File Error');
define('ASD_ERROR_VARIABLE', 'Variable Error');

// Error messages and tips
$GLOBALS['asd_error_messages'] = [
    'syntax_unexpected' => [
        'message' => 'Unexpected token or syntax',
        'tip' => 'Check for missing parentheses, quotes, or incorrect command syntax'
    ],
    'syntax_invalid' => [
        'message' => 'Invalid syntax in expression',
        'tip' => 'Verify your condition or expression follows ASD syntax rules'
    ],
    'runtime_division_zero' => [
        'message' => 'Division by zero',
        'tip' => 'Check your DIV operation to ensure divisor is not zero'
    ],
    'type_array_string' => [
        'message' => 'Array used where string expected',
        'tip' => 'Use individual array elements instead of the whole array'
    ],
    'file_not_found' => [
        'message' => 'File not found or not readable',
        'tip' => 'Check file path and permissions'
    ],
    'file_read_error' => [
        'message' => 'Unable to read file',
        'tip' => 'Verify file exists and is accessible'
    ],
    'variable_undefined' => [
        'message' => 'Undefined variable',
        'tip' => 'Initialize variable with SETVAR before use'
    ],
    'variable_wrong_type' => [
        'message' => 'Variable used with wrong type',
        'tip' => 'Check variable content and expected type for operation'
    ],
    'regex_invalid' => [
        'message' => 'Invalid regex pattern',
        'tip' => 'Check your regex syntax and special characters'
    ],
    'module_missing' => [
        'message' => 'Required module function missing',
        'tip' => 'Check module files are properly included and functions defined'
    ],
    'runtime_exception' => [
        'message' => 'Runtime exception occurred',
        'tip' => 'Check your code for logical errors'
    ],
    'runtime_php' => [
        'message' => 'PHP runtime error',
        'tip' => 'Review the error message for details'
    ]
];

/**
 * Report an error with consistent formatting
 *
 * @param string $error_type Type of error (use ASD_ERROR_ constants)
 * @param string $error_code Specific error code from $error_messages
 * @param int $line_number Line number where error occurred
 * @param string $custom_message Optional custom message
 * @param string $custom_tip Optional custom tip
 * @return void
 */
function asd_error($error_type, $error_code, $line_number = 0, $custom_message = null, $custom_tip = null) {
    $messages = $GLOBALS['asd_error_messages'];
    
    $message = $custom_message ?? ($messages[$error_code]['message'] ?? 'Unknown error');
    $tip = $custom_tip ?? ($messages[$error_code]['tip'] ?? 'Review your code for issues');
    
    $output = "ASD ERR!: $error_type";
    if ($line_number > 0) {
        $output .= " at line $line_number";
    }
    $output .= "; $message\n";
    $output .= "💡 Tip: $tip\n\n";
    
    fwrite(STDERR, $output);
}

/**
 * Handle PHP errors and convert them to ASD errors
 *
 * @param int $errno Error level
 * @param string $errstr Error message
 * @param string $errfile File where error occurred
 * @param int $errline Line number
 * @return bool
 */
function asd_error_handler($errno, $errstr, $errfile, $errline) {
    // Don't handle suppressed errors (@ operator)
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Only handle errors in ASD files or the interpreter
    $filename = basename($errfile);
    if (strpos($errfile, '.asd') !== false || $filename === 'asd.php' || $filename === 'asd') {
        $error_type = ASD_ERROR_RUNTIME;
        $error_code = 'runtime_php';
        
        if (strpos($errstr, 'Array to string conversion') !== false) {
            $error_code = 'type_array_string';
        } elseif (strpos($errstr, 'undefined function') !== false) {
            $error_code = 'module_missing';
        } elseif (strpos($errstr, 'division by zero') !== false) {
            $error_code = 'runtime_division_zero';
        } elseif (strpos($errstr, 'Undefined variable') !== false) {
            $error_code = 'variable_undefined';
        } elseif (strpos($errstr, 'preg_match') !== false) {
            $error_code = 'regex_invalid';
        }
        
        asd_error($error_type, $error_code, $errline, $errstr);
        
        // Don't execute PHP internal error handler for these
        return true;
    }
    
    // Let PHP handle other errors normally
    return false;
}

/**
 * Handle PHP exceptions and convert them to ASD errors
 *
 * @param Throwable $exception The thrown exception
 * @return void
 */
function asd_exception_handler($exception) {
    $error_type = ASD_ERROR_RUNTIME;
    $error_code = 'runtime_exception';
    
    $line = $exception->getLine();
    $message = $exception->getMessage();
    $file = $exception->getFile();
    
    // Check if this is from an eval() (condition evaluation)
    if (strpos($message, 'eval()') !== false || strpos($file, 'eval()') !== false) {
        $error_type = ASD_ERROR_SYNTAX;
        $error_code = 'syntax_invalid';
    }
    
    asd_error($error_type, $error_code, $line, $message);
    exit(1);
}

/**
 * Get current line number from execution context
 * Useful when line number isn't directly available
 *
 * @return int Current line number
 */
function asd_get_current_line() {
    if (defined('__ASD_LINE__')) {
        return __ASD_LINE__;
    }
    
    // Try to get from debug backtrace
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    foreach ($trace as $frame) {
        if (isset($frame['line']) && isset($frame['file']) && 
            (strpos($frame['file'], '.asd') !== false || basename($frame['file']) === 'asd.php')) {
            return $frame['line'];
        }
    }
    
    return 0;
}

/**
 * Initialize error handling for ASD interpreter
 *
 * @return void
 */
function init_asd_error_handling() {
    // Set custom error and exception handlers
    set_error_handler('asd_error_handler');
    set_exception_handler('asd_exception_handler');
    
    // Don't display PHP errors directly
    ini_set('display_errors', 0);
    
    // But do log them
    ini_set('log_errors', 1);
}

// Initialize error handling when this module is included
init_asd_error_handling();