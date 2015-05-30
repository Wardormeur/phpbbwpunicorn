<?php
/**
*
* @package phpBB Extension - Acme Demo
* @copyright (c) 2014 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace wardormeur\phpbbwpunicorn\tests\dbal;
class db_integrity_test extends \phpbb_database_test_case
{
	private $db;
	private $tools;
	
	static protected function setup_extensions()
	{
		return array('wardormeur/phpbbwpunicorn');
	}

	
	protected function setUp()
    {
		$this->db = $this->new_dbal();
		$this->db_tools = new \phpbb\db\tools($this->db);

    }

	//create sample DB
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/config.xml');
	}
	
	public function test_path()
	{
		//valid path?
		$sql = 'SELECT value FROM config WHERE name=\'phpbbwpunicorn_path\' ';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$path = $row['value'];
		}
		$this->assertNotEmpty($path);
	}
	public function test_association()
	{
		//valid JSON ?
		//valid path?
		$sql = 'SELECT value FROM config WHERE name=\'phpbbwpunicorn_wp_*\' ';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->assertNotEmpty(unserialize($path));
		}
	}
	
	public function test_default_role()
	{
		//exists?
	}
}