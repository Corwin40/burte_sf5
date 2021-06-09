<?php

namespace App\Controller\Webapp;

use App\Entity\Admin\Parameter;
use App\Entity\Webapp\Page;
use App\Repository\Webapp\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PublicController
 * @package App\Controller\Webapp
 */
class PublicController extends AbstractController
{
    /**
     * @Route("/webapp/public/", name="op_webapp_public_index")
     */
    public function index(): RedirectResponse
    {
        $parameter = $this->getDoctrine()->getRepository(Parameter::class)->findFirstReccurence();
        // boucle : verifie si le site est installé
        if(!$parameter){
            return $this->redirectToRoute('op_admin_dashboard_first_install');
        }
        else {
            $isOnline = $parameter->getIsOnline();
            if(!$isOnline) {
                return $this->redirectToRoute('op_webapp_public_offline');
            }
            else{
                return $this->redirectToRoute('op_webapp_public_homepage');
            }
        }

    }

    /**
     * @return Response
     * Inclus la balise meta du iste pour le référencement
     */
    public function meta() : Response
    {
        $parameter = $this->getDoctrine()->getRepository(Parameter::class)->find(1);

        return $this->render('include/meta.html.twig', [
            'parameter' => $parameter
        ]);
    }

    /**
     * @Route("/webapp/public/page/", name="op_webapp_public_homepage")
     */
    public function homepage() : Response
    {
        $parameter = $this->getDoctrine()->getRepository(Parameter::class)->find(1);
        return $this->render('webapp/public/index.html.twig',[
            'parameter' => $parameter
        ]);
    }

    /**
     * @route("/webapp/public/offline", name="op_webapp_public_offline")
     */
    public function Offline() : Response
    {
        $parameter = $this->getDoctrine()->getRepository(Parameter::class)->findFirstReccurence();
        return $this->render('webapp/public/Offline.html.twig', [
            'parameter' => $parameter
        ]);
    }

    /**
     * @Route ("/webapp/public/menus", name="op_webapp_public_listmenus")
     */
    public function BlocMenu(PageRepository $pageRepository): Response
    {
        $menus = $pageRepository->listMenu();

        return $this->render('include/navbar_webapp.html.twig', [
            'menus' => $menus
        ]);
    }
}
