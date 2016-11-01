<?php
/*
# $Id$
#
# If your Model requires access control then add a public $acl property and
# populate it with an AclObject instance during construction, eg:
#
#	class YourModel extends Model {
#		public $acl;
#		public function __construct($modelName=NULL) {
#			parent::__construct($modelName);
#			$this->acl = new AclObject($this, AclObject::ROLE);
#		}
#	}
#
# A custom Model is only ever directly associated with a single AclRoleModel.
*/

/*
# @class AclObject
*/
class AclObject {

	/*
	# @constant ROLE
	*/
	const ROLE = 'role';

	/*
	# @constant RESOURCE
	*/
	const RESOURCE = 'resource';

	/*
	# @constant BOTH
	*/
	const BOTH = 'role-and-resource';

	/*
	# @property Model $model
	# The Model instance that is using this AclObject.
	*/
	private $model;

	private $isRole = FALSE;

	private $isResource = FALSE;

	private $options;

	/*
	# @method void __construct( Model $model, string $type, [array $options] )
	# $model	= Model which this AclObject controls
	# $type		= ROLE | RESOURCE | BOTH
	# $options	= Array of options (see below)
	#
	# Create AclObject.
	#
	# Valid options:
	# string defaultParentRole		= Alias of this model's default parent role
	# string defaultParentResource	= Alias of this model's default parent resource
	# string defaultRepo			= Name of default ACL repository
	# string defaultRoleType		= Default type
	# string defaultResourceType	= Default type
	*/
	public function __construct($model, $type, $options=array()) {
		$this->model = $model;
		$this->options = $options;
		$this->isRole = $type==self::ROLE || $type==self::BOTH;
		$this->isResource = $type==self::RESOURCE || $type==self::BOTH;

		if($this->isRole) {
			$this->getRole();
		}

		if($this->isResource) {
			$this->getResource();
		}
	}

	/*
	# @method AclRoleModel getRole()
	#
	# Retrieve the AclRole instance.
	*/
	public function getRole() {
		$role = $this->model->loadAndGetAclRole();
		if($role===NULL) {
			$role = Model::create('AclRole');
			$role->addRelatedModel($this->model);
			if(isset($this->options['defaultParentRole'])) {
				$parent = Model::create('AclRole');
				$parent->alias = $this->options['defaultParentRole'];
				if($parent->getModelManager()->loadByAlias($parent)) {
					$parent->addRelatedModel($role);
				}
			}
			if(isset($this->options['defaultRepo'])) {
				$repo = Model::create('AclRepo');
				$repo->name = $this->options['defaultRepo'];
				if($repo->getModelManager()->loadByName($repo)) {
					$repo->addRelatedModel($role);
				}
			}
			if(isset($this->options['defaultRoleType'])) {
				$type = Model::create('AclType');
				$type->type = $this->options['defaultRoleType'];
				if($type->getModelManager()->loadByType($type)) {
					$type->addRelatedModel($role);
				}
			}
		}
		return $role;
	}

	/*
	# @method AclResourceModel getResource()
	#
	# Retrieve the AclResource instance.
	*/
	public function getResource() {
		$resource = $this->model->loadAndGetAclResource();
		if($resource===NULL) {
			$resource = Model::create('AclResource');
			$resource->addRelatedModel($this->model);
			if(isset($this->options['defaultParentResource'])) {
				$parent = Model::create('AclResource');
				$parent->alias = $this->options['defaultParentResource'];
				if($parent->getModelManager()->loadByAlias($parent)) {
					$parent->addRelatedModel($resource);
				}
			}
			if(isset($this->options['defaultRepo'])) {
				$repo = Model::create('AclRepo');
				$repo->name = $this->options['defaultRepo'];
				if($repo->getModelManager()->loadByName($repo)) {
					$repo->addRelatedModel($resource);
				}
			}
			if(isset($this->options['defaultResourceType'])) {
				$type = Model::create('AclType');
				$type->type = $this->options['defaultResourceType'];
				if($type->getModelManager()->loadByType($type)) {
					$type->addRelatedModel($resource);
				}
			}
		}
		return $resource;
	}

	/*
	# @method bool save()
	#
	# Convenience function for saving the AclRole associated with $this->model
	# You can achieve the same manually:
	#	$role = $myModel->getRole();
	#	$role->getModelManager()->save($role);
	*/
	public function save() {

		$result = TRUE;
		if($this->isRole) {
			$role = $this->getRole();
			if(!$role->getModelManager()->save($role)) {
				$result = FALSE;
			}
		}
		if($this->isResource) {
			$resource = $this->getResource();
			if(!$resource->getModelManager()->save($resource)) {
				$result = FALSE;
			}
		}
		return $result;
	}
}
?>