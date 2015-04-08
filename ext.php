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
	
	$path_to_wp = $config['phpbbwpunicorn_wp_path'];

	define( 'WP_USE_THEMES', FALSE );
	define( 'SHORTINIT', TRUE );
	
	$request->enable_super_globals();//Gosh.. WP.
	require( $path_to_wp.'/wp-load.'.$phpEx );

	//i must admit that composer autloader SUXX with namespaces.
	require 'vendor/autoload.php';
	
	require __DIR__.'/proxy/SafeFunction.php';
	require __DIR__.'/proxy/PathFixer.php';
	
	//VERY MINIMAL FUCKING CONF MODAFUCKERS!!1
	require($path_to_wp.'/wp-includes/query.'.$phpEx);
	require($path_to_wp.'/wp-includes/taxonomy.'.$phpEx);
	require($path_to_wp.'/wp-includes/capabilities.'.$phpEx);
	require($path_to_wp.'/wp-includes/meta.'.$phpEx);
	require($path_to_wp.'/wp-includes/link-template.'.$phpEx);
	require($path_to_wp.'/wp-includes/pluggable.'.$phpEx);
	
	//PLZ DONT CALL ME NAMES, it's ugly, it's patching for someone who dont want to make a modification to cores
	
	if(!empty($wp_path)){
		$parser = new \PHPParser_Parser(new \PHPParser_Lexer);
		$prettyPrinter = new \PHPParser_PrettyPrinter_Default;
		try {
				$searched_function[] = "validate_username"; 
			
				$traverser_safety     = new \PHPParser_NodeTraverser;
				$traverser_safety->addVisitor(new SafeFunction($searched_function));
				// parse
				$raw = file_get_contents($path_to_wp.'/wp-includes/user.php');
			
				$stmts = $parser->parse($raw);

				// traverse
				$stmts = $traverser_safety->traverse($stmts);

				// pretty print
				
				$code = $prettyPrinter->prettyPrint($stmts);
				file_put_contents($phpbb_root_path . 'cache/phpbbwpunicorn_user.' . $phpEx, '<?php '.$code.' ?>');
		} catch (PHPParser\Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}

		
		
		

		require($phpbb_root_path . 'cache/phpbbwpunicorn_user.'.$phpEx);
			
			
		$parser = new \PHPParser_Parser(new \PHPParser_Lexer);
		$prettyPrinter = new \PHPParser_PrettyPrinter_Default;
		try {
				$searched_function[] = "make_clickable"; 
			
				$traverser_safety     = new \PHPParser_NodeTraverser;
				$traverser_safety->addVisitor(new SafeFunction($searched_function));
				// parse
				$raw = file_get_contents($path_to_wp.'/wp-includes/formatting.php');	
		
				$stmts = $parser->parse($raw);

				// traverse
				$stmts = $traverser_safety->traverse($stmts);

				// pretty print
				
				$code = $prettyPrinter->prettyPrint($stmts);
				file_put_contents($phpbb_root_path . 'cache/phpbbwpunicorn_formatting.' . $phpEx, '<?php '.$code.' ?>');
		} catch (PHPParser\Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}

		require($phpbb_root_path . 'cache/phpbbwpunicorn_formatting.'.$phpEx);
	}
	$request->disable_super_globals();
	// Run parent enable step method
	
	return parent::enable_step($old_state);
   }
   
   
 
}

?>