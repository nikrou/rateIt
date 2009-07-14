<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of rateIt, a plugin for Dotclear 2.
#
# Copyright (c) 2009 JC Denis and contributors
# jcdenis@gdwd.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

class rateItStars
{
	public static function getArray($core)
	{
		if (!defined('RATEIT_URL_PREFIX')) {
			return array(
				'theme'=>array('dir'=>null,'url'=>null),
				'public'=>array('dir'=>null,'url'=>null),
				'rateit'=>array('dir'=>null,'url'=>null),
			);
		}

		return array(
			'theme' => array(
				'dir' => $core->blog->themes_path.'/'.$core->blog->settings->theme.'/img/rateit-stars.png',
				'url' => $core->blog->settings->themes_url.$core->blog->settings->theme.'/img/rateit-stars.png'
			),
			'public' => array(
				'dir' => $core->blog->public_path.'/rateit/rateit-stars.png',
				'url' => $core->blog->host.path::clean($core->blog->settings->public_url).'/rateit/rateit-stars.png'
			),
			'rateit' => array(
				'dir' => dirname(__FILE__).'/../default-templates/img/rateit-stars.png',
				'url' => $core->blog->url.RATEIT_URL_PREFIX.'/img/rateit-stars.png'
			)
		);
	}

	public static function getUrl(&$core)
	{
		$files = self::getArray($core);
		foreach($files as $k => $file) {
			if (file_exists($files[$k]['dir']))
				return $files[$k]['url'];
		}
		return null;
	}

	public static function getPath(&$core)
	{
		$files = self::getArray($core);
		foreach($files as $k => $file) {
			if (file_exists($files[$k]['dir']))
				return $files[$k]['dir'];
		}
		return null;
	}

	public static function getSize(&$core)
	{
		if (!($img = self::getPath($core)))
			return array('w'=>16,'h'=>16);
		else {
			$info = getimagesize($img);
			return array('w'=>$info[0],'h'=>floor($info[1] /3));
		}
	}
}
?>