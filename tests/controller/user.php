<?php 


namespace wardormeur\phpbbwpunicorn\tests\controller;


class user_test extends \phpbb_test_case{

	private _user;
	protected function setUp()
    {
    	$this->_user = new \wardormeur\phpbbwpunicorn\User();
		$this->_wp_user = new \WP_User();
		$this->_phpbb_user = new \User();
	}
	
	public function create_wp_user_test($localuser)
	{
		//Check if the user is created through SQL ?
		
		$this->assertTrue(true);
	}

	private function get_role_test($localuser)
	{
		//Check if we can return some roles
		$this->assertTrue(true);
	}
	public function update_wp_user_test($localuser,$wpuser)
	{
		//Check if the user we update is updated : through mock user
		$this->assertTrue(true);
	}
	
	//get all users from phpbb & sync them into WP	
	public function sync_users_test()
	{
		//it's a loop of the users, so..
		//tests how many is updated?
		
		$this->assertTrue(true);
	}
	
	public function get_wp_user_test()
	{
		//get usermock per username
		$this->assertEqual($this->_wp_user['username_clean'],$this->_phpbb_user->data['username']);
	}
	
	private function prepare_wp_user_array_test()
	{
		//return an array of corresponding info from phpbbuser to wpuser
		$this->_user->prepare_wp_user_array($this->_phpbb_user, $this->_wp_user);
		$this->assert
	}
	
	
	
	

	
}
?>