<?php

namespace AppBundle\Services;

use Swift_Message;
use Swift_Mailer;

class MailSender
{
    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @param Swift_Mailer $mailer
     * @param MailRender   $render
     */
    public function __construct(Swift_Mailer $mailer, MailRender $render)
    {
        $this->mailer = $mailer;
        $this->render = $render;
    }

    /**
     * @param $argumentos
     */
    public function renderAndSend($argumentos)
    {
        $message = $this->createMessage($argumentos);

        $this->send($message);
    }


    /**
     * @param $arguments
     *
     * @return Swift_Message
     */
    public function createMessage($arguments)
    {
        $body = $this->render->render($arguments);

        $message = new Swift_Message('Datos Solicitdes', $body, 'text/html', 'UTF-8');
        $message->addBcc('jmadueno@iccaweb.com');
        $message->addFrom('jmadueno@iccaweb.com');
        $message->addTo($arguments['destinatario']);

        return $message;

    }


    /**
     * @param Swift_Message $message
     */
    public function send(Swift_Message $message)
    {
        $this->mailer->send($message);
    }

} 