<?php
/* $Id$ */
use Buan\Config;
$GV->html->addJavascripts(Config::get('core.url.resources').'/js/core/extension-manager.js', Config::get('core.url.resources').'/js/core/json.js');
?>

<h1>Extension Manager</h1>
<table>
	<caption>The following extensions are available</caption>
	<thead>
		<tr>
			<th>Extension title</th>
			<th>Description</th>
			<th>Version</th>
			<th>Options</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($extensions as $name=>$ext): ?>
		<tr>
			<td><?php echo $ext->title; ?></td>
			<td><?php echo htmlspecialchars($ext->description); ?></td>
			<td><?php echo htmlspecialchars($ext->version); ?></td>
			<td id="fe_extBtnContainer_<?php echo $name; ?>">
				<?php
				$buttons = $ext->getManagerButtonMap();
				foreach($buttons as $button):
					$onclick = isset($button['onclick']) ? $button['onclick'] : "callExtensionMethod('{$name}', '{$button['method']}');";
				?>
					<input type="button" id="fe_extBtn_<?php echo $name;?>_<?php echo $button['method']; ?>" value="<?php echo htmlspecialchars($button['label']); ?>" onclick="<?php echo $onclick; ?>" />
				<?php endforeach; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>