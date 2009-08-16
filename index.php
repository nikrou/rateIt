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

<?php if ('' != $requests->msg) :  ?><p class="message"><?php echo $requests->msg; ?></p><?php endif;

rateItTabs::summaryTab($core);
rateItTabs::detailTab($core,$requests);
rateItTabs::postTab($core,$requests,$params,$combos);
rateItTabs::commentTab($core,$requests);
rateItTabs::categoryTab($core,$requests);


# --BEHAVIOR-- adminRateItTabs
$core->callBehavior('adminRateItTabs',$core);


rateItTabs::settingsTab($core,$requests,$combos);
rateItTabs::uninstallTab($core);


?>
<div class="multi-part" id="about" title="<?php echo __('About'); ?>">
<div class="two-cols">
<div class="col">
<h3>Version:</h3>
<ul><li>rateIt <?php echo $core->plugins->moduleInfo('rateIt','version'); ?></li></ul>
<h3>Support:</h3>
<ul>
<li><a href="http://dotclear.jcdenis.com/">Author's blog</a></li>
<li><a href="http://forum.dotclear.net/viewtopic.php?id=39801">Dotclear forum</a></li>
<li><a href="http://lab.dotclear.org/wiki/plugin/rateIt">Dotclear lab</a></li>
</ul>
<h3>Copyrights:</h3>
<ul>
<li><strong>Files</strong><br />
These files are parts of rateIt, a plugin for Dotclear 2.<br />
Copyright (c) 2009 JC Denis and contributors<br />
Licensed under the GPL version 2.0 license.<br />
<a href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html">http://www.gnu.org/licenses/old-licenses/gpl-2.0.html</a>
</li>
<li><strong>Images</strong><br />
Some icons from Silk icon set 1.3 by Mark James at:<br />
<a href="http://www.famfamfam.com/lab/icons/silk/">http://www.famfamfam.com/lab/icons/silk/</a><br />
under a Creative Commons Attribution 2.5 License<br />
<a href="http://creativecommons.org/licenses/by/2.5/">http://creativecommons.org/licenses/by/2.5/</a>.
</li>
</ul>
<h3>Tools:</h3>
<ul>
<li>Traduced with Dotclear plugin Translater,</li>
<li>Packaged with Dotclear plugin Packager.</li>
<li>Used jQuery Star Rating Plugin v3.12 by <a href="http://www.fyneworks.com/jquery/star-rating/">Fyneworks</a></li>
</ul>
</div>
<div class="col">
<pre><?php readfile(dirname(__FILE__).'/release.txt'); ?></pre>
</div>
</div>
</div>
<hr class="clear"/>
<p class="right">
rateIt - <?php echo $core->plugins->moduleInfo('rateIt','version'); ?>&nbsp;
<img alt="RateIt" src="index.php?pf=rateIt/icon.png" />
</p>
</body>
</html>