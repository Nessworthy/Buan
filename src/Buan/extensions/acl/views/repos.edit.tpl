<?php
/* $Id$ */
?>

<h1>Add/Edit Repository</h1>

<form method="post" action="<?php echo UrlCommand::createUrl('acl', 'repos', array('id'=>$repo->id)); ?>">
	<fieldset>
		<legend>Repository details</legend>

		<label>Name</label>
		<input type="text" name="name" value="<?php echo htmlentities($repo->name, ENT_COMPAT, 'UTF-8'); ?>" /><br/>

		<input type="submit" value="Save" />

	</fieldset>
</form>