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
	'ACP_GROUP_MISSING'	=> 'Группа &quot;%s&quot; не существует.', // %s is the group name.

	'ACP_MOVE_GROUP'			=> 'Перенести в группу',
	'ACP_MOVE_GROUP_EXPLAIN'	=> 'Имя группы в которую будут определены пользователи. Это также будет их группой по умолчанию.<br /><strong>Если не выбрано <em>“Нет указанной группы.”</em> в выпадающем списке, значит у вас не указано ни одной группы.</strong>',

	'ACP_PWU_TITLE'		=> 'PhpbbWPUnicorn',
	'ACP_PWU_SETTINGS'	=> 'Настройки PhpbbWPUnicorn',
	'ACP_SYNC'		=> 'Синхронизировать всех пользователей',
	'WP_PATH'			=> 'Путь к Wordpress  (где находится файл wp-config.php)',
	'WP_CACHE'			=> 'Перегенерировать временные файлы ',
	'WP_CACHE_INFO' => '(необходимо выполнить, если создание пользователя вызывает ошибку, или если Вы изменили wordpress путь)',
	'WP_SYNC_AVATAR'			=> 'Использовать phpbb аватары для wordpress (будет синхронизирован каждый пользователь)',
	'WP_RESYNC'			=> 'Пересинхронизировать каждого пользователя (осторожно с авторами статей..(еще не обработано))',
	'WP_DEFAULT_ROLE'			=> 'Роль по умолчанию в Wordpress (переназначит указанную в Wordpress)',
	'SETTINGS_ERROR'		=> 'Возникла ошибка при сохранении настроек. Пожалуйста, отправте отчет об ошибке.',
	'SETTINGS_SUCCESS'		=> 'Настройки успешно сохранены',
	'WP_ASSOCIATED_ROLE'	=> 'Ручная ассоциация ролей',
	'WP_MANUAL_SYNC' => 'Manual association',
	'WP_EXSTING_USERNAME' => 'Existing wordpress username',
	'WP_NEW_USERNAME' => 'New wordpress user',
	'WP_USERNAME' => 'Existing phpbb user'
	));
