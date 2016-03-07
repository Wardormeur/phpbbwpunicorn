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

		//TODO: remove config from here
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
		if(!defined('SHORTINIT'))
		{
			define( 'SHORTINIT', TRUE );
		}

		//TODO : check active as this is instanciated on the plugin activation
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

		require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/plugin.'.$phpEx);
		require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-admin/includes/user.'.$phpEx);
		require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/capabilities.'.$phpEx);
		require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/general-template.'.$phpEx);

		if(file_exists($this->phpbb_root_path . 'cache/phpbbwpunicorn_user.'.$this->phpbb_phpEx) &&
		file_exists($this->phpbb_root_path . 'cache/phpbbwpunicorn_formatting.'.$this->phpbb_phpEx)){
			require_once($this->phpbb_root_path . 'cache/phpbbwpunicorn_user.'.$this->phpbb_phpEx);
			require_once($this->phpbb_root_path . 'cache/phpbbwpunicorn_formatting.'.$this->phpbb_phpEx);
		}

		//since 4.4, classes are externalised
		//https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-roles.php
		//And that's why I'd prefer to stop dev for <v4.4, because it's a fuckking mess whereas there is a rest API on 4.4+
		if(!class_exists('WP_Role') && !class_exists('WP_Roles') && !class_exists('WP_User')){
			//TODO: move bridge-related functions to a single fiel to avoid multiple injections of the same file
			require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/class-wp-role.'.$this->phpbb_phpEx);
			require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/class-wp-roles.'.$this->phpbb_phpEx);
			require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/class-wp-user.'.$this->phpbb_phpEx);
			require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/rest-api.'.$this->phpbb_phpEx);
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
		$userdata['role'] = $this->handle_extra_roles($userdata['role']);
		if(!is_null($userdata['role'])){
			$this->prepare_wp_user_array ($localuser,$userdata);

			//wp_insert_user https://codex.wordpress.org/Function_Reference/wp_insert_user
			//wp_insert_user doesnt apply role on creation, only update; thx doc not saying that
			$wpuserid = wp_insert_user($userdata);
			if(!is_wp_error($wpuserid)){
				wp_update_user( array ('ID' => $wpuserid, 'role' => $userdata['role'] ) ) ;

				//Add reference to our phpbb table
				$sql = "UPDATE ".USERS_TABLE. " SET wordpress_id = $wpuserid WHERE user_id = {$localuser['user_id']}";
				$this->db->sql_query($sql);
			}
			else{
				$this->errors['users'][] = $localuser['username'];
			}
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
		$potential_roles[] = !empty($role) ? $role :[];
		$roles = $this->get_roles();
		$user_groups =  group_memberships(false,$localuser['user_id']);
		//We default the returned role to the config's default one
		$selected_role = $role;

		if($user_groups){
			//pooooooooooor design, gush.
			foreach(array_reverse(array_keys($roles->roles)) as $wp_role)// we reverse it to put the importants roles (as admin/editor) as the last choice
			{
				$phpbb_roles = unserialize($this->config['phpbbwpunicorn_role_'.$wp_role]);
				foreach($phpbb_roles as $phpbb_role)
				{
					foreach($user_groups as $user_group)
					{
						if ($phpbb_role == $user_group["group_id"])
						{
							$potential_roles[] = $wp_role;
						}
					}
				}
			}
			//Which one are we supposed to return? Lol. first of order per ID Desc?
			$nb_possibilities = count($potential_roles);
			//if we had an user group which is not associated with anything
			if($nb_possibilities > 1){
				$selected_role = $potential_roles[$nb_possibilities-1];
			}else{
				//reuse the default value of $role
			}

		}


		return $selected_role;
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
		$wpuser['role'] = $this->handle_extra_roles($wpuser['role'], false);
		if(!is_null($wpuser['role'])){
			//We restrict to update the role to avoid triggering email for pwd ie
			wp_update_user( array ('ID' => $wpuser['ID'], 'role' => $wpuser['role'] ) ) ;
		}else{
			//We delete the user and reassign all articles to the user 1 of WP, we expect it to be the account creating the WP, and so , the administrator
			//Jut to be sure, update the reference to our phpbb table
			wp_delete_user($wpuser['ID'], 1);
			$wpuser['ID'] = 'NULL';
		}
		//Jut to be sure, update the reference to our phpbb table
		$sql = "UPDATE ".USERS_TABLE. " SET wordpress_id = ".$wpuser['ID']." WHERE user_id =".$localuser['user_id'];
		$this->db->sql_query($sql);
		$this->request->disable_super_globals();//Gosh.. WP.
	}

	/**
	 * function to resynchronize every field of either every user, or a selected list; into WP
	 * @param  [array] $user_id_ary [selected list of users]
	 */
	 //TODO : support sync by username ? unsafe/recovery
	public function sync_users($user_id_ary = null){
		//By default take every user
		//restrict to "normal" users
		$sql = 'SELECT user_id, wordpress_id from '.USERS_TABLE. ' WHERE (user_type = 0 OR user_type = 3 OR user_type = 1) AND user_id != 1';
		if($user_id_ary != null){
			$sql .=' AND '. $this->db->sql_in_set('user_id', $user_id_ary);
		}
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
			if(empty($user['wordpress_id'])){
				// !username_exists($phpbbuser['username'])  &&
				//the function to check if an user exists are having, again, the same name than the phpbb one's.
				try{
					$this->create_wp_user($phpbbuser);
				}catch(\Exception $e){
					//Just to be sure we don't break the loop
					var_dump('not supposed to happen');
				}
			}else{
				//lets be sure it's updated
				$wpuser = $this->get_wp_user($user['wordpress_id']);
				if($wpuser !== false){
					$wp_array = $wpuser->to_array();
					$this->update_wp_user($phpbbuser, $wp_array);
				}
			}
		}
		$this->check_errors();

	}

	public function get_wp_user($data, $slug = 'id')
	{
		return get_user_by( $slug, $data );
	}

	/**
	 * Return a phpbb user from username
	 * @param  [string] $username [clean username used to search the user]
	 * @return [object]           [the phpbbUser]
	 */
	public function get_phpbb_user_by_username($username){
			$ids = null;
			$usernames = [$username];
			$return = false;
			user_get_id_name($ids, $usernames , false);
		if(sizeof($ids)){
				$return = $this->user_loader->get_user($ids[0], false);
				$return['user_id'] = $ids[0];
		}
			return $return;
	}


  /**
   * Compatible function for sanitizing username between WP&PHPBB
   * @param  [String] $username [description]
   * @return [String] cleanedUsername [description]
   */
	public function sanitize_username($username){
		//user WP sanitize_username
		$username = sanitize_user($username, true);
		//custom replacement of %20
		return str_replace(' ','-',$username);
	}

  /**
   * Returns a SINGLE user corresponding to the username
   * @param  [String] $username [description]
   * @return [PHPBBUser]           [description]
   */

  /**
   * Check errors returned when doing sync
   */
	private function check_errors(){
		//TODO : support more type of errors?
		//TODO : log it for user creation
		if(sizeof($this->errors) > 0){
			$error_users = implode(', ',$this->errors['users']);
			trigger_error("Error Processing user sync, please sync manually the following users : $error_users ");
		}
		$this->errors = [];
	}

	/**
	 * Return WP roles as WP Object
	 * @return [WPRole] [description]
	 */
	public function get_roles(){
		$wp_roles = new \WP_Roles();
		//TODO : translation support
		$wp_roles->roles["none"] = array("name"=>"None");
		$wp_roles->roles["no-sync"] = array("name"=>"No-sync");
		return $wp_roles;
	}

  /**
   * return appropriate values to be used for the sync script
   * @param  [type] $role [description]
   * @param  [type] $soft [description]
   * @return [type]       [description]
   */
	private function handle_extra_roles($role, $soft = false){
		if($role=='none' || $role == 'no-sync' && $soft === true){
			$role='';
		}

		if($role=='no-sync' && $soft === false){
			$role = null;
		}
		return $role;
	}

}
?>
