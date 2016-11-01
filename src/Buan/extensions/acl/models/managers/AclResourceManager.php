<?php
/*
# $Id$
*/

/*
# @class AclResourceManager
*/
class AclResourceManager extends ModelManager {

	/*
	# @method bool loadByAlias( AclRepoModel &$model )
	# $model	= Model instance
	#
	# Find and load the Model identified by $model->alias.
	*/
	public function loadByAlias(&$model) {
		$C = new ModelCriteria();
		$C->addClause(ModelCriteria::EQUALS, 'alias', $model->alias);
		$models = self::select($model->modelName, $C);
		if(count($models)>0) {
			$model = $models[0];
			return TRUE;
		}
		return FALSE;
	}

	public function save(&$model) {
		return parent::save($model);
		// TODO:
		// Ensure that the acl_repo_id is != 0 - ie. that this resource is related to a repository
		// If not, and this resource has a parent, then perhaps default to using the same repo as the parent.
	}
}
?>