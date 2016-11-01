<?php
/*
# $Id$
#
# TODO:
# - ::removeFromRole/Resource just unsets the relationship - it doesn't delete
#	the record from AclRoleMember. Need to think about how to accomplish this
#	when the developer saves the AclRole. Maybe keep a log of removed aliases?
*/

/*
# @class AclRoleModel
*/
class AclRoleModel extends Model {

	/*
	# @constant int RETURN_OBJECTS
	# Return and array of objects each containing a single Model's data.
	*/
	const RETURN_OBJECTS = 0;

	/*
	# @constant int RETURN_IDS
	# Returns an array of Model IDs.
	*/
	const RETURN_IDS = 1;

	/*
	# @property string $dbTableName
	# Database table name.
	*/
	protected $dbTableName = 'acl_role';

	/*
	# @method bool loadRoleByAlias( string $alias )
	# $alias	= AclRole alias
	#
	# Attempt to load an AclRole matching the given alias.
	*/
	private function loadRoleByAlias($alias) {
		$role = Model::create('AclRole');
		$role->alias = $alias;
		if(!$role->getModelManager()->loadByAlias($role)) {
			return FALSE;
		}
		return $role;
	}

	/*
	# @method bool loadResourceByAlias( string $alias )
	# $alias	= AclRole alias
	#
	# Attempt to load an AclRole matching the given alias.
	*/
	private function loadResourceByAlias($alias) {
		$resource = Model::create('AclResource');
		$resource->alias = $alias;
		if(!$resource->getModelManager()->loadByAlias($resource)) {
			return FALSE;
		}
		return $resource;
	}

	/*
	# @method void addToRole( AclRoleModel $role )
	# $role	= Model instance
	#
	# Add $this role as a child of the specified $role.
	*/
	public function addToRole($role) {
		$alias = $role;
		if(is_string($role) && !($role = $this->loadRoleByAlias($role))) {
			throw new BuanException("Could not find AclRole with an alias of '$alias'");
			return FALSE;
		}
		$this->addRelatedModel($role, ModelRelation::REF_CHILD, ModelRelation::REF_PARENT);
	}

	/*
	# @method void removeFromRole( AclRoleModel $role )
	# $role	= Model instance
	#
	# Remove $this role from the specified parent $role.
	*/
	public function removeFromRole($role) {
		$alias = $role;
		if(is_string($role) && !($role = $this->loadRoleByAlias($role))) {
			throw new BuanException("Could not find AclRole with an alias of '$alias'");
			return FALSE;
		}
		$this->removeRelatedModel($role);
	}

	/*
	# @method bool isMemberOf( AclRoleModel|string $role )
	# $role	= AclRoleModel instance or string alias
	#
	# Returns TRUE if $this role is a child of $role.
	*/
	public function isMemberOf($role) {
		$alias = $role;
		if(is_string($role) && !($role = $this->loadRoleByAlias($role))) {
			throw new BuanException("Could not find AclRole with an alias of '$alias'");
			return FALSE;
		}
		$this->loadRelatedModels('AclRole', NULL, ModelRelation::REF_CHILD);
		return $this->getLinkingModel($role)===NULL ? FALSE : TRUE;
	}

	/*
	# @method array getAncestors()
	#
	# Returns a list of parent AclRoles from $this all the way up the tree.
	# TODO: Roles can belong to several parent roles - work it out!
	*/
	public function getAncestors() {

		/*// Vars
		$lineage = array();
		$role = $this->modelName=='AclRole' ? $this : $this->getAclRole();

		// Load parent AclRole
		$role->loadRelatedModels('AclRole', NULL, ModelRelation::REF_CHILD);
		$parentRole = $role->getRelatedModels('AclRole', ModelRelation::REF_CHILD);
		if(is_null($parentRole)) {
			$lineage = array();
		}
		else {
			$parentParentPath = $parentRole->getAclRoleAncestors();
			$lineage = array_merge($parentParentPath, array($parentRole));
		}

		// Result
		return $lineage;*/

		return array();
	}

