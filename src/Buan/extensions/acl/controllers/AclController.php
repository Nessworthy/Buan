<?php
/*
# $Id$
*/

class AclController extends Controller {

	public function index($params) {
		$view = new View();
		$view->setSource(Config::get('ext.acl.dir.views').'/index.tpl');
		return $view;
	}

	public function repos($params) {
		$view = new View();
		if(!Core::hasAdminAccess()) {
			return Core::getLoginView($view);
		}
		$view->setSource(Config::get('ext.acl.dir.views').'/repos.tpl');

		if(isset($_GET['id'])) {
			$view->setSource(Config::get('ext.acl.dir.views').'/repos.edit.tpl');
			$repo = Model::create('AclRepo');
			if($_GET['id']>0) {
				$repo->id = (int)$_GET['id'];
				if(!$repo->getModelManager()->load($repo)) {
					SystemLog::add(array('Failed to load repository #%s', $repo->id), SystemLog::WARNING);
					$view->setSource(NULL);
					return $view;
				}
			}

			// Save
			if(isset($_POST['name'])) {
				$repo->name = $_POST['name'];
				if($repo->getModelManager()->save($repo)) {
					SystemLog::add('Data saved!', SystemLog::INFO);
				}
				else {
					SystemLog::add('Failed to save data.', SystemLog::WARNING);
				}
			}

			// Delete
			else if(isset($_GET['remove']) && $repo->isInDatabase()) {
				if($repo->getModelManager()->delete($repo)) {
					SystemLog::add('Data removed!', SystemLog::INFO);
					$repo = Model::create('AclRepo');
				}
				else {
					SystemLog::add('Failed to remove data.', SystemLog::WARNING);
				}
			}

			// View
			$view->repo = $repo;
			return $view;
		}

		// View
		$view->repos = ModelManager::select('AclRepo');
		return $view;
	}
}
?>