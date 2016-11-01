<?php
/*
# $Id$
*/

/*
# @class AclRepoManager
*/
class AclRepoManager extends ModelManager {

	/*
	# @method bool loadByName( AclRepoModel &$model )
	# $model	= Model instance
	#
	# Find and load the Model identified by $model->name.
	*/
	public function loadByName(&$model) {
		$C = new ModelCriteria();
		$C->addClause(ModelCriteria::EQUALS, 'name', $model->name);
		$models = self::select($model->modelName, $C);
		if(count($models)>0) {
			$model = $models[0];
			return TRUE;
		}
		return FALSE;
	}
}
?>