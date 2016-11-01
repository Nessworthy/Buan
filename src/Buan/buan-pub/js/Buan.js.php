<?php
/*
# $Id$
#
# Provides a library of JavaScript functions that can be used to mimic certain
# functionality within the server-side framework.
#
# Include this script in your View templates by adding the following code
# somewhere before the </body> tag:
#	<?php include Config::get('core.dir.resources').'/js/Buan.js.php'; ?>
*/
use \Buan\Config;
?>
<!-- Buan JavaScript -->
<script type="text/javascript" src="<?php echo Config::get('core.url.resources'); ?>/js/Buan.Config.js"></script>
<script type="text/javascript" src="<?php echo Config::get('core.url.resources'); ?>/js/Buan.UrlCommand.js"></script>
<script type="text/javascript">
Buan.Config.set('app.command.urlPrefix', '<?php echo Config::get('app.command.urlPrefix'); ?>');
Buan.Config.set('app.command.parameter', '<?php echo Config::get('app.command.parameter'); ?>');
Buan.Config.set('app.domain', '<?php echo Config::get('app.domain'); ?>');
Buan.Config.set('app.urlRoot', '<?php echo Config::get('app.urlRoot'); ?>');
</script>
<!-- end -->
