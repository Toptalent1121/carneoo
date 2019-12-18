<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

/**
 * Class Notifications
 * @package App\Service
 */
class Notifications
{
    /**
     * @var int
     */
    const TYPE_FINISH_CONFIGURATION = 1;

    /**
     * @var int
     */
    const TYPE_FORGOT_PASSWORD = 2;

    /**
     * @var int
     */
    const TYPE_CONTACT = 3;

    /**
     * @var int
     */
    const TYPE_ADMIN_FINISH_CONFIGURATION = 4;

    /**
     * @var int
     */
    const TYPE_SHARE_MAIL = 5;

    /**
     * @var int
     */
    const TYPE_REGISTRATION_CONFIRM = 6;

    /**
     * @var \Swift_Mailer $mailer
     */
    private $mailer;

    /**
     * @var \Twig_Environment $templating
     */
    private $templating;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    public function __construct(\Swift_Mailer $mailer, Environment $templating, ContainerInterface $container)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->container = $container;
    }

    /**
     * @param $type
     * @param string $email
     * @param array $data
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function send($type, string $email, array $data)
    {
        if(isset($data['email'])) {
            $from = $data['email'];
        } else {
            $from = 'info@carneoo.de';
        }

        $message = (new \Swift_Message($this->getSubject($type)))
            ->setFrom($this->getFrom($type, $from))
            ->setTo($email)
            ->addBcc('konrad@websky.pl')
            //->addBcc('m.gnos@factum.ch')
            //->addBcc('nadine.grosse@carneoo.de')
            ->setBody(
                $this->templating->render('notifications/' .  $this->getTemplateName($type) . '.html.twig', [
                    'data' => $data,
                    'http_host' => $this->container->getParameter('http_host'),
                    ]
                ),
                'text/html'
            );

        if($type == self::TYPE_FINISH_CONFIGURATION || $type == self::TYPE_ADMIN_FINISH_CONFIGURATION) {
            $date = $data['date'];
            $fileName =
                (isset($data['car']) && isset($data['car']['mark']) ? $data['car']['mark'] . '_' : '') .
                (isset($data['car']) && isset($data['car']['model']) ? $data['car']['model'] . '_' : '') .
                '_'  .
                $date->format('Ymd') .
                '_'  .
                $date->format('His') .
                '_Anfrage_Carneoo' .
                '.pdf';

            $pdfFileName = $this->container->getParameter('kernel.project_dir') . '/var/tmp/' . $fileName;

            if(false == file_exists($pdfFileName)) {
                $pdfFileName = $this->container->get('App\Service\Pdf')->save($data, 'finish_configuration', $fileName);
            }

            $message->attach(
                \Swift_Attachment::fromPath($pdfFileName)->setFilename($fileName)
            );
        }

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            // @todo add exception
        }
    }

    /**
     * @param int $type
     * @return string
     */
    private function getSubject(int $type)
    {
        switch ($type) {
            case self::TYPE_FINISH_CONFIGURATION:
                return 'Ihre Fahrzeug-Konfiguration bei Carneoo.de';
                break;
            case self::TYPE_ADMIN_FINISH_CONFIGURATION:
                return 'Unverbindliche Bestellanfrage: HP';
                break;
            case self::TYPE_FORGOT_PASSWORD:
                return 'Zurücksetzen Ihres Carneoo-Passwortes';
                break;
            case self::TYPE_CONTACT:
                return 'Zurücksetzen Ihres Carneoo-Passwortes';
                break;
            case self::TYPE_SHARE_MAIL:
                return 'Ist das auch dein Wunschauto?';
                break;
            case self::TYPE_REGISTRATION_CONFIRM:
                return 'Ihre Registrierung bei Carneoo.de: Bestätigen Sie bitte Ihre E-Mail-Adresse';
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * @param int $type
     * @return string
     */
    private function getTemplateName(int $type)
    {
        switch ($type) {
            case self::TYPE_FINISH_CONFIGURATION:
                return 'finish_configuration';
                break;
            case self::TYPE_ADMIN_FINISH_CONFIGURATION:
                return 'admin_finish_configuration';
                break;
            case self::TYPE_FORGOT_PASSWORD:
                return 'forgot_password';
                break;
            case self::TYPE_CONTACT:
                return 'contact';
                break;
            case self::TYPE_SHARE_MAIL:
                return 'share_mail';
                break;
            case self::TYPE_REGISTRATION_CONFIRM:
                return 'registration';
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * @param int $type
     * @param string $userEmail
     * @return string
     */
    private function getFrom(int $type, string $userEmail) : string
    {
        switch ($type) {
            case self::TYPE_ADMIN_FINISH_CONFIGURATION:
                return $userEmail;
                break;
            default:
                return 'info@carneoo.de';
                break;
        }
    }
}