<?php
/**
 * $Id: messages.php 2856 2011-07-05 20:47:31Z miles $
 *
 * vi: shiftwidth=4 tabstop=4 smarttab expandtab formatoptions=croql
 *
 * @package webDBedit
 * @subpackage Models
 */

/**
 * Model for UI status/warning/error messages
 *
 * @author Miles Davis <miles@cs.stanford.edu>
 */
class Messages extends CI_Model{

    public $messages = array();
    public $errors = array();
    public $warnings = array();
    public $debug = false;
    public $use_jquery = false;

    function __construct() {
        parent::__construct();
        $this->debug = $this->config->item('debug');

    }

    function get_messages() {
        $result['messages'] = $this->messages;
        $result['warnings'] = $this->warnings;
        $result['errors'] = $this->errors;
        return $result;
    }

    function debug($msg) {
        if(!$this->debug) return true;
        array_push($this->warnings, $msg);
        log_message('debug', $msg);
    }

    function message($msg) {
        array_push($this->messages, $msg);
        log_message('debug', $msg);
    }

    function warning($msg) {
        array_push($this->warnings, $msg);
        log_message('error', $msg);
    }

    function error($msg) {
        array_push($this->errors, $msg);
        log_message('error', $msg);
    }

    function error_count() {
        return count($this->errors);
    }

    function debug_push($array) {
        if(!$this->debug) return true;
        if (!is_array($array)) return false;
        $this->messages = array_merge($this->messages, $array);
        return true;
    }
    function error_push($array) {
        if (!is_array($array)) return false;
        $this->errors = array_merge($this->errors, $array);
        return true;
    }

    function message_push($array) {
        if (!is_array($array)) return false;
        $this->messages = array_merge($this->messages, $array);
        return true;
    }

    function warning_push($array) {
        if (!is_array($array)) return false;
        $this->warnings = array_merge($this->warnings, $array);
        return true;
    }

    function warn($msg) {
        $this->warning($msg);
    }

}