	/*
	# @method bool allow( string $permissions, AclResourceModel|string $resource )
	# $permissions	= Comma separated list of permisions
	# $resource		= Resource (or alias) on which the permissions are allowed
	#
	# Grants the specified permissions to $this AclRole on $resource.
	*/
	public function allow($permissions, $resource) {

		// Load resource from a given alias
		$alias = $resource;
		if(is_string($resource) && !($resource = $this->loadResourceByAlias($resource))) {
			throw new BuanException("Could not find AclResource with an alias of '$alias'");
			return FALSE;
		}

		// Convert $permissions to an array
		if(!is_array($permissions)) {
			$permissions = explode(",", preg_replace("/[^a-z0-9_\-\*,]/i", "", strtolower($permissions)));
		}
		$permissions = array_unique($permissions);

		// Find an existing related AclEntry Model that handles the relation
		// between $role and $resource, or create a new one if none is found.
		if($this->isInDatabase()) {
			$tmpEntry = Model::create('AclEntry');
			$C = new ModelCriteria();
			$C->addClause(ModelCriteria::EQUALS, $tmpEntry->getForeignKey($this->modelName), $this->getPrimaryKeyValue());
			if($resource->isInDatabase()) {
				$C->addClause(ModelCriteria::EQUALS, $tmpEntry->getForeignKey($resource->modelName), $resource->getPrimaryKeyValue());
			}
			$this->loadRelatedModels('AclEntry', $C);
			unset($tmpEntry);
		}
		$entry = $this->getLinkingModel($resource);
		if($entry===NULL) {
			$entry = Model::create('AclEntry');
			$entry->addRelatedModel($this);
			$entry->addRelatedModel($resource);
		}

		// Apply permissions
		$entry->pallow = $entry->pallow=='' ? implode(",", $permissions) : implode(",", array_unique(array_merge(explode(",", $entry->pallow), $permissions)));
		$entry->pdeny = implode(",", array_diff(explode(",", $entry->pdeny), $permissions));

		// Result
		return TRUE;
	}

	/*
	# @method bool deny( string $permissions, AclResourceModel|string $resource )
	# $permissions	= Comma separated list of permisions
	# $resource		= Resource (or alias) on which the permissions are denied
	#
	# Revokes and denies the specified permissions for $this AclRole on
	# $resource.
	*/
	public function deny($permissions, $resource) {

		// Load resource from a given alias
		$alias = $resource;
		if(is_string($resource) && !($resource = $this->loadResourceByAlias($resource))) {
			throw new BuanException("Could not find AclResource with an alias of '$alias'");
			return FALSE;
		}

		// Convert $permissions to an array
		if(!is_array($permissions)) {
			$permissions = explode(",", preg_replace("/[^a-z0-9_\-\*,]/i", "", strtolower($permissions)));
		}
		$permissions = array_unique($permissions);

		// Find an existing related AclEntry Model that handles the relation
		// between $role and $resource, or create a new one if none is found.
		if($this->isInDatabase()) {
			$tmpEntry = Model::create('AclEntry');
			$C = new ModelCriteria();
			$C->addClause(ModelCriteria::EQUALS, $tmpEntry->getForeignKey($this->modelName), $this->getPrimaryKeyValue());
			if($resource->isInDatabase()) {
				$C->addClause(ModelCriteria::EQUALS, $tmpEntry->getForeignKey($resource->modelName), $resource->getPrimaryKeyValue());
			}
			$this->loadRelatedModels('AclEntry', $C);
			unset($tmpEntry);
		}
		$entry = $this->getLinkingModel($resource);
		if($entry===NULL) {
			$entry = Model::create('AclEntry');
			$entry->addRelatedModel($this);
			$entry->addRelatedModel($resource);
		}

		// Apply permissions
		$entry->pallow = implode(",", array_diff(explode(",", $entry->pallow), $permissions));
		$entry->pdeny = $entry->pdeny=='' ? implode(",", $permissions) : implode(",", array_unique(array_merge(explode(",", $entry->pdeny), $permissions)));

		// Result
		return TRUE;
	}

