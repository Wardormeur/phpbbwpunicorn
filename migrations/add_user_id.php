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

class add_user_id extends \phpbb\db\migration\migration
{

	// Default settings to start with.

	  public function update_schema()
	  {
	    return
	      array(
	        'add_columns'    => array(
	          $this->table_prefix . 'users'        => array(
	              'wordpress_id'                => array('UINT', NULL),
	              ),
	          ),
	        );
	    }


	  public function revert_schema()
	  {
	    return
	      array(
	        'drop_columns'    => array(
	          $this->table_prefix . 'users'        => array(
	              'wordpress_id'
	              ),
	          ),
	        );
	    }

}
