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
		global $db, $request, $template, $user;

		$user->add_lang('acp/groups');

		$this->page_title = $user->lang['ACP_PWU_TITLE'];
		$this->tpl_name = 'phpbbwpunicorn_body';

		add_form_key('unicornfart');

		// Get saved settings.
		/*$sql = 'SELECT * FROM ' . CONFIG_TEXT_TABLE . "
				WHERE config_name = 'banhammer_settings'";
		$result = $db->sql_query($sql);
		$settings = $db->sql_fetchfield('config_value');
		$db->sql_freeresult($result);

		if (!empty($settings))
		{
			$settings = unserialize($settings);
		}
		else
		{
			// Default settings in case something went wrong with the install.
			$settings = array(
				'ban_email'		=> 1,
				'ban_ip'		=> 0,
				'del_avatar'	=> 0,
				'del_privmsgs'	=> 0,
				'del_posts'		=> 0,
				'del_profile'	=> 0,
				'del_signature'	=> 0,
				'group_id'		=> 0,
				'sfs_api_key'	=> '',
			);
		}
		*/
		
		if ($request->is_set_post('submit'))
		{
			// Test if form key is valid
			if (!check_form_key('unicornfart'))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
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

		$template->assign_vars(array(
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
