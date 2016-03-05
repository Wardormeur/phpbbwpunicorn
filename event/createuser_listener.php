<?php

namespace wardormeur\phpbbwpunicorn\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \wardormeur\phpbbwpunicorn\user;


class createuser_listener implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
            'core.user_add_after' => 'listen_create_wp_user',
			      'core.ucp_prefs_post_update_data' => 'listen_update_wp_user',
            'core.group_add_user_after' => 'listen_group_add_user',
            'core.group_delete_user_after' => 'listen_group_delete_user',

        );
    }

	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\auth\auth $auth,
		\phpbb\request\request $request,
		\phpbb\cache\driver\driver_interface $cache,
		\wardormeur\phpbbwpunicorn\user $bridge_user,
		$phpbb_root_path,
		$phpExt)
	{
		$this->template		= $template;
		$this->user			= $user;
		$this->db			= $db;
		$this->auth			= $auth;
		$this->request		= $request;
		$this->cache		= $cache;
		$this->bridge_user 	= $bridge_user;
		$this->root_path	= $phpbb_root_path;
		$this->php_ext		= $phpExt;

	}

	/*Naming : WP functions use pattern wp_verb_compl, so we do a smhtin verb_wp_compl here when encapsulating*/
	/*Doc : https://wiki.phpbb.com/Category:Functions_user*/

	public function listen_create_wp_user($event)
	{
		$local_user = $event['user_row'];
		$local_user['user_id'] = $event['user_id'];
		if( in_array($local_user['user_type'], array(0,1,3))){
			$this->bridge_user->create_wp_user($local_user);
		}
	}


	public function listen_update_wp_user($event)
	{
		//no check here, we suppose the user was allowed to be created
		$this->bridge_user->update_wp_user($event['user_row']);
	}


	public function listen_group_add_user($event)
	{
    //$group_id, $group_name, $pending, $user_id_ary, $username_ary
		//no check here, we suppose the user was allowed to be created
		$this->bridge_user->sync_users($event['user_id_ary']);
	}


	public function listen_group_delete_user($event)
	{
    //$group_id, $group_name, $user_id_ary, $username_ary
		//no check here, we suppose the user was allowed to be created
		$this->bridge_user->sync_users($event['user_id_ary']);
	}


}

?>
