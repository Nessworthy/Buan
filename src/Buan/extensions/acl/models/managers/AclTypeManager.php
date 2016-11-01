<?php
/*
# $Id$
*/

/*
# @class AclTypeManager
*/
class AclTypeManager extends ModelManager {

	/*
	# @method bool loadByType( AclRepoModel &$model )
	# $model	= Model instance
	#
	# Find and load the Model identified by $model->type.
	*/
	public function loadByType(&$model) {
		$C = new ModelCriteria();
		$C->addClause(ModelCriteria::EQUALS, 'type', $model->type);
		$models = self::select($model->modelName, $C);
		if(count($models)>0) {
			$model = $models[0];
			return TRUE;
		}
		return FALSE;
	}
}
?>