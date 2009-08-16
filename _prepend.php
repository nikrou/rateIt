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

if (!defined('DC_RC_PATH')){return;}

# Class
$GLOBALS['__autoload']['rateIt'] = 
	dirname(__FILE__).'/inc/class.rateit.php';

$GLOBALS['__autoload']['rateItRest'] = 
	dirname(__FILE__).'/inc/class.rateit.rest.php';

$GLOBALS['__autoload']['rateItInstall'] = 
	dirname(__FILE__).'/inc/class.rateit.install.php';

$GLOBALS['__autoload']['rateItBackup'] = 
	dirname(__FILE__).'/inc/class.rateit.backup.php';

$GLOBALS['__autoload']['rateItPostsList'] = 
	dirname(__FILE__).'/inc/lib.rateit.list.php';

$GLOBALS['__autoload']['rateItTabs'] = 
	dirname(__FILE__).'/inc/lib.rateit.tabs.php';

# Public urls
$rateit_m = $GLOBALS['core']->blog->settings->rateit_module_prefix;
$rateit_m = $rateit_m ? $rateit_m : 'rateit';

$rateit_p = $GLOBALS['core']->blog->settings->rateit_postform_prefix;
$rateit_p = $rateit_p ? $rateit_p : 'rateitpost';

$rateit_r = $GLOBALS['core']->blog->settings->rateit_service_prefix;
$rateit_r = $rateit_r ? $rateit_r : 'rateitservice';

$GLOBALS['core']->url->register('rateItmodule',
	$rateit_m,'^'.$rateit_m.'/(.+)$',array('urlRateIt','files'));

$GLOBALS['core']->url->register('rateItpostform',
	$rateit_p,'^'.$rateit_p.'/(.+)$',array('urlRateIt','postform'));

$GLOBALS['core']->url->register('rateItservice',
	$rateit_r,'^'.$rateit_r.'/$',array('urlRateIt','service'));

unset($rateit_m,$rateit_p,$rateit_r);

# Generic class (Used on several plugins)
if (!is_callable(array('libImagePath','getArray')))
	require dirname(__FILE__).'/inc/lib.image.path.php';
?>