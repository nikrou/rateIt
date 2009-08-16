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

if (!defined('DC_CONTEXT_ADMIN')){return;}
//*
if ($core->plugins->moduleExists('rateItComment') 
 || $core->plugins->moduleExists('rateItCategory')) {
	throw new Exception('You must uninstall rateItComment and rateItCategory before installing rateIt 0.9 and higher');
	return false;
}
//*/
$new_version = $core->plugins->moduleInfo('rateIt','version');
$old_version = $core->getVersion('rateIt');

if (version_compare($old_version,$new_version,'>=')) return;

try {
	rateItInstall::setTable($core);
	rateItInstall::setSettings($core,true);
	rateItInstall::setVersion($core);
	return true;
}
catch (Exception $e) {
	$core->error->add($e->getMessage());
}
return false;
?>