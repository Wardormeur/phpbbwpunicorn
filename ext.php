<?php
namespace wardormeur\phpbbwpunicorn;


class ext extends \phpbb\extension\base
{
// override enable step
   function enable_step($old_state)
   {
	global $config;
	global $request;
	global $phpEx;
	global $phpbb_root_path;

	/*Require WP includes*/
	//$path_to_wp = __DIR__.'/../../../../wordpress/';
	$path_to_wp = $config['phpbbwpunicorn_wp_path'];

	define( 'WP_USE_THEMES', FALSE );
	define( 'SHORTINIT', TRUE );
	
	$request->enable_super_globals();//Gosh.. WP.
	require( $path_to_wp.'/wp-load.'.$phpEx );

	//VERY MINIMAL FUCKING CONF MODAFUCKERS!!1
	#require($path_to_wp.'wp-includes/post.'.$phpEx);
	require($path_to_wp.'/wp-includes/query.'.$phpEx);
	require($path_to_wp.'/wp-includes/taxonomy.'.$phpEx);
	require($path_to_wp.'/wp-includes/capabilities.'.$phpEx);
	require($path_to_wp.'/wp-includes/meta.'.$phpEx);
	require($path_to_wp.'/wp-includes/link-template.'.$phpEx);
	require($path_to_wp.'/wp-includes/pluggable.'.$phpEx);


	//PLZ DONT CALL ME NAMES, it's ugly, it's patching for someone who dont want to make a modification to cores
		
	$unsafe_include = file_get_contents($path_to_wp.'/wp-includes/user.php');
	
	//actually, i'd better encapsulate only, but the regexp.. --'
	//if we could, we'd get a lesser chance to break it
	
	//we enclose the validate_username function to be able to use the class without breaking everything
	$safe_include=str_replace('function validate_username','if (!function_exists(\'validate_username\')) { function validate_username',$unsafe_include);
	$safe_include=str_replace('return apply_filters( \'validate_username\', $valid, $username );','return apply_filters( \'validate_username\', $valid, $username );}',$safe_include);
	
	file_put_contents($phpbb_root_path . 'cache/phpbbwpunicorn_user.' . $phpEx, $safe_include);

	require($phpbb_root_path . 'cache/phpbbwpunicorn_user.'.$phpEx);
	
	//tehn stock it as a file and reinclude??
	//use phpbb cache system?
		
		
	$unsafe_include = file_get_contents($path_to_wp.'/wp-includes/formatting.php');	
	$safe_include=str_replace('function make_clickable','if (!function_exists(\'make_clickable\')) { function make_clickable',$unsafe_include);
	$safe_include=str_replace('return $r;','return $r; }',$safe_include);
		
	file_put_contents($phpbb_root_path . 'cache/phpbbwpunicorn_formatting.' . $phpEx, $safe_include);

	require($phpbb_root_path . 'cache/phpbbwpunicorn_formatting.'.$phpEx);
	
	$request->disable_super_globals();
	// Run parent enable step method
	
	return parent::enable_step($old_state);
   }
   
   
 
}

?>