	/*
	# @method bool clear( [string $permissions], AclResourceModel|string $resource )
	# $permissions	= Comma separated list of permisions, or NULL
	# $resource		= Resource (or alias) on which the permissions are denied
	#
	# Clears specified $permissions (or ALL permissions if NULL).
	*/
	public function clear($permissions=NULL, $resource) {

		// Load resource from a given alias
		$alias = $resource;
		if(is_string($resource) && !($resource = $this->loadResourceByAlias($resource))) {
			throw new BuanException("Could not find AclResource with an alias of '$alias'");
			return FALSE;
		}

		// Convert $permissions to an array
		if($permissions!==NULL && !is_array($permissions)) {
			$permissions = explode(",", preg_replace("/[^a-z0-9_\-\*,]/i", "", strtolower($permissions)));
		}
		$permissions = $permissions===NULL ? NULL : array_unique($permissions);

		// Find an existing related AclEntry Model that handles the relation
		// between $role and $resource.
		if($this->isInDatabase()) {
			$tmpEntry = Model::create('AclEntry');
			$C = new ModelCriteria();
			$C->addClause(ModelCriteria::EQUALS, $tmpEntry->getForeignKey($this->modelName), $this->getPrimaryKeyValue());
			if($resource->isInDatabase()) {
				$C->addClause(ModelCriteria::EQUALS, $tmpEntry->getForeignKey($resource->modelName), $resource->getPrimaryKeyValue());
			}
			$this->loadRelatedModels('AclEntry', $C);
			unset($tmpEntry);
		}
		$entry = $this->getLinkingModel($resource);
		if($entry!==NULL) {
			$entry->pallow = $permissions===NULL ? '' : implode(",", array_diff(explode(",", $entry->pallow), $permissions));
			$entry->pdeny = $permissions===NULL ? '' : implode(",", array_diff(explode(",", $entry->pdeny), $permissions));
			return TRUE;
		}

		// Result
		return TRUE;
	}

	/*
	# @method bool isAllowed( string $permissions, AclResourceModel $resource, [bool $ignoreInheritance] )
	# $permissions			= Comma spearated list of permissions
	# $resource				= AclResource Model instance
	# $ignoreInheritance	= [INTERNAL USE ONLY]
	#
	# Test whether $this AclRole has the specified $permissions on $resource.
	*/
	public function isAllowed($permissions, $resource, $ignoreInheritance=FALSE) {

		// Load resource from a given alias
		$alias = $resource;
		if(is_string($resource) && !($resource = $this->loadResourceByAlias($resource))) {
			throw new BuanException("Could not find AclResource with an alias of '$alias'");
			return FALSE;
		}

		// Convert $permissions to an array
		if(!is_array($permissions)) {
			$permissions = explode(",", preg_replace("/[^a-z0-9_\-\*,]/i", "", strtolower($permissions)));
		}
		$permissions = array_unique($permissions);

		// Travel up through $resource and it's parent AclResources to find a
		// usable AclEntry
		$tmpEntry = Model::create('AclEntry');
		$pAclResource = $resource;
		while($pAclResource!==NULL) {

			// Load all AclEntry Models related to $pAclResource
			$C = new ModelCriteria();
			$C->addClause(ModelCriteria::EQUALS, $tmpEntry->getForeignKey($this->modelName), $this->getPrimaryKeyValue());
			$pAclResource->loadRelatedModels('AclEntry', $C);

			$entry = $pAclResource->getLinkingModel($this);
			if($entry!==NULL) {

				// Wildcard permissions
				if($entry->pallow=='*') {
					return TRUE;
				}
				if($entry->pdeny=='*') {
					return FALSE;
				}

				// All specified permissions are allowed
				$unknownAllowPerms = array_diff($permissions, explode(",", $entry->pallow));
				if(count($unknownAllowPerms)==0) {
					return TRUE;
				}

				// Partial permissions allowed (these will be passed onto the
				// parent AclResource and retested)
				else {

					// If any of the permissions are listed in the "pdeny" column,
					// then we need to return FALSE
					$permissions = $unknownAllowPerms;
					$unknownDenyPerms = array_diff($permissions, explode(",", $entry->pdeny));
					if(count($unknownDenyPerms)!=count($permissions)) {
						return FALSE;
					}
				}
			}

			// No AclEntry was found, so try $pAclResource's parent AclResource
			if(!$ignoreInheritance) {
				$pAclResource->loadRelatedModels('AclResource', NULL, ModelRelation::REF_CHILD);
				$pAclResource = $pAclResource->getRelatedModels('AclResource', ModelRelation::REF_CHILD);
			}
			else {
				$pAclResource = NULL;
			}
		}

		// If ALL the permissions are not yet satisfied, then start testing
		// permissions on the parents of the $role
		if(count($permissions)>0 && !$ignoreInheritance) {
			$rModel = $this->loadAndGetAclRole(NULL, ModelRelation::REF_CHILD);
			foreach($rModel as $rm) {
				if($rm->isAllowed($permissions, $resource)) {
					return TRUE;
				}
			}
			return FALSE;
		}
		else {
			return FALSE;
		}
	}

