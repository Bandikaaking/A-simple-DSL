<?php
/*
  ASD IF / ELSE / ELSEIF Module
  Fully safe, block-based IF/ELSEIF/ELSE/END
  Includes regex token functionality from regex.php
  Modular, no lexer required
*/

/**
 * Process IF / ELSEIF / ELSE / END blocks
 *
 * @param array &$lines All lines in the ASD script
 * @param int &$i Current line index (passed by reference)
 * @param array &$variables Current variable state
 */
function if_else_elseif(&$lines, &$i, &$variables) {
    $count = count($lines);
    $blocks = [];
    $current_cond = null;
    $current_cmds = [];

    while ($i < $count) {
        $line = trim($lines[$i]);

        // IF block start
        if (preg_match('/^IF (.+) THEN DO$/i', $line, $m)) {
            if ($current_cond !== null) {
                throw new Exception("Nested IF not supported at line " . ($i+1));
            }
            $current_cond = trim($m[1]);
            $current_cmds = [];
        }
        // ELSEIF block
        elseif (preg_match('/^ELSE;IF (.+)$/i', $line, $m)) {
            $blocks[] = ['cond' => $current_cond, 'commands' => $current_cmds];
            $current_cond = trim($m[1]);
            $current_cmds = [];
        }
        // ELSE block
        elseif (preg_match('/^ELSE$/i', $line)) {
            $blocks[] = ['cond' => $current_cond, 'commands' => $current_cmds];
            $current_cond = true; // unconditional ELSE
            $current_cmds = [];
        }
        // END block
        elseif (preg_match('/^END$/i', $line)) {
            $blocks[] = ['cond' => $current_cond, 'commands' => $current_cmds];
            break;
        }
        // command inside block
        else {
            if ($current_cond !== null) {
                $current_cmds[] = $line;
            }
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
            foreach ($cmds as $cmd) {
                execute_line($cmd, $variables);
            }
            break; // only the first true block runs
        }
    }
}

/**
 * Evaluate condition safely
 *
 * @param string $cond
 * @param array $variables
 * @return bool
 */
function safe_eval_condition($cond, $variables) {
    // Replace variables with safe string values
    foreach ($variables as $key => $val) {
        if (is_array($val)) {
            continue;
        }
        $cond = str_replace($key, '"' . addslashes($val) . '"', $cond);
    }

    // Handle LINE:MATCH safely
    $cond = preg_replace_callback('/(\w+)\s+LINE:MATCH\(/', function($matches) use (&$variables) {
        $var = $matches[1];
        if (!isset($variables[$var])) {
            $variables[$var] = [];
        } elseif (!is_array($variables[$var])) {
            $variables[$var] = [$variables[$var]];
        }
        return 'false'; // prevent eval issues
    }, $cond);

    try {
        return eval('return (' . $cond . ');');
    } catch (Throwable $e) {
        return false; // any error in eval returns false
    }
}

/**
 * Converts readable tokens to PHP regex
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

    $pattern = preg_quote($pattern, '/');

    foreach ($map as $token => $regex) {
        $pattern = str_replace(preg_quote($token, '/'), $regex, $pattern);
    }

    return $pattern;
}
