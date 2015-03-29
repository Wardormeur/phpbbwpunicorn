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

class phpbbwpunicorn_info
{
	function module()
	{
		return array(
			'filename'	=> '\wardormeur\phpbbwpunicorn\acp\phpbbwpunicorn_module',
			'title'	=> 'ACP_PWU_TITLE',
			'version'	=> '0.0.1',
			'modes'	=> array(
				'settings'	=> array('title' => 'ACP_PWU_SETTINGS',
									'auth' => 'ext_wardormeur/phpbbwpunicorn && acl_a_user',
									'cat' => array('ACP_PWU_TITLE')),
			),
		);
	}
}
