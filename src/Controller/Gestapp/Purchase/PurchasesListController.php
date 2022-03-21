<?php

namespace App\Controller\Gestapp\Purchase;

use App\Entity\Gestapp\Purchase;
use App\Repository\Gestapp\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Admin\Member;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class PurchasesListController extends abstractController
{
    /**
     * @Route("/webapp/purchases/", name="op_webapp_purchases_index")
     * @IsGranted("ROLE_USER", message="Vous devez être connecté pour accéder à vos commandes")
     */
    public function index()
    {
        /** @var Member */
        $member = $this->getUser();

        return $this->render('gestapp/purchase/index.html.twig',[
            'purchases'=> $member->getPurchases(),
            'hide' => 0,
        ]);
    }

    /**
     * @Route("/opadmin/purchases/", name="op_admin_purchases_index")
     * @IsGranted("ROLE_USER", message="Vous devez être connecté pour accéder à l'administration")
     */
    public function listAdmin(PurchaseRepository $purchaseRepository, Request $request, PaginatorInterface $paginator)
    {
        /** @var Member */
        $member = $this->getUser();

        $data = $purchaseRepository->findBy(array('customer'=> $member));
        $purchases = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            300
        );

        return $this->render('gestapp/purchase/list.html.twig',[
            'purchases'=> $purchases,
            'hide' => 0,
        ]);
    }

    /**
     * @return void
     * Function pour faire évoluer l'etat de paiement de la commande par le client
     * @Route("/opadmin/purchases/updateStatePaid/{id}/{status}", name="op_admin_purchases_updateStatePaid")
     */
    public function updateStatePaid(Purchase $purchase, $status, EntityManagerInterface $em)
    {
        $numPurchase = $purchase->getNumPurchase();
        $member = $purchase->getCustomer();
        $emailMember = $member->getEmail();
        $fnMember = $member->getFirstName();
        $lsMember = $member->getLastName();

        if($status = "CANCELLED"){
            $purchase->setStatus($status);
            $em->flush();
            // Envoi du mail de nouvelle recomandation au membre recommandé
            $email = (new TemplatedEmail())
                ->from('postmaster@openpixl.fr')
                ->to($emailMember)
                //->cc('cc@example.com')
                //->bcc('bcc@example.com')
                //->replyTo('fabien@example.com')
                //->priority(Email::PRIORITY_HIGH)
                ->subject('Cartes de prières - Commande ' . $numPurchase . 'test')
                //->text('Sending emails is fun again!')
                ->htmlTemplate('email/updatePurchaseState.html.twig')
                ->context([
                    'author' => 'Soeur Marie',
                    'commande' => $numPurchase,
                    'prenomDestin' => $fnMember,
                    'nomDestin' => $lsMember,
                ]);
            $mailer->send($email);
        }
        return $this->json([
            'code'          => 200,
            'message'       => "La commande a été correctement modifié.",
            'liste'         => $this->renderView('gestapp/product/include/_liste.html.twig', [
                'purchases' => $purchases
            ])
        ], 200);

    }

    /**
     * @return void
     * Fonction de progression sur la commande, suivi des produits à réaliser à la main, et de l'envoi de la commande par la poste.
     * @Route("/opadmin/purchases/updateStatePurchase/{id}/{status}", name="op_admin_purchases_updateStatePurchase")
     */
    public function updateStatePurchase(Purchase $purchase)
    {
        $numPurchase = $purchase->getNumPurchase();
        $member = $purchase->getCustomer();
        dd($member);

        if($status = "CANCELLED"){
            $purchase->setStatus($status);
            $em->flush();
            // Envoi du mail de nouvelle recomandation au membre recommandé
            $email = (new TemplatedEmail())
                ->from('postmaster@openpixl.fr')
                ->to($email)
                //->cc('cc@example.com')
                //->bcc('bcc@example.com')
                //->replyTo('fabien@example.com')
                //->priority(Email::PRIORITY_HIGH)
                ->subject('Cartes de prières - Commande ' . $purchase['numPurchase']. 'test')
                //->text('Sending emails is fun again!')
                ->htmlTemplate('email/updatePurchaseState.html.twig')
                ->context([
                    'author' => $author,
                    'titleRecom' => $titleRecom,
                    'prenomDestin' => $firstNameDesti,
                    'nomDestin' => $lastNameDesti,
                ]);
            $mailer->send($email);
        }
        return $this->json([
            'code'          => 200,
            'message'       => "La commande a été correctement modifié.",
            'liste'         => $this->renderView('gestapp/product/include/_liste.html.twig', [
                'purchases' => $purchases
            ])
        ], 200);
    }

    /**
     * Affiche les nouvelles commandes sur le dashboard 
     * @Route("/op_admin/gestapp/purchases/byuserNew/{hide}", name="op_gestapp_purchases_byusernewpurchases", methods={"GET"})
     */
    public function byUserReceiptNewPurchases($hide, PurchaseRepository $purchaseRepository): Response
    {
        $user = $this->getUser();
        $purchases = $this->getDoctrine()->getRepository(Purchase::class)->findByUserReceiptNew($user);
        return $this->render('gestapp/purchase/byuserReceipt.html.twig', [
            'purchases' => $purchases,
            'hide' => $hide,
        ]);
    }

    /**
     * @Route("/op_admin/gestapp/purchases/byuserSend/{hide}", name="op_gestapp_purchases_byusersend", methods={"GET"})
     */
    public function byUserSend($hide,PurchaseRepository $purchaseRepository): Response
    {
        $user = $this->getUser();
        $purchases = $this->getDoctrine()->getRepository(Purchase::class)->findByUserSend($user);
        $hide = 1;
        return $this->render('gestapp/purchase/byuserSend.html.twig', [
            'purchases' => $purchases,
            'hide' => $hide
        ]);
    }
}