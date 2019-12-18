<?php

namespace App\Controller\Panel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class PanelIndexController extends AbstractController
{
    public function index()
    {
        return $this->render('panel/dashboard/index.html.twig', [
            'controller_name' => 'PanelIndexController',
        ]);
    }
}
