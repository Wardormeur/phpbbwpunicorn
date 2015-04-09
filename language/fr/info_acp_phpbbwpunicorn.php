<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <jari@tumba25.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* DO NOT CHANGE
*/
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
$lang = array_merge($lang, array(
	'ACP_GROUP_MISSING'	=> 'The group &quot;%s&quot; does not exist.', // %s is the group name.

	'ACP_MOVE_GROUP'			=> 'Move to group',
	'ACP_MOVE_GROUP_EXPLAIN'	=> 'Name of the group to which users should be affected. This will also be their default group.<br /><strong>If nothing but <em>“No group specified.”</em> is in the drop down then you have not set up any groups.</strong>',

	'ACP_PWU_TITLE'		=> 'PhpbbWPUnicorn',
	'ACP_PWU_SETTINGS'	=> 'PhpbbWPUnicorn Settings',
	'ACP_SYNC'		=> 'Synchronize all users',
	'LAST_TIME'		=> 'Last time',
	'WP_PATH'			=> 'Wordpress path (where can be find the wp-config.php file',
	'WP_CACHE'			=> 'Regenerate temporary Wordpress files',
	'WP_SYNC_AVATAR'			=> 'Use phpbb avatar for wordpress (will resync every user)',
	
	'WP_RESYNC'			=> 'Resynchronize every user (care with article ownership..)',
	'WP_DEFAULT_ROLE'			=> 'Default role used in Wordpress (overwriting the one defined by Wordpress)',
	'SETTINGS_ERROR'		=> 'There was an error saving your settings. Please submit the back trace with your error report.',
	'SETTINGS_SUCCESS'		=> 'The settings were successfully saved',
	));
