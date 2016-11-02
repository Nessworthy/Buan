<?php
/**
 * Engine wrapper for rendering templates via the Smarty engine
 * @see http://www.smarty.net/
 *
 * This class requires that you have setup Smarty in your application, including
 * the necessary libraries.
 *
 * @package Buan
 */
namespace Buan;

class SmartyViewEngine implements IViewEngine
{
    /**
     * The view attached to this engine.
     *
     * @var View
     */
    private $view;

    /**
     * Constructor.
     *
     * @param \Smarty The Smarty instance to use
     */
    public function __construct(\Smarty $smarty)
    {
        $this->smarty = $smarty;
    }

    /**
     * Return the View
     *
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Load helpers.
     *
     * @param string $helper Helper id
     */
    public function loadHelper($helper)
    {
        // Do nothing as this engine doesn't support any helpers just yet
    }

    /**
     * Render the View.
     *
     * @return string
     */
    public function render()
    {
        $src = $this->getView()->getSource();
        $this->smarty->template_dir = dirname($src);
        $vars = $this->getView()->getVariables();
        $this->smarty->assign($vars);
        return $this->smarty->fetch(basename($src));
    }

    /**
     * Set the View
     *
     * @param View $view View instance
     */
    public function setView(View $view)
    {
        $this->view = $view;
    }
}
