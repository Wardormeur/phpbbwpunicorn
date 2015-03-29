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
			'core.ucp_prefs_post_update_data' =>'listen_update_wp_user'
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
		$this->bridge_user->create_wp_user($event['user_row']);
	}
	
	
	public function listen_update_wp_user($event)
	{
		$this->bridge_user->update_wp_user($event['user_row']);
	}
	
	
}

?>