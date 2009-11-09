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
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')){return;}

require_once dirname(__FILE__).'/inc/lib.rateit.index.php';

$requests = rateItTabs::requests($core);
$combos = rateItTabs::combos($core);
$params = rateItTabs::params($core,$requests,$combos);

?>
<html>
 <head>
  <title><?php echo __('Rate it'); ?></title>
  <?php echo dcPage::jsLoad('js/_posts_list.js').dcPage::jsToolBar().dcPage::jsPageTabs($requests->tab); ?>
  <script type="text/javascript">
    $(function() {
		$('#post-options-title').toggleWithLegend($('#post-options-content'),{cookie:'dcx_rateit_admin_post_options'});
		$('#post-entries-title').toggleWithLegend($('#post-entries-content'),{cookie:'dcx_rateit_admin_post_entries'});
		$('#comment-options-title').toggleWithLegend($('#comment-options-content'),{cookie:'dcx_rateit_admin_comment_options'});
		$('#comment-entries-title').toggleWithLegend($('#comment-entries-content'),{cookie:'dcx_rateit_admin_comment_entries'});
		$('#gallery-options-title').toggleWithLegend($('#gallery-options-content'),{cookie:'dcx_rateit_admin_gallery_options'});
		$('#gallery-gals-title').toggleWithLegend($('#gallery-gals-content'),{cookie:'dcx_rateit_admin_gals_entries'});
		$('#gallery-galitems-title').toggleWithLegend($('#gallery-galitems-content'),{cookie:'dcx_rateit_galitems_gallery_entries'});
    });
  </script>
<?php


# --BEHAVIOR-- adminRateItHeader
$core->callBehavior('adminRateItHeader',$core);


?>
 </head>
<body>
<h2 style="padding:8px 0 8px 34px;background:url(index.php?pf=rateIt/icon-b.png) no-repeat;">
<?php echo html::escapeHTML($core->blog->name).' &rsaquo; '.__('Rate it'); ?></h2>

<?php if ('' != $requests->msg) :  ?>
 <p class="message"><?php echo $requests->msg; ?></p>
<?php endif;

rateItTabs::summaryTab($core);
rateItTabs::detailTab($core,$requests);
rateItTabs::postTab($core,$requests,$params,$combos);
rateItTabs::commentTab($core,$requests);
rateItTabs::categoryTab($core,$requests);
rateItTabs::tagTab($core,$requests);
rateItTabs::galleryTab($core,$requests);


# --BEHAVIOR-- adminRateItTabs
$core->callBehavior('adminRateItTabs',$core);


rateItTabs::settingsTab($core,$requests,$combos);

echo dcPage::helpBlock('rateIt'); ?>

<hr class="clear"/>
<p class="right">
rateIt - <?php echo $core->plugins->moduleInfo('rateIt','version'); ?>&nbsp;
<img alt="RateIt" src="index.php?pf=rateIt/icon.png" />
</p>
</body>
</html>