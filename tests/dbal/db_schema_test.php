<?php
/**
*
* @package phpBB Extension - Acme Demo
* @copyright (c) 2014 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace wardormeur\phpbbwpunicorn\tests\dbal;
class db_schema_test extends \phpbb_database_test_case
{
	private $db;
	private $tools;
	
	static protected function setup_extensions()
	{
		return array('wardormeur/phpbbwpunicorn');
	}

	//create sample DB
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/config.xml');
	}
	
	protected function setUp()
    {
		$this->db = $this->new_dbal();
		$this->db_tools = new \phpbb\db\tools($this->db);

    }

	public function test_db_format()
	{
		$this->assertTrue($db_tools->sql_column_exists(USERS_TABLE, 'user_acme'), 'Asserting that column "user_acme" exists');
		$this->assertFalse($db_tools->sql_column_exists(USERS_TABLE, 'user_acme_demo'), 'Asserting that column "user_acme_demo" does not exist');
	
	}
	
	public function test_default_values()
	{
		$this->assertTrue(true);
	}
	
}