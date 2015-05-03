<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <jari@tumba25.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace wardormeur\phpbbwpunicorn\acp;

class phpbbwpunicorn_module
{
	public	$u_action;

	function main($id, $mode)
	{
		global $request, $template, $user, $phpbb_container,$phpbb_root_path,$config,$phpEx;
		
		$proxy = $phpbb_container->get('wardormeur.phpbbwpunicorn.proxy');
		//not defined?? WTF ?
		//$phpbb_root_path = $phpbb_container->get("core.root_path");
		//$config = $phpbb_container->get('core.config');
		//$phpEx = $phpbb_container->get('core.php_ext');

		//required to list the wordpress roles avaialbes
		$request->enable_super_globals();
		define( 'SHORTINIT', TRUE );
		require_once($config['phpbbwpunicorn_wp_path'].'/wp-load.php');

		require_once($config['phpbbwpunicorn_wp_path'].'/wp-includes/plugin.'.$phpEx);
		require_once($config['phpbbwpunicorn_wp_path'].'/wp-admin/includes/user.'.$phpEx);
		
		require_once($config['phpbbwpunicorn_wp_path'].'/wp-includes/capabilities.'.$phpEx);
		$request->disable_super_globals();
		$user->add_lang('acp/groups');

		$this->page_title = $user->lang['ACP_PWU_TITLE'];
		$this->tpl_name = 'phpbbwpunicorn_body';

		add_form_key('unicornfart');

		// Get saved settings.
	
		
		
		if ($request->is_set_post('submit'))
		{
			// Test if form key is valid
			if (!check_form_key('unicornfart'))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
			$wp_path =  $request->variable('wp_path','');
			$recache = $request->variable('wp_cache','');
			$sync_avatar = $request->variable('wp_sync_avatar','');
			$default_role =  $request->variable('wp_default_role','');
			if(empty($default_role)){
				$default_role = $config['phpbbwpunicorn_wp_default_role'];
				echo 'cf'.$default_role;
			}
			if(( $config['phpbbwpunicorn_wp_path'] != $wp_path && !empty($wp_path)) 
				|| $recache == 'on'){
				//we need to recache, since the wp_path has changed
				$proxy->cache();
			}
			if($resync == 'on' ){
				//resync every users
				//we get the service from here in order to not block the regeneration of cache if it's none-working
				$wp_user = $phpbb_container->get('wardormeur.phpbbwpunicorn.user');
				$wp_user->sync_users();
			}
			//we set the last dates of sync
			$recache = $recache=="on"?time():$config['phpbbwpunicorn_wp_cache'];
			$resync = $resync=="on"?time():$config['phpbbwpunicorn_wp_resync'];

			// Default settings in case something went wrong with the install.
			$settings = array(
				'phpbbwpunicorn_wp_path'		=> $wp_path,
				'phpbbwpunicorn_wp_cache'		=> $recache, //set a date
				'phpbbwpunicorn_wp_resync'	=> $resync, //set a date
				'phpbbwpunicorn_wp_default_role'		=> $default_role //wp role name
			);
			//We savvvve
			foreach($settings as $key=>$value){
				$config->set($key,$value);
			}
			
			if ($success === false)
			{
				trigger_error($user->lang['SETTINGS_ERROR'] . adm_back_link($this->u_action), E_USER_ERROR);
			}
			else
			{
				trigger_error($user->lang['SETTINGS_SUCCESS'] . adm_back_link($this->u_action));
			}
		}
		$request->enable_super_globals();
		$roles = new \WP_Roles();
		$request->disable_super_globals();

		foreach($roles->roles as $role=>$roledata){
			$html_roles = $html_roles.'<option value="'.$role.'" '.($role == $config['phpbbwpunicorn_wp_default_role']?'selected':'').'>'.$roledata['name'].'</option>';
		}
		
		$template->assign_vars(array(
			'PATH'	=> $config['phpbbwpunicorn_wp_path'],
			'CACHE'	=> $config['phpbbwpunicorn_wp_cache'],
			'RESYNC'	=> $config['phpbbwpunicorn_wp_resync'],
			'DEFAULT_ROLE'	=> $html_roles
		));
	}

	/**
	 * function to return groups that are mappable
	 */
	private function get_groups($group_selected)
	{
		global $db, $user;

		// Don't display any of the default groups
		// highly doubt an admin would want to ban someone into a default group
		$ignore_groups = array('BOTS', 'GUESTS', 'REGISTERED', 'REGISTERED_COPPA', 'NEWLY_REGISTERED', 'ADMINISTRATORS', 'GLOBAL_MODERATORS');

		$sql = 'SELECT group_name, group_id, group_type
			FROM ' . GROUPS_TABLE . '
			WHERE ' . $db->sql_in_set('group_name', $ignore_groups, true) . '
			ORDER BY group_name ASC';
		$result = $db->sql_query($sql);

		$selected = ($group_selected == 0) ? ' selected="selected"' : '';
		$s_group_options = "<option value='0'$selected>&nbsp;{$user->lang['NO_GROUP']}&nbsp;</option>";
		while ($row = $db->sql_fetchrow($result))
		{
			$selected = ($row['group_id'] == $group_selected) ? ' selected="selected"' : '';
			$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name'];
			$s_group_options .= "<option value='{$row['group_id']}'$selected>$group_name</option>";
		}
		$db->sql_freeresult($result);

		return $s_group_options;
	}
}
