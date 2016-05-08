<?php

namespace AppBundle\Services;

use Twig_Environment;

/**
 * Class MailRender
 *
 * @package AppBundle\Services
 */
class MailRender
{

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @param Twig_Environment $twig
     */
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }


    /**
     * @param $arguments
     *
     * @return string
     */
    public function render($arguments)
    {
        $body = $this->twig->render('mail/solicitudes_diario.html.twig', $arguments);

        return $body;
    }
}