<?php
/* $Id$ */
?>

<h1>ACL Repositories</h1>

<input type="button" value="Add a repository" onclick="self.location='<?php echo UrlCommand::createUrl('acl', 'repos', array('id'=>0)); ?>';" />

<table>
	<caption>Displaying all available repositories</caption>
	<thead>
		<tr>
			<th>Id</th>
			<th>Repository alias</th>
			<th>Options</th>
		</tr>
	</thead>
	<tfoot>
	<?php foreach($repos as $repo): ?>
		<tr>
			<td><?php echo $repo->id; ?></td>
			<td><?php echo $repo->name; ?></td>
			<td>
				<a href="<?php echo UrlCommand::createUrl('acl', 'repos', array('id'=>$repo->id)); ?>">edit</a> |
				<a href="<?php echo UrlCommand::createUrl('acl', 'repos', array('id'=>$repo->id, 'remove'=>1)); ?>">delete</a>
			</td>
		</tr>
	<?php endforeach; ?>
	<tbody>
	</tbody>
</table>