<?php
/*
# $Id$
*/

/*
# @class AclRoleManager
*/
class AclRoleManager extends ModelManager {

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
		// Ensure that the acl_repo_id is != 0 - ie. that this role is related to a repository
	}
}
?>