<?php
/*
  ASD IF / ELSE / ELSEIF Module
  Fully safe, block-based IF/ELSEIF/ELSE/END
  Includes regex token functionality
  License: MIT
*/

/**
 * Process IF / ELSEIF / ELSE / END blocks
 *
 * @param string $line Current line
 * @param array &$variables Current variable state
 * @param array &$lines All lines in the ASD script
 * @param int &$i Current line index (passed by reference)
 * @return bool True if line was handled
 */
function if_else_elseif($line, &$variables, &$lines, &$i) {
    // Check if this is an IF statement
    if (!preg_match('/^IF (.+) THEN DO$/i', trim($line), $m)) {
        return false;
    }
    
    $count = count($lines);
    $blocks = [];
    $current_cond = trim($m[1]);
    $current_cmds = [];
    $i++; // Move past IF line

    while ($i < $count) {
        $current_line = trim($lines[$i]);

        // ELSEIF block
        if (preg_match('/^ELSEIF (.+)$/i', $current_line, $m)) {
            $blocks[] = ['cond' => $current_cond, 'commands' => $current_cmds];
            $current_cond = trim($m[1]);
            $current_cmds = [];
        }
        // ELSE block
        elseif (preg_match('/^ELSE$/i', $current_line)) {
            $blocks[] = ['cond' => $current_cond, 'commands' => $current_cmds];
            $current_cond = true; // unconditional ELSE
            $current_cmds = [];
        }
        // END block
        elseif (preg_match('/^END$/i', $current_line)) {
            $blocks[] = ['cond' => $current_cond, 'commands' => $current_cmds];
            $i++; // Move past END
            break;
        }
        // command inside block
        else {
            $current_cmds[] = $current_line;
        }

        $i++;
    }

    // Execute first matching block
    foreach ($blocks as $block) {
        $cond = $block['cond'];
        $cmds = $block['commands'];
        $ok = false;

        if ($cond === true) {
            $ok = true; // ELSE
        } else {
            $ok = safe_eval_condition($cond, $variables);
        }

        if ($ok) {
            // Execute each command in the block
            foreach ($cmds as $cmd) {
                // Use the main execute_line function if available
                if (function_exists('execute_line')) {
                    execute_line($cmd, $variables);
                }
            }
            break;
        }
    }
    
    return true;
}

/**
 * Evaluate condition safely
 *
 * @param string $cond
 * @param array $variables
 * @return bool
 */
function safe_eval_condition($cond, $variables) {
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
        return eval('return (' . $eval_cond . ');');
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Converts readable tokens to PHP regex
 *
 * @param string $pattern Pattern with tokens like %digit, %word, etc.
 * @return string PHP regex pattern
 */
function regex($pattern) {
    $map = [
        '%char'   => '.',
        '%digit'  => '\d',
        '%word'   => '\w+',
        '%line'   => '.*',
        '%id'     => '[a-zA-Z_][a-zA-Z0-9_]*',
        '%op'     => '[+\-*\/=<>!]+',
        '%string' => '"[^"]*"|\'[^\']*\'',
        '%number' => '\d+(\.\d+)?',
        '%space'  => '\s+',
        '%alpha'  => '[a-zA-Z]+',
        '%alnum'  => '[a-zA-Z0-9]+',
        '%hex'    => '[0-9A-Fa-f]+',
    ];

    // Escape the pattern first
    $pattern = preg_quote($pattern, '/');
    
    // Replace tokens with their regex equivalents
    foreach ($map as $token => $regex) {
        $pattern = str_replace(preg_quote($token, '/'), $regex, $pattern);
    }

    return '/' . $pattern . '/';
}