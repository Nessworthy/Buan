<?php
/* $Id: app_login.tpl 36 2007-01-19 20:50:17Z james $ */
use Buan\UrlCommand;
?>

<h1>Application Login</h1>
<p>
	The page you have requested requires that you are logged in using the application password. This can be found in your application's configuration file.
</p>
<p>
	<form action="<?php echo UrlCommand::createUrl('core', 'app-login'); ?>" method="post">
		<label for="fe_password">
			Password:
		</label>
		<input type="password" id="fe_password" name="password" value="" />
		<input type="submit" id="fe_btnSubmit" name="btnSubmit" value="Login" />
		<input type="hidden" name="refererUrl" value="<?php echo htmlentities($rUrl); ?>" />
	</form>
</p>