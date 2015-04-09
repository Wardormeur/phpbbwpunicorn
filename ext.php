<?php
namespace wardormeur\phpbbwpunicorn;


//i must admit that composer autloader SUXX with namespaces.
require 'vendor/autoload.php';
include_once('proxy.php');

class ext extends \phpbb\extension\base
{
// override enable step
   function enable_step($old_state)
   {
	global $phpEx;
	global $phpbb_root_path;
	global $request;

	/*Require WP includes*/
	
	$proxy = new Proxy();
	$proxy->cache();
	
	//AAAAAAAAAAAAND let's hope it works?
	// Run parent enable step method
	return parent::enable_step($old_state);
   }
   
   
 
}

?>