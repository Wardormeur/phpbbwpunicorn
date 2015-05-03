<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <jari@tumba25.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace wardormeur\phpbbwpunicorn\migrations;

class install_phpbbwpunicorn extends \phpbb\db\migration\migration
{

	public function calculate_wp_default_path(){
		global $phpbb_root_path;
	   
		//find wp install 
		$wp_path = $phpbb_root_path;
		$i = 0; $found=false;
		do{
			//http://php.net/manual/en/class.recursivedirectoryiterator.php#114504
			$directory = new \RecursiveDirectoryIterator($wp_path, \FilesystemIterator::FOLLOW_SYMLINKS);
			//OH WAIT? this PIECE OF SHI*T doesnt work for recursive directory that arent the parent. GOD. WHY. 
			$filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
				//in case we take the time to exclude the self from the previous loop
				//well, it's ez, but im lazy
				return true;
			});
			$iterator = new \RecursiveIteratorIterator($filter);
			//directory dependance of the callback request us to ... redefine the whole goddam thing each loop. cmon..
			
			$files = array();
			$iterator->rewind();
			while( $iterator->valid() || !$found)
			{
				$info = $iterator->current();
				$iterator->next();
			  //alasfiltering must be done here cause filter doesnt filter.meh.
				if(strpos($info->getFilename(),'wp-config.php') === 0)
					$files[] = $info->getPath();
					//actually, yeah, we stop once we found one.
					$found = true;
			}
			
			//We got up 1 lvl in hierarchy
			$wp_path = $wp_path.'../';
			$i++;
		}while ($i<2 || !$found);
		return !empty($files)?$files[0]:"";
	}


	public function update_data()
	{
		global $config;
		$wp_path = $this->calculate_wp_default_path();
		// Default settings to start with.
		$config->set('phpbbwpunicorn_wp_path', $wp_path);
		$config->set('phpbbwpunicorn_wp_default_role', '');
	
		$settings_ary = array(
			'phpbbwpunicorn_wp_path'		=> $wp_path,
			'phpbbwpunicorn_wp_default_role'		=> '',
			'phpbbwpunicorn_wp_resync'	=> 0, //set a date
			'phpbbwpunicorn_wp_cache'		=> 0 //set a date		
		);

		return(array(

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_PWU_TITLE'
			)),

			array('module.add', array(
				'acp',
				'ACP_PWU_TITLE',
				array(
					'module_basename'	=> '\wardormeur\phpbbwpunicorn\acp\phpbbwpunicorn_module',
					'modes'				=> array('settings'),
				),
			)),
		));
	}

	public function revert_data()
	{
		return(array(
			array('config_text.remove', array('phpbbwpunicorn_settings')),

			array('module.remove', array(
				'acp',
				'ACP_PWU_TITLE',
				array(
					'module_basename'	=> '\wardormeur\phpbbwpunicorn\acp\phpbbwpunicorn_module',
				),
			)),
		));
	}
}
