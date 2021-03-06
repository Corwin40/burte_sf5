<?php

namespace App\Controller\Gestapp;

use App\Entity\Gestapp\Avis;
use App\Form\Gestapp\AvisType;
use App\Repository\Gestapp\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("")
 */
class AvisController extends AbstractController
{
    /**
     * @Route("/op_admin/gestapp/avis/", name="op_gestapp_avis_index", methods={"GET"})
     */
    public function index(AvisRepository $avisRepository): Response
    {
        return $this->render('gestapp/avis/index.html.twig', [
            'avis' => $avisRepository->findAll(),
        ]);
    }

    /**
     * @Route("/op_admin/gestapp/avis/new", name="op_gestapp_avis_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $avi = new Avis();
        $form = $this->createForm(AvisType::class, $avi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($avi);
            $entityManager->flush();

            return $this->redirectToRoute('op_gestapp_avis_index');
        }

        return $this->render('gestapp/avis/new.html.twig', [
            'avi' => $avi,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/op_admin/gestapp/avis/{id}", name="op_gestapp_avis_show", methods={"GET"})
     */
    public function show(Avis $avi): Response
    {
        $avis = $this->getDoctrine()->getRepository(Avis::class)->findAll();
        return $this->render('gestapp/avis/show.html.twig', [
            'avis' => $avis,
        ]);
    }

    /**
     * @Route("/op_admin/gestapp/avis/{id}/edit", name="op_gestapp_avis_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Avis $avi): Response
    {
        $form = $this->createForm(AvisType::class, $avi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('op_Gestapp_avis_index');
        }

        return $this->render('gestapp/avis/edit.html.twig', [
            'avi' => $avi,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/op_admin/Gestapp/avis/{id}", name="op_Gestapp_avis_delete", methods={"POST"})
     */
    public function delete(Request $request, Avis $avi): Response
    {
        if ($this->isCsrfTokenValid('delete'.$avi->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($avi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('op_gestapp_avis_index');
    }

    /**
     * @Route("/op_admin/gestapp/avis/view", name="op_Gestapp_avis_view", methods={"GET"})
     */
    public function view(AvisRepository $avisRepository): Response
    {
        return $this->render('gestapp/avis/view.html.twig', [
            'avis' => $avisRepository->findAll(),
        ]);
    }
}
