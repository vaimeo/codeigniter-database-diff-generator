<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');
class Zend
{
/**
  * Constructor
  *
  * @param string $class class name
  */
function __construct($class = NULL)
{
  // include path for Zend Framework
  // alter it accordingly if you have put the 'Zend' folder elsewhere
        if (!strstr(ini_get('include_path'),APPPATH . 'third_party'))
        {
            ini_set('include_path',
            ini_get('include_path') . PATH_SEPARATOR . APPPATH . 'third_party');
        }

  if ($class)
  {
   require_once (string) $class .'.php';
   log_message('debug', "Zend Class $class Loaded");
  }
  else
  {
   log_message('debug', "Zend Class Initialized");
  }
}

/**
  * Zend Class Loader
  *
  * @param string $class class name
  */
function load($class)
{
  require_once (string) $class .'.php';
  log_message('debug', "Zend Class $class Loaded");
}
}