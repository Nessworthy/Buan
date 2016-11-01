$Id$

Extension "acl"
===============
Provides an authorization API that can be layered on top of your Models.

Preparation
===========
1. All Models that are to act as an AclRole or AclResource MUST have the relevant database field (or both if it acts as both role and resource):
	acl_role_id unsigned int
	acl_resource_id unsigned int

A custom Model can only be related to a single AclRole/Resource.

2. In each of your Model classes, you must have the following:
	class MyModel extends Model {
		public $acl;

		public function __construct($modelName=NULL) {
			parent::__construct($modelName);
			$this->acl = new AclObject($this, AclObject::ROLE);
		}
	}

3. In your "bootstrap.php" script, you must specify which of your Models will act as roles/resources, eg:
	Extension::getExtensionByName('acl')->defineRoleModels('Person', 'Usergroup');
	Extension::getExtensionByName('acl')->defineResourceModels('Document', 'Project', 'Task');

This will set up the required ModelRelation definitions for you.

Notes
=====
If using the AclObject class to associate an AclRole with a custom Model, then you must remeber to manually save the role because saving rh custom Model is not guaranteed to filter through and save any changes you've made to the role-associations. eg:
	$p = Model::create('Person');
	$p->acl->getRole()->addToRole('Superusers');
	$p->getModelManager()->save($p);		// Will save $p, but will NOT save the new association with 'Superusers' because neither $p or $p's role (via ->acl->getRole()) have themselves changed.
	$p->acl->save();						// Correct way to do it - this saves the AclRole and all associated models.

Usage examples
==============

Create ACL repository
---------------------
$repo = Model::create('AclRepo');
$repo->name = "My Repo";
$repo->getModelManager()->save($repo);

Load existing repository by name
--------------------------------
$repo = Model::create('AclRepo');
$repo->name = "My Repo";
$repo->getModelManager()->loadByName($repo);

Create a new AclRole, add to repository and save
------------------------------------------------
$role = Model::create('AclRole');
$repo->addRelatedModel($role);
$role->getModelManager->save($role);

Accessing the AclResource Model instance in a custom Model
----------------------------------------------------------
$my = Model::create('MyCustom');
$my->acl->getResource()->alias = "Folder";

Granting/revoking permissions
-----------------------------
$my = Model::create('MyCustom');
$my->acl->getRole()->allow('view,modify', $folder->acl->getResource());
$my->acl->getRole()->deny('view', 'Projects');