<?php 


namespace wardormeur\phpbbwpunicorn;



class user{
	public function __construct(\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db, 
		\phpbb\request\request $request,
		\phpbb\user $user,
		$phpEx,
		$phpbb_root_path)
	{
		global $phpbb_container;

		$this->config = $config;
		$this->phpbb_db = $db;
		$this->phpbb_user = $user;
		$this->request = $request;
		$this->cache = $cache;

		$this->phpbb_phpEx = $phpEx;
		$this->phpbb_root_path = $phpbb_root_path;

	
	
	
		/*Require WP includes*/
		$path_to_wp = $config['phpbbwpunicorn_wp_path'];
		define( 'WP_USE_THEMES', FALSE );
		define( 'SHORTINIT', TRUE );
		
		$this->request->enable_super_globals();//Gosh.. WP.
		require( $path_to_wp.'/wp-load.'.$phpEx );

		//VERY MINIMAL FUCKING CONF MODAFUCKERS!!1
		require($path_to_wp.'/wp-includes/post.'.$phpEx);
		require($path_to_wp.'/wp-includes/query.'.$phpEx);
		require($path_to_wp.'/wp-includes/taxonomy.'.$phpEx);
		require($path_to_wp.'/wp-includes/capabilities.'.$phpEx);
		require($path_to_wp.'/wp-includes/meta.'.$phpEx);
		require($path_to_wp.'/wp-includes/link-template.'.$phpEx);
		require($path_to_wp.'/wp-includes/pluggable.'.$phpEx);
	
	
		require($this->phpbb_root_path . 'cache/phpbbwpunicorn_user.'.$this->phpbb_phpEx);
		
	
		require($this->phpbb_root_path . 'cache/phpbbwpunicorn_formatting.'.$this->phpbb_phpEx);
		
		$this->request->disable_super_globals();
	
	}

	public function create_wp_user($localuser)
	{
		var_dump($localuser);
		$this->request->enable_super_globals();//Gosh.. WP.
		
		//Init data
		$userdata['user_login'] =  $localuser['username'];
		$userdata['user_pass'] = wp_generate_password();
		$this->prepare_wp_user_array ($localuser,$userdata);
			
		$wpuser = wp_insert_user($userdata);
	    //wp_insert_user https://codex.wordpress.org/Function_Reference/wp_insert_user
		
		// Update user meta information
		update_user_meta($userid, 'phpbb_userid', $localuser['user_id']);	
		//used by old bridge (wp_phpbb_bridge by e-xtnd.it) to link wp_user to user; save it for compatibility :)
		$this->request->disable_super_globals();//Gosh.. WP.
		
		return $userid;
	}

	private function prepare_wp_user_array($localuser,$wpuser)
	{
		$wpuser['user_url'] = $localuser['user_website'];
        $wpuser['user_email'] = $localuser['user_email'];
        $wpuser['nickname'] = $localuser['username'];
        $wpuser['jabber'] = $localuser['user_jabber'];
        $wpuser['aim'] = $localuser['user_aim'];
        $wpuser['yim'] = $localuser['user_yim'];
        /*What else is mappable ? */
		
		return $wpuser;
	}
	
	
	public function update_wp_user($localuser,$wpuser)
	{
		//We need to recover the id from the Wordpress part
		if($wpuser == null)
			$wpuser = get_wp_user($localuser->data['username']);
		
		//LOL passing reference var :')) Sorry for myslef, C++
		$wpuser = prepare_wp_user_array($localuser,$wpuser);
		
        wp_update_user($wpuser);
	}
	
	//get all users from phpbb & sync them into WP	
	public function sync_users(){
		$sql = 'SELECT user_id from '.USER_TABLE;
		$result = $db->sql_query($sql);
		/*recover every ID*/
		while ($row = $db->sql_fetchrow($result))
		{
			$add_id[] = (int) $row['user_id'];
		}
		$db->sql_freeresult($result);
		

		/*get all real user*/
		foreach($add_id as $user_id)
		{
			/*https://www.phpbb.de/infos/3.1/xref/nav.html?phpbb/user_loader.php.html#get_user*/
			$phpbbuser = get_user($user_id, true);
			$wpuser = get_wp_user($phpbbuser['username']);
			if($wpuser == null)
			{
				create_wp_user($phpbbuser);
			}else{
				//lets be sure it's updated
				update_wp_user($phpbbuser,$wpuser);
			}
		}
		
	}
	
	public function get_wp_user($username)
	{
		return get_user_by( 'user_login', $username );
	}
	
	
	//add user +avatar+specs?
	//exclude banned users
	//share sessions (redir single login) => into WP plugin
	//post to forum
	
	//roles ? default + compare
	//
}
?>