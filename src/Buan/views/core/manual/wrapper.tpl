<?php
/* $Id$ */
use Buan\UrlCommand;
?>

<div id="buancore-manual-nav">
	<?php if($footer['previous']!==NULL): ?>
		&laquo; <a href="<?php echo $footer['previous']; ?>">Previous</a> |
	<?php endif; ?>
	<a href="<?php echo UrlCommand::createUrl('core', 'manual'); ?>">Table of contents</a>
	<?php if($footer['next']!==NULL): ?>
		| <a href="<?php echo $footer['next']; ?>">Next</a> &raquo;
	<?php endif; ?>
</div>

<h1><?php echo $chapterH1; ?></h1>

<?php echo $this->getSlot('chapter')->render(); ?>

<div id="buancore-manual-nav">
	<?php if($footer['previous']!==NULL): ?>
		&laquo; <a href="<?php echo $footer['previous']; ?>">Previous</a> |
	<?php endif; ?>
	<a href="<?php echo UrlCommand::createUrl('core', 'manual'); ?>">Table of contents</a>
	<?php if($footer['next']!==NULL): ?>
		| <a href="<?php echo $footer['next']; ?>">Next</a> &raquo;
	<?php endif; ?>
</div>