	/*
	# @method bool isExplicitlyAllowed( string $permissions, AclResourceModel $resource )
	# $permissions	= Comma separated list of permissions
	# $resource		= AclResource Model instance
	#
	# Same as $this->isAllowed(), but only takes into account exact AclEntry matches.
	*/
	public function isExplcitlyAllowed($permissions, $resource) {
		return $this->isAllowed($permissions, $resource, TRUE);
	}

	/*
	# @method bool isAllowed( string $permissions, AclResourceModel|string $resource, [bool $ignoreInheritance] )
	# $permissions			= Comma spearated list of permissions
	# $resource				= AclResource Model instance or string alias
	# $ignoreInheritance	= [INTERNAL USE ONLY]
	#
	# Test whether $this AclRole has specifically had the specified $permissions
	# denied on $resource.
	*/
	public function isDenied($permissions, $resource, $ignoreInheritance=FALSE) {

		// Load resource from a given alias
		$alias = $resource;
		if(is_string($resource) && !($resource = $this->loadResourceByAlias($resource))) {
			throw new BuanException("Could not find AclResource with an alias of '$alias'");
			return FALSE;
		}

		// Convert $permissions to an array
		if(!is_array($permissions)) {
			$permissions = explode(",", preg_replace("/[^a-z0-9_\-\*,]/i", "", strtolower($permissions)));
		}
		$permissions = array_unique($permissions);

		// Travel up through $resource and it's parent AclResources to find a
		// usable AclEntry
		$tmpEntry = Model::create('AclEntry');
		$pAclResource = $resource;
		while($pAclResource!==NULL) {

			// Load all AclEntry Models related to $pAclResource
			$C = new ModelCriteria();
			$C->addClause(ModelCriteria::EQUALS, $tmpEntry->getForeignKey($this->modelName), $this->id);
			$pAclResource->loadRelatedModels('AclEntry', $C);

			$entry = $pAclResource->getLinkingModel($this);
			if($entry!==NULL) {

				// Wildcard permissions
				if($entry->pdeny=='*') {
					return TRUE;
				}
				if($entry->pallow=='*') {
					return FALSE;
				}

				// All specified permissions are denied
				$unknownDenyPerms = array_diff($permissions, explode(",", $entry->pdeny));
				if(count($unknownDenyPerms)==0) {
					return TRUE;
				}

				// Partial permissions allowed (these will be passed onto the
				// parent AclResource and retested)
				else {

					// If any of the permissions are listed in the "pallow" column,
					// then we need to return FALSE
					$permissions = $unknownDenyPerms;
					$unknownAllowPerms = array_diff($permissions, explode(",", $entry->pallow));
					if(count($unknownAllowPerms)!=count($permissions)) {
						return FALSE;
					}
				}
			}

			// No AclEntry was found, so try $pAclResource's parent AclResource
			if(!$ignoreInheritance) {
				$pAclResource->loadRelatedModels('AclResource', NULL, ModelRelation::REF_CHILD);
				$pAclResource = $pAclResource->getRelatedModels('AclResource', ModelRelation::REF_CHILD);
			}
			else {
				$pAclResource = NULL;
			}
		}

		// If ALL the permissions are not yet satisfied, then start testing
		// permissions on the parents $role
		if(count($permissions)>0 && !$ignoreInheritance) {
			$rModel = $this->loadAndGetAclRole(NULL, ModelRelation::REF_CHILD);
			foreach($rModel as $rm) {
				if($rm->isDenied($permissions, $resource)) {
					return TRUE;
				}
			}
			return FALSE;
		}
		else {
			return FALSE;
		}
	}

