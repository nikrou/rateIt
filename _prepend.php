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
?>