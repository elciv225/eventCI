<?php
/**
 * Custom error handler to display errors in a React-like style
 * 
 * This file provides functions to display errors in a more user-friendly way,
 * similar to how React displays errors in development mode.
 */

// Function to display an error in a React-like style
function display_error($message, $file = null, $line = null, $trace = null, $fatal = true) {
    // Stop any further output if it's a fatal error
    if ($fatal) {
        ob_clean();
    }

    // CSS for styling the error display
    $css = '
    <style>
        .react-error-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.85);
            color: #fff;
            z-index: 9999;
            padding: 20px;
            overflow: auto;
        }
        .react-error-header {
            background-color: #FE1212;
            padding: 15px;
            border-radius: 4px 4px 0 0;
            font-size: 18px;
            font-weight: bold;
        }
        .react-error-body {
            background-color: #2D2D2D;
            padding: 15px;
            border-radius: 0 0 4px 4px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .react-error-message {
            font-size: 16px;
            margin-bottom: 15px;
            color: #FFA500;
        }
        .react-error-location {
            font-size: 14px;
            margin-bottom: 15px;
            color: #CCCCCC;
        }
        .react-error-stack {
            font-size: 12px;
            color: #AAAAAA;
            border-top: 1px solid #444;
            padding-top: 10px;
        }
        .react-error-close {
            position: absolute;
            top: 10px;
            right: 10px;
            color: white;
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
        }
        .react-error-inline {
            position: relative;
            margin: 20px 0;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
    ';

    // HTML for the error display
    $containerClass = $fatal ? 'react-error-container' : 'react-error-inline';
    $html = '
    <div class="' . $containerClass . '">
        ' . ($fatal ? '<button class="react-error-close" onclick="this.parentNode.style.display=\'none\'">×</button>' : '') . '
        <div class="react-error-header">
            Error
        </div>
        <div class="react-error-body">
            <div class="react-error-message">' . htmlspecialchars($message) . '</div>';

    // Add file and line information if available
    if ($file && $line) {
        $html .= '<div class="react-error-location">in ' . htmlspecialchars($file) . ' on line ' . htmlspecialchars($line) . '</div>';
    }

    // Add stack trace if available
    if ($trace) {
        $html .= '<div class="react-error-stack">' . htmlspecialchars($trace) . '</div>';
    }

    $html .= '
        </div>
    </div>
    ';

    echo $css . $html;

    // Exit if it's a fatal error
    if ($fatal) {
        exit;
    }
}

// Function to display a non-fatal error
function display_warning($message) {
    $trace = debug_backtrace();
    $file = isset($trace[0]['file']) ? $trace[0]['file'] : null;
    $line = isset($trace[0]['line']) ? $trace[0]['line'] : null;

    display_error($message, $file, $line, null, false);
}

// Function to replace PHP's die() function
function custom_die($message) {
    $trace = debug_backtrace();
    $file = isset($trace[0]['file']) ? $trace[0]['file'] : null;
    $line = isset($trace[0]['line']) ? $trace[0]['line'] : null;

    // Format stack trace
    $traceStr = '';
    foreach ($trace as $i => $t) {
        if ($i === 0) continue; // Skip the call to custom_die itself
        $traceStr .= "#$i " . (isset($t['file']) ? $t['file'] : '<unknown file>');
        $traceStr .= "(" . (isset($t['line']) ? $t['line'] : '0') . "): ";
        $traceStr .= (isset($t['class']) ? $t['class'] . $t['type'] : '') . $t['function'] . "()\n";
    }

    display_error($message, $file, $line, $traceStr);
}

// Set custom error handler for all PHP errors
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Only handle errors that are reported according to error_reporting settings
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $error_type = '';
    switch ($errno) {
        case E_ERROR:
            $error_type = 'Fatal Error';
            break;
        case E_WARNING:
            $error_type = 'Warning';
            break;
        case E_PARSE:
            $error_type = 'Parse Error';
            break;
        case E_NOTICE:
            $error_type = 'Notice';
            break;
        default:
            $error_type = 'Unknown Error';
            break;
    }

    $message = "$error_type: $errstr";
    $trace = debug_backtrace();

    // Format stack trace
    $traceStr = '';
    foreach ($trace as $i => $t) {
        if ($i === 0) continue; // Skip the error handler itself
        $traceStr .= "#$i " . (isset($t['file']) ? $t['file'] : '<unknown file>');
        $traceStr .= "(" . (isset($t['line']) ? $t['line'] : '0') . "): ";
        $traceStr .= (isset($t['class']) ? $t['class'] . $t['type'] : '') . $t['function'] . "()\n";
    }

    display_error($message, $errfile, $errline, $traceStr);
    return true;
}

// Register the custom error handler
set_error_handler("custom_error_handler");

// Set exception handler
function custom_exception_handler($exception) {
    $message = "Uncaught Exception: " . $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();
    $trace = $exception->getTraceAsString();

    display_error($message, $file, $line, $trace);
}

// Register the custom exception handler
set_exception_handler("custom_exception_handler");
?>
