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

if (!defined('DC_RC_PATH')) return;

$GLOBALS['__autoload']['rateIt'] = 
	dirname(__FILE__).'/inc/class.rateit.php';
$GLOBALS['__autoload']['rateItRest'] = 
	dirname(__FILE__).'/inc/class.rateit.rest.php';
$GLOBALS['__autoload']['rateItInstall'] = 
	dirname(__FILE__).'/inc/class.rateit.install.php';
$GLOBALS['__autoload']['rateItPostsList'] = 
	dirname(__FILE__).'/inc/lib.rateit.list.php';
$GLOBALS['__autoload']['rateItStars'] = 
	dirname(__FILE__).'/inc/lib.rateit.stars.php';

$rateit_u = $GLOBALS['core']->blog->settings->rateit_url_prefix;
$rateit_u = $rateit_u ? $rateit_u : 'rateit';
define('RATEIT_URL_PREFIX',$rateit_u);

$rateit_p = $GLOBALS['core']->blog->settings->rateit_post_prefix;
$rateit_p = $rateit_p ? $rateit_p : 'rateitpost';
define('RATEIT_POST_PREFIX',$rateit_p);

$rateit_r = $GLOBALS['core']->blog->settings->rateit_rest_prefix;
$rateit_r = $rateit_r ? $rateit_r : 'rateitservice';
define('RATEIT_REST_PREFIX',$rateit_r);

unset($rateit_u,$rateit_p,$rateit_r);

$GLOBALS['core']->url->register(
	RATEIT_URL_PREFIX,
	RATEIT_URL_PREFIX,
	'^'.RATEIT_URL_PREFIX.'/(.+)$',
	array('urlRateIt','rateit')
);
?>