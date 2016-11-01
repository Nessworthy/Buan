<?php
/**
* Renderer for PHP templates.
*
* @package Ias
*/
namespace Buan;
class PhpViewEngine implements IViewEngine {

	/**
	* The view which this engine will render.
	*
	* @var Buan\View
	*/
	private $view;

	/**
	* Return the view instance.
	*
	* @return Buan\View
	*/
	public function getView() {
		return $this->view;
	}

	/**
	* Render the view and return the result.
	*
	* @return string
	*/
	public function render() {

		// Start buffering
		ob_start();

		// Extract all variables into the local scope
		$__vars = $this->getView()->getVariables();
		extract($__vars, EXTR_REFS);
		unset($__vars);

		// Include PHP source template
		if($this->getView()->getSource()===NULL) {
			// No external source template has been defined, so check the rawSource
			//if($this->rawSource!==NULL) {
			//	echo $this->rawSource;
			//}
		}
		else if(is_file($this->getView()->getSource()) && include($this->getView()->getSource())) {
			// Successfully included
		}
		else {
			SystemLog::add("View template source is missing: {$this->getView()->getSource()}", SystemLog::WARNING);
		}

		// Read the output buffer
		$buffer = ob_get_clean();

		// Remove any UTF-8 BOM (Byte Order Mark) from the beginning of the buffer
		// This is required, at least, on child templates because otherwise the
		// BOM is displayed as a visible character in the output.
		$buffer = preg_replace("/^\xef\xbb\xbf/", "", $buffer);

		// Result
		return $buffer;
	}

	/**
	* Set the view that will be rendered by this engine.
	*
	* @param Buan\View
	* @return void
	*/
	public function setView(View $view) {
		$this->view = $view;
	}

	/**
	* Loads the specified helper.
	*
	* @param string Helper identifier
	* @return void
	*/
	public function loadHelper($helper) {

		// Helper: html
		// Simply create a shortcut reference to the ->helpers->html in the View
		if($helper==='html') {
			$this->html = $this->getView()->helpers->html;
		}
	}
}
?>