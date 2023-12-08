<?php
defined('BASEPATH') OR exit('No direct script access allowed');


$autoload['packages'] = array();
$autoload['libraries'] = array('xmlrpc', 'pagination');
$autoload['drivers'] = array();
$autoload['helper'] = array('url','file','form','security','string','inflector','directory','download','user','multi_language', 'common', 'pagination');
$autoload['config'] = array();
$autoload['language'] = array();
$autoload['model'] = array('user_model', 'crud_model', 'video_model', 'email_model', 'payment_model');
