<?php
/**
* Engine wrapper for rendering templates via the Twig engine
* @see http://www.twig-project.org/
*
* This class requires that you have setup Twig in your application, including
* the autoloader.
*
* @package Buan
*/
namespace Buan;
class TwigViewEngine implements IViewEngine {

	/**
	* The Twig environment instance used by this engine.
	*
	* @var \Twig_Environment
	*/
	private $twigEnvironment;

	/**
	* The view attached to this engine.
	*
	* @var Buan\View
	*/
	private $view;

	/**
	* Constructor.
	*
	* @param \Twig_Environment The environment to use
	* @return Buan\TwigViewEngine
	*/
	public function __construct($twigEnvironment) {
		$this->twigEnvironment = $twigEnvironment;
	}

	/**
	* Return the View
	*
	* @return Buan\View
	*/
	public function getView() {
		return $this->view;
	}

	/**
	* Load helpers.
	*
	* @param string Helper id
	* @return void
	*/
	public function loadHelper($helper) {
		// Do nothing as this engine doesn't support any helpers just yet
	}

	/**
	* Render the View.
	*
	* @return string
	*/
	public function render() {
		$template = $this->twigEnvironment->loadTemplate(basename($this->getView()->getSource()));
		$vars = $this->getView()->getVariables();
		return $template->render($vars);
	}

	/**
	* Set the View
	*
	* @param Buan\View View instance
	* @return void
	*/
	public function setView(View $view) {
		$this->view = $view;
	}
}
?>