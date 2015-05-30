<?php 


namespace wardormeur\phpbbwpunicorn\tests\controller;

require_once __DIR__.'/proxy/SafeFunction.php';
require_once __DIR__.'/proxy/PathFixer.php';
	

class proxy_test extends \phpbb_test_case{

	public function cache_test()
	{
		$this->assertFileExists("/");
	}
}
?>