<?php
/**
* Iterface that all view-renderer classes should implement.
*
* @package Ias
*/
namespace Buan;
interface IViewEngine {

	/**
	* Return the View instance that this engine will be rendering.
	*
	* @return Buan\View
	*/
	public function getView();

	/**
	* Perform some actions when the specified helper is loaded.
	* This method should never be called directly, but instead the
	* Buan\View::loadHelper() is called which in turn calls this method.
	*
	* @param string Helper identifier
	* @return void
	*/
	public function loadHelper($helper);

	/**
	* Renders the view and returns the result.
	*
	* @return string
	*/
	public function render();

	/**
	* Store the View instance that this engine will be rendering.
	*
	* @param Buan\View View instance
	* @return void
	*/
	public function setView(View $view);
}
?>