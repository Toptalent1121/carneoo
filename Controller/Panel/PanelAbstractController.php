<?php

namespace App\Controller\Panel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class PanelAbstractController extends AbstractController
{
    public function setLocaleRedirect(Request $request)
    {
		if ($route = $request->attributes->get('route')) {
			return $this->redirectToRoute($route);
        } 
    }
	
	/**
     * @return string
     */
    public function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
