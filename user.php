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


		require_once($this->phpbb_root_path . 'cache/phpbbwpunicorn_user.'.$this->phpbb_phpEx);


		require_once($this->phpbb_root_path . 'cache/phpbbwpunicorn_formatting.'.$this->phpbb_phpEx);

		$this->request->disable_super_globals();

	}

	public function __destroy(){
		$this->request->disable_super_globals();


	}


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
		if(!is_wp_error($wpuserid)){
			wp_update_user( array ('ID' => $wpuserid, 'role' => $userdata['role'] ) ) ;
			// Update user meta information
			update_user_meta($wpuserid, 'phpbb_userid', $localuser['user_id']);
			//used by old bridge (wp_phpbb_bridge by e-xtnd.it) to link wp_user to user; save it for compatibility :)
		}
		else{
			throw new \Exception("Error Processing user update {$localuser['username']}", 1);

		}
		$this->request->disable_super_globals();//Gosh.. WP.

		return $wpuserid;
	}

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
		$this->request->enable_super_globals();//Gosh.. WP
		//We need to recover the id from the Wordpress part
		if($wpuser == null)
			$wpuser = \get_wp_user($localuser->data['username_clean']);

		$wpuser = $this->prepare_wp_user_array($localuser,$wpuser);
		$wpuser['role'] = $this->get_role($localuser);
		//We restrict to update the role to avoid triggering email for pwd ie
		wp_update_user( array ('ID' => $wpuser['ID'], 'role' => $wpuser['role'] ) ) ;
		//used by old bridge (wp_phpbb_bridge by e-xtnd.it) to link wp_user to user; save it for compatibility :)
		update_user_meta($wpuser, 'phpbb_userid', $localuser['user_id']);

		$this->request->disable_super_globals();//Gosh.. WP.
	}

	//get all users from phpbb & sync them into WP
	public function sync_users(){
		//restrict to "normal" users
		$sql = 'SELECT user_id from '.USERS_TABLE. ' WHERE user_type = 0 OR user_type = 3 OR user_type = 1';
		$result = $this->db->sql_query($sql);
		/*recover every ID*/
		while ($row = $this->db->sql_fetchrow($result))
		{
			$add_id[] = (int) $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		$this->request->enable_super_globals();//Gosh.. WP.
		/*get all real user*/
		foreach($add_id as $user_id)
		{
			/*https://www.phpbb.de/infos/3.1/xref/nav.html?phpbb/user_loader.php.html#get_user*/
			//yes, i know, im requesting twice the user, but fuck it, im lazy.
			//The less SQL and the more core function, the more robust?
			$phpbbuser = $this->user_loader->get_user($user_id, true);
			$wpuser = $this->get_wp_user($phpbbuser['username_clean']);
			if($wpuser == null){
				$this->create_wp_user($phpbbuser);
			}else{
				//lets be sure it's updated
				$this->update_wp_user($phpbbuser,$wpuser->to_array());
			}
		}
		$this->request->disable_super_globals();//Gosh.. WP.

	}

	public function get_wp_user($username)
	{
		return get_user_by( 'slug', $this->sanitize_username($username) );

	}

	public function sanitize_username($username){
		//user WP sanitize_username
		$username = sanitize_user($username, true);
		//custom replacement of %20
		return str_replace(' ','-',$username);
	}

	//exclude banned users on update

	//roles ? default + compare

}
?>