	/*
	# @method bool isExplicitlyDenied( string $permissions, AclResourceModel $resource )
	# $permissions	= Comma separated list of permissions
	# $resource		= AclResource Model instance
	#
	# Same as $this->isDenied(), but only takes into account exact AclEntry matches.
	*/
	public function isExplicitlyDenied() {
		return $this->isDenied($permissions, $resource, TRUE);
	}

	/*
	# @method array whichAllowed( string|array $permissions, AclResourceModel $resource, [int $resultFormat] )
	# $permissions	= Permissions
	# $resource		= AclResourceModel instance
	# $resultFormat	= Format in which to return results
	#
	# Tests all child resources within $resource and returns a list containing
	# the resources on which $this role can permiform all specified permissions.
	#
	# TODO:
	# Somehow integrate a custom SQL statement with this method. For example,
	# we might only want to return the first 10 Project Models that resolve our permissions.
	*/
	public function whichAllowed($permissions, $resource, $resultFormat=self::RETURN_OBJECTS) {

		// Load resource from a given alias
		$alias = $resource;
		if(is_string($resource) && !($resource = $this->loadResourceByAlias($resource))) {
			throw new BuanException("Could not find AclResource with an alias of '$alias'");
			return FALSE;
		}

		// If $resource is not persistent, we have to test all in-memory child
		// resources and return as an array, ignoring $resultFormat.
		// TODO: Why ignore $resultFormat?
		if(!$resource->isInDatabase()) {
			$childResources = $resource->getRelatedModels('AclResource', ModelRelation::REF_PARENT);
			$allowed = array();
			foreach($childResources as $child) {
				if($this->isAllowed($permissions, $child)) {
					$allowed[] = $child;
				}
			}
			return $allowed;
		}

		// Get inheritable result first
		$inheritable = $this->isAllowed($permissions, $resource);

		// Convert $permissions to an array
		if(!is_array($permissions)) {
			$permissions = explode(",", preg_replace("/[^a-z0-9_\-\*,]/i", "", strtolower($permissions)));
		}
		$permissions = array_unique($permissions);

		// Build list of AclRole IDs which affect $role.
		// TODO: Order may be significant when I get around to using it. ie. A
		// User is more significant, has more weight, than it's parent group,
		// and so on up the hierarchy.
		$parents = $this->getAncestors();
		$roleIds = array();
		foreach($parents as $p) {
			$roleIds[] = $p->id;
		}
		$roleIds[] = $this->id;

		// TODO
		// The code below doesn't yet take into account any AclEntry models
		// that may just be in-memory, eg from:
		// $role->load();
		// $role->allow('view', 'resource');
		// $role->whichAllowed();
		//
		// It only searches in the DB. Change to look through in-memory AclEntries
		// too.

		// Gather all allowable resources
		$resultFields = $resultFormat==self::RETURN_OBJECTS ? 'R.*' : 'R.id';
		$sql = 'SELECT '.$resultFields.' FROM acl_resource AS R
				LEFT JOIN acl_entry AS E ON R.id=E.acl_resource_id
				WHERE R.parent_id='.$resource->id.' AND (';
		if($inheritable) {
			$sql .= 'E.acl_resource_id IS NULL OR (E.acl_role_id<>'.implode(" AND E.acl_role_id<>", $roleIds).') OR (E.pdeny<>"*" AND NOT FIND_IN_SET("'.implode('", pdeny) AND NOT FIND_IN_SET("', $permissions).'", pdeny))';
		}
		else {
			$sql .= 'E.acl_role_id='.implode(" OR E.acl_role_id=", $roleIds).' AND (E.pallow="*" OR (FIND_IN_SET("'.implode('") AND FIND_IN_SET("', $permissions).'", pallow)))';
		}
		$sql .= ') GROUP BY R.id';

		$stmt = ModelManager::sqlQuery($sql);
		$idList = array();
		while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
			$idList[] = $resultFormat==self::RETURN_IDS ? (int)$row->id : $row;
		}
		return $idList;
	}
}
?>