<?php
/**
* Renders raw string sources.
*
* Specify the raw source via $view->setSource('...template source here...').
*
* @package Ias
*/
namespace Buan;
class StringViewEngine implements IViewEngine {

	private $view;

	public function getView() {
		return $this->view;
	}

	public function loadHelper($helper) {
		// Do nothing as this engine doesn't support any helpers just yet
	}

	public function render() {
		return $this->getView()->getSource();
	}

	public function setView(View $view) {
		$this->view = $view;
	}
}
?>