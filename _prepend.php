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

global $__autoload, $core;

# Class
$__autoload['rateIt'] = dirname(__FILE__).'/inc/class.rateit.php';
$__autoload['rateItRest'] = dirname(__FILE__).'/inc/class.rateit.rest.php';

# Public urls
$core->url->register('rateItmodule',
	'rateit','^rateit/(.+)$',array('urlRateIt','files'));
$core->url->register('rateItpostform',
	'rateitpost','^rateitpost/(.+)$',array('urlRateIt','postform'));
$core->url->register('rateItservice',
	'rateitservice','^rateitservice/$',array('urlRateIt','service'));

# Generic class (Used on several plugins)
if (!is_callable(array('libImagePath','getArray')))
	require dirname(__FILE__).'/inc/lib.image.path.php';

# Add rateIt report on plugin activityReport
if ($core->activityReport instanceof activityReport)
{
	require_once dirname(__FILE__).'/inc/lib.rateit.activityreport.php';
}
?>