<?php


namespace wardormeur\phpbbwpunicorn;



class user{
	public function __construct(\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\user $user,
		\phpbb\user_loader  $user_loader,
		$phpEx,
		$phpbb_root_path)
	{
		global $phpbb_container;

		$this->config = $config;
		$this->db = $db;
		$this->phpbb_user = $user;
		$this->request = $request;
		$this->user = $user;
		$this->user_loader = $user_loader;
		$this->errors = [];

		$this->phpbb_phpEx = $phpEx;
		$this->phpbb_root_path = $phpbb_root_path;

		/*Require WP includes*/
		$path_to_wp = $config['phpbbwpunicorn_wp_path'];
		define( 'WP_USE_THEMES', FALSE );
		define( 'SHORTINIT', TRUE );

		$this->request->enable_super_globals();//Gosh.. WP.
		require_once( $path_to_wp.'/wp-load.'.$phpEx );

		require_once($this->phpbb_root_path.'includes/functions_user.'.$this->phpbb_phpEx);
		//VERY MINIMAL FUCKING CONF MODAFUCKERS!!1
		require_once($path_to_wp.'/wp-includes/l10n.'.$phpEx);
		require_once($path_to_wp.'/wp-includes/post.'.$phpEx);
		require_once($path_to_wp.'/wp-includes/query.'.$phpEx);
		require_once($path_to_wp.'/wp-includes/taxonomy.'.$phpEx);
		require_once($path_to_wp.'/wp-includes/capabilities.'.$phpEx);
		require_once($path_to_wp.'/wp-includes/meta.'.$phpEx);
		require_once($path_to_wp.'/wp-includes/link-template.'.$phpEx);
		require_once($path_to_wp.'/wp-includes/pluggable.'.$phpEx);
		require_once($path_to_wp.'/wp-includes/kses.'.$phpEx);

		if(file_exists($this->phpbb_root_path . 'cache/phpbbwpunicorn_user.'.$this->phpEx) &&
		file_exists($this->phpbb_root_path . 'cache/phpbbwpunicorn_formatting.'.$this->phpEx)){
			require_once($this->phpbb_root_path . 'cache/phpbbwpunicorn_user.'.$this->phpbb_phpEx);
			require_once($this->phpbb_root_path . 'cache/phpbbwpunicorn_formatting.'.$this->phpbb_phpEx);
		}

		$this->request->disable_super_globals();

	}

	public function __destroy(){
		$this->request->disable_super_globals();


	}

	/**
	 * [create_wp_user : create a WP user based upon an arrayish PHPPBUser]
	 * @param  [PHPPBUser] $localuser [description]
	 * @return [ID]            [wpuserid]
	 */
	public function create_wp_user($localuser)
	{
		$this->request->enable_super_globals();//Gosh.. WP.

		//Init data
		$userdata['user_login'] =  $localuser['username'];
		$userdata['user_nicename'] =  $this->sanitize_username($localuser['username_clean']);
		$userdata['display_name'] =  $localuser['username'];
		$userdata['user_pass'] = wp_generate_password();
		$userdata['role'] = $this->get_role($localuser);
		$this->prepare_wp_user_array ($localuser,$userdata);

		//wp_insert_user https://codex.wordpress.org/Function_Reference/wp_insert_user
		//wp_insert_user doesnt apply role on creation, only update; thx doc not saying that
		$wpuserid = wp_insert_user($userdata);
		var_dump($wpuserid);
		if(!is_wp_error($wpuserid)){
			wp_update_user( array ('ID' => $wpuserid, 'role' => $userdata['role'] ) ) ;

			//Add reference to our phpbb table
			$sql = "UPDATE ".USERS_TABLE. " SET wordpress_id = $wpuserid WHERE user_id = {$localuser['user_id']}";
			var_dump($sql);
			$this->db->sql_query($sql);
		}
		else{
			$this->errors['users'][] = $localuser['username'];
		}
		$this->request->disable_super_globals();//Gosh.. WP.

		return $wpuserid;
	}

	/**
	 * [get_role for a specific user, based upon his user_group, ordered by priority (1rst admin->modo->user)
	 * As Wp only supports only 1 group, it only select the more important one]
	 * @param  [type] $localuser [description]
	 * @return [type]            [description]
	 */
	private function get_role($localuser){
		$role = $this->config['phpbbwpunicorn_wp_default_role'];
		//TODO: this actually shows a bad design, requiring me to loop over roles whereas a bi-directionnal array could have mesaved from that
		//stock every role into a single multi dim array?
		$potential_roles[] = $role?$role:[];
		$roles = new \WP_Roles();


		foreach(array_reverse(array_keys($roles->roles)) as $wp_role)// we reverse it to put the importants roles (as admin/editor) as the last choice
		{
			$phpbb_roles = unserialize($this->config['phpbbwpunicorn_role_'.$wp_role]);
			foreach($phpbb_roles as $phpbb_role)
			{

				$user_groups =  group_memberships(false,$localuser['user_id']);

				foreach($user_groups as $user_group)
				{
					if ($phpbb_role == $user_group["group_id"])
					{
						$potential_roles[] = $wp_role;
					}
				}
			}
		}
		//pooooooooooor design, gush.
		//Which one are we supposed to return? Lol. first of order per ID Desc?

		return $potential_roles[count($potential_roles)-1];
	}


	private function prepare_wp_user_array($localuser, $wpuser)
	{
        $wpuser['user_email'] = $localuser['user_email'];
        $wpuser['nickname'] = $localuser['username'];
        /*What else is mappable ? */

		return $wpuser;
	}


	public function update_wp_user($localuser, $wpuser)
	{
		$this->request->enable_super_globals();//Gosh.. WP

		$wpuser = $this->prepare_wp_user_array($localuser, $wpuser);
		$wpuser['role'] = $this->get_role($localuser);
		//We restrict to update the role to avoid triggering email for pwd ie
		wp_update_user( array ('ID' => $wpuser['ID'], 'role' => $wpuser['role'] ) ) ;

		//Jut to be sure, update the reference to our phpbb table
		$sql = "UPDATE ".USERS_TABLE. " SET wordpress_id =  {$wpuser['ID']} WHERE user_id = {$localuser['user_id']}";
		$this->db->sql_query($sql);

		$this->request->disable_super_globals();//Gosh.. WP.
	}

	//get all users from phpbb & sync them into WP
	public function sync_users(){
		//restrict to "normal" users
		$sql = 'SELECT user_id, wordpress_id from '.USERS_TABLE. ' WHERE (user_type = 0 OR user_type = 3 OR user_type = 1) AND user_id != 1';
		$result = $this->db->sql_query($sql);
		/*recover every ID*/
		while ($row = $this->db->sql_fetchrow($result))
		{
			$phpbb_user[] = $row;
		}
		$this->db->sql_freeresult($result);
		$this->request->enable_super_globals();//Gosh.. WP.
		/*get all real user*/
		foreach($phpbb_user as $user)
		{
			/*https://www.phpbb.de/infos/3.1/xref/nav.html?phpbb/user_loader.php.html#get_user*/
			//yes, i know, im requesting twice the user, but fuck it, im lazy.
			//The less SQL and the more core function, the more robust?
			$phpbbuser = $this->user_loader->get_user($user['user_id'], true);
			if($user['wordpress_id'] == null){
				// !username_exists($phpbbuser['username'])  &&
				//the function to check if an user exists are having, again, the same name than the phpbb one's.
				try{
					$this->create_wp_user($phpbbuser);
				}catch(\Exception $e){
					//Just to be sure we don't break the loop
				}
			}else{
				//lets be sure it's updated
				$wpuser = $this->get_wp_user($user['wordpress_id']);
				$this->update_wp_user($phpbbuser,$wpuser->to_array());
			}
		}
		$this->check_errors();

	}

	public function get_wp_user($data, $slug = 'id')
	{
		return get_user_by( $slug, $data );
	}

	public function sanitize_username($username){
		//user WP sanitize_username
		$username = sanitize_user($username, true);
		//custom replacement of %20
		return str_replace(' ','-',$username);
	}

	public function get_phpbb_user_by_username($username){
		$ids = null;
		$usernames = [$username];
		$return = false;
		user_get_id_name($ids, $usernames , false);
		if(sizeof($ids)){
			var_dump($ids);
			$return = $this->user_loader->get_user($ids[0], false);
			$return['user_id'] = $ids[0];
		}
		return $return;
	}

	private function check_errors(){
		//TODO : support more type of errors?
		//TODO : log it for user creation
		if(sizeof($this->errors) > 0){
			$error_users = implode(', ',$this->errors['users']);
			trigger_error("Error Processing user sync, please sync manually the following users : $error_users ");
		}
		$this->errors = [];
	}

}
?>
