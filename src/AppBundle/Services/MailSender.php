<?php

namespace AppBundle\Services;

use AppBundle\Util\DateUtil;
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

        $hoy = $arguments['hoy'];

        $asunto = $hoy instanceof \DateTimeInterface ?
            sprintf("Datos Solicitudes %s %s", DateUtil::diaSemanaCompleto($hoy), $hoy->format('d/m/Y')) :
            'Datos Solicitudes';

        $message = new Swift_Message($asunto, $body, 'text/html', 'UTF-8');
        $message->addBcc('jmadueno@iccaweb.com');
        $message->addFrom('jmadueno@iccaweb.com');

        $to = filter_var($arguments['destinatario'], FILTER_VALIDATE_EMAIL);
        $to = $to ? $to  : 'jmadueno@iccaweb.com';

        $message->addTo($to);

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