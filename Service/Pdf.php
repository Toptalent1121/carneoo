<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Class Notifications
 * @package App\Service
 */
class Pdf
{
    /**
     * @var \Twig_Environment $templating
     */
    private $templating;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    public function __construct(Environment $templating, ContainerInterface $container)
    {
        $this->templating = $templating;
        $this->container = $container;
    }

    /**
     * @param array $data
     * @param string $template
     * @param string $fileName
     *
     * @return string
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function save(array $data = [], string $template = '', string $fileName = 'file.pdf')
    {
        $filePath = $this->container->getParameter('kernel.project_dir') . '/var/tmp/' . $fileName;

        $snappy = $this->container->get('knp_snappy.pdf');
        $html = $this->generate($data, $template);
        $options = [
            'margin-top'    => '15mm',
            'margin-right'  => 0,
            'margin-bottom' => '35mm',
            'margin-left'   => 0,
            'header-html'   => $this->templating->render('pdf/_include/header.html.twig', ['http_host' => $this->container->getParameter('http_host')]),
            'footer-html'   => $this->templating->render('pdf/_include/footer.html.twig', ['http_host' => $this->container->getParameter('http_host')]),
        ];

        try {
            $snappy->generateFromHtml($html, $filePath, $options, true);
        } catch (\Exception $exception) {

        }

        return $filePath;
    }

    /**
     * @param array $data
     * @param string $template
     * @param string $fileName
     *
     * @return Response
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function download(array $data = [], string $template = '', string $fileName = 'file.pdf')
    {
        ini_set('max_execution_time', 3600);
        $html = $this->generate($data, $template);

        $snappy = $this->container->get('knp_snappy.pdf');
        $snappy->setOptions([
            'margin-top'    => 0,
            'margin-right'  => 0,
            'margin-bottom' => 0,
            'margin-left'   => 0,
        ]);
        $snappy->setOption('lowquality', false);
        $snappy->setOption('page-size', 'A4');

        return new Response(
            $snappy->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'attachment; filename="' . $fileName . '"'
            )
        );
    }

    /**
     * @param array $data
     * @param string $template
     *
     * @return string
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function generate(array $data = [], string $template = '')
    {
        return $this->templating->render('pdf/' . $template . '.html.twig', [
            'data' => $data,
            'http_host' => $this->container->getParameter('http_host'),
        ]);
    }
}