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
	private $request;
	private $proxy;
	private $config;
	private $user;
	private $phpbb_container;
	private $template;

	function main($id, $mode)
	{
		global $request, $template, $user, $phpbb_container,$phpbb_root_path,$config,$phpEx;
		$this->request = $request;
		$this->config = $config;
		$this->user = $user;
		$this->phpbb_container = $phpbb_container;
		$this->template = $template;
		$this->proxy = $this->phpbb_container->get('wardormeur.phpbbwpunicorn.proxy');
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $phpEx;
		$this->active = false;
		//required to list the wordpress roles avaialbes

		if($this->path_valids()){
			$this->active = true;
			$this->request->enable_super_globals();
			define( 'SHORTINIT', TRUE );

			require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-load.php');
			require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/plugin.'.$phpEx);
			require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-admin/includes/user.'.$phpEx);
			require_once($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/capabilities.'.$phpEx);

			$this->request->disable_super_globals();
		}
		$this->user->add_lang('acp/groups');
		$this->page_title = $this->user->lang['ACP_PWU_TITLE'];
		$this->tpl_name = 'phpbbwpunicorn_body';

		add_form_key('unicornfart');

		// Get saved settings.



		if ($this->request->is_set_post('submit'))
		{
			$this->save();
		}

		$this->display();

	}

	private function process(){

	}

	private function save(){
		// Test if form key is valid
		if (!check_form_key('unicornfart'))
		{
			trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
		}
		$wp_path =  $this->request->variable('wp_path','');
		$recache = $this->request->variable('wp_cache','');
		$resync = $this->request->variable('wp_resync','');
		$default_role =  $this->request->variable('wp_default_role','');

		//
		$wp_path_changed = $this->config['phpbbwpunicorn_wp_path'] != $wp_path ? true:false;
		//we set the last dates of sync
		$do_recache = $recache == "on" ? true:false;
		$recache = $do_recache ? time():$this->config['phpbbwpunicorn_wp_cache'];
		$do_resync = $resync == "on" ? true:false;
		$resync = $do_resync ? time():$this->config['phpbbwpunicorn_wp_resync'];

		$settings = array(
			'phpbbwpunicorn_wp_path'		=> $wp_path,
			'phpbbwpunicorn_wp_cache'		=> $recache, //set a date
			'phpbbwpunicorn_wp_resync'	=> $resync, //set a date
			'phpbbwpunicorn_wp_default_role'		=> $default_role //wp role name
		);

		//We savvvve
		foreach($settings as $key=>$value)
		{
			$this->config->set($key,$value);
		}

		//process actions
		if(empty($default_role))
		{
			$default_role = $this->config['phpbbwpunicorn_wp_default_role'];
		}

		if($this->path_valids() &&
			($wp_path_changed  || $do_recache == 'on')){
			//we need to recache, since the wp_path has changed
			$this->proxy->set_config($this->config);
			$this->proxy->cache();
		}

		if($do_resync == 'on' && $this->path_valids() )
		{
			//resync every users
			//we get the service from here in order to not block the regeneration of cache if it's none-working
			$wp_user = $this->phpbb_container->get('wardormeur.phpbbwpunicorn.user');
			$this->proxy->set_config($this->config);
			$wp_user->sync_users();
		}


		//Association of roles/groups
		//we count the number of roles and use the name of wp role to associate. A number wouldnt work as the wordpress isnt saving a proper id
		// a table could have been a solution, but would let the config out of the config table
		if($this->active){
			$this->request->enable_super_globals();
			$roles = new \WP_Roles();
			$this->request->disable_super_globals();
			$index = count($roles->roles);
			$indexes = array_keys($roles->roles);
			for ($i = 0; $i<$index; $i++)
			{
				$temp_wp_r = $this->request->variable("wp_role$i",'');
				$temp_phpbb_r = $this->request->variable('phpbb_role'.$i, array(0));

				$this->config->set('phpbbwpunicorn_role_'.$indexes[$i], serialize($temp_phpbb_r));
			}
		}

		if(!$this->path_valids()){
			trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
		} else {
			trigger_error($this->user->lang['SETTINGS_SUCCESS'] . adm_back_link($this->u_action));
		}

	}



	private function display(){
		$html_roles = '';

		if($this->active){
			$this->request->enable_super_globals();
			$roles = new \WP_Roles();
			$this->request->disable_super_globals();

			foreach($roles->roles as $role=>$roledata){
				$html_roles = $html_roles.'<option value="'.$role.'" '.($role == $this->config['phpbbwpunicorn_wp_default_role']?'selected':'').'>'.$roledata['name'].'</option>';
			}
		}
			$this->template->assign_vars(array(
				'PATH'	=> $this->config['phpbbwpunicorn_wp_path'],
				'CACHE'	=> $this->config['phpbbwpunicorn_wp_cache'] ? date('c',$this->config['phpbbwpunicorn_wp_cache']) : 'Never',
				'RESYNC'	=> $this->config['phpbbwpunicorn_wp_resync'] ? date('c',$this->config['phpbbwpunicorn_wp_resync']) : 'Never' ,
				'DEFAULT_ROLE'	=> $html_roles,
			));
			//Prepare block

		if($this->active){
			foreach($roles->roles as $key_group=>$group)
			{
				$wp_roles[]=array('NAME'=>$group['name'],'ID'=>$key_group);

			}
			foreach($this->get_groups() as $group)
			{
				$phpbb_roles[]=array('NAME'=>$group['group_name'],'ID'=>$group['group_id']);
			}

			//TODO: something bugs me on those loops, like if it was not optimised; likecalling twice the same resource or smthing : must look into it
			//to save, we must erase and resave every config related to mapping
			//Passing by ref issue : modification is lost during the loop, even with a local copy
			for ($i=0;$i<count($wp_roles);$i++)
			{
				$temp_wp_roles = (new \ArrayObject($wp_roles))->getArrayCopy();
				$temp_phpbb_roles = (new \ArrayObject($phpbb_roles))->getArrayCopy();

				//we extract the previously saved data
				if($this->config['phpbbwpunicorn_role_'.$temp_wp_roles[$i]['ID']])
				{
					$phpbb_selected_roles = unserialize($this->config['phpbbwpunicorn_role_'.$temp_wp_roles[$i]['ID']]);
					foreach($temp_phpbb_roles as &$local_phpbb_roles)
					{
					//We compare the extracted id with all the existing roles
						foreach($phpbb_selected_roles as $proles_id)
						{
							if($proles_id == $local_phpbb_roles['ID'] ){
								$local_phpbb_roles['selected'] = 'selected';
							}
						}
					}
				}
				$temp_wp_roles[$i]['selected'] = 'selected';
				$this->template->assign_block_vars('roles',array(
					'wp'=>$temp_wp_roles,
					'phpbb'=>$temp_phpbb_roles,
					'index'=>$i)
				);
			}
		}
		$this->template->assign_vars(array(
			'active'=>$this->active
			));
	}


	/**
	 * function to return groups that are mappable
	 */
	private function get_groups()
	{
		global $db;

		// Don't display any of the default groups
		$ignore_groups = array('BOTS', 'GUESTS');

		$sql = 'SELECT group_name, group_id, group_type
			FROM ' . GROUPS_TABLE . '
			WHERE ' . $db->sql_in_set('group_name', $ignore_groups, true) . '
			ORDER BY group_name ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$groups[] = $row;
		}
		$db->sql_freeresult($result);

		return $groups;
	}

	private function path_valids(){
		try
		{
			if (file_exists($this->config['phpbbwpunicorn_wp_path'].'/wp-load.php') &&
				file_exists($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/plugin.'.$this->phpEx) &&
				file_exists($this->config['phpbbwpunicorn_wp_path'].'/wp-admin/includes/user.'.$this->phpEx) &&
				file_exists($this->config['phpbbwpunicorn_wp_path'].'/wp-includes/capabilities.'.$this->phpEx)
			)
			{
				return true;
			}
		}catch(Exception $err){
			return false;
		}
		return false;
	}
}
