<?php 


namespace wardormeur\phpbbwpunicorn;

	


require_once __DIR__.'/proxy/SafeFunction.php';
require_once __DIR__.'/proxy/PathFixer.php';
	

class proxy{

	public function __construct()
	{ // default constructor
		global $config;
		global $phpEx;
		global $phpbb_root_path;
		
		$this->config = $config;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $phpEx;
		$this->path_to_wp = $config['phpbbwpunicorn_wp_path'];
		
	}
	//else use mutator as a service
	public function set_config($config){
		$this->config = $config;
		$this->path_to_wp = $config['phpbbwpunicorn_wp_path'];

	}
	public function set_phpEx($phpEx){
		$this->phpEx = $phpEx;
	
	}
	public function set_phpbb_root_path($phpbb_root_path){
		$this->phpbb_root_path = $phpbb_root_path;
	}

	public function cache()
	{
		$parser = new \PHPParser_Parser(new \PHPParser_Lexer);
		$prettyPrinter = new \PHPParser_PrettyPrinter_Default;
		try {
				$searched_function[] = "validate_username"; 
			
				$traverser_safety     = new \PHPParser_NodeTraverser;
				$traverser_safety->addVisitor(new SafeFunction($searched_function));
				// parse
				$raw = file_get_contents($this->path_to_wp.'/wp-includes/user.'.$this->phpEx);
			
				$stmts = $parser->parse($raw);

				// traverse
				$stmts = $traverser_safety->traverse($stmts);

				// pretty print
				
				$code = $prettyPrinter->prettyPrint($stmts);
				file_put_contents($this->phpbb_root_path . 'cache/phpbbwpunicorn_user.' . $this->phpEx, '<?php '.$code.' ?>');
		} catch (PHPParser\Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}
		
		$parser = new \PHPParser_Parser(new \PHPParser_Lexer);
		$prettyPrinter = new \PHPParser_PrettyPrinter_Default;
		try {
				$searched_function[] = "make_clickable"; 
			
				$traverser_safety     = new \PHPParser_NodeTraverser;
				$traverser_safety->addVisitor(new SafeFunction($searched_function));
				// parse
				$raw = file_get_contents($this->path_to_wp.'/wp-includes/formatting.'.$this->phpEx);	
		
				$stmts = $parser->parse($raw);

				// traverse
				$stmts = $traverser_safety->traverse($stmts);

				// pretty print
				
				$code = $prettyPrinter->prettyPrint($stmts);
				file_put_contents($this->phpbb_root_path . 'cache/phpbbwpunicorn_formatting.' . $this->phpEx, '<?php '.$code.' ?>');
		} catch (PHPParser\Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}
	}
}
?>