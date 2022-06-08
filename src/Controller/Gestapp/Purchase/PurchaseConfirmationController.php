<?php

namespace App\Controller\Gestapp\Purchase;

use App\Cart\CartService;
use App\Entity\Gestapp\ProductCustomize;
use App\Entity\Gestapp\Purchase;
use App\Entity\Gestapp\PurchaseItem;
use App\Form\Gestapp\CartConfirmationType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class PurchaseConfirmationController extends AbstractController
{

    protected $cartService;
    protected $em;
    private $requestStack;


    public function __construct(CartService $cartService, EntityManagerInterface $em, RequestStack $requestStack)
    {
        $this->cartService = $cartService;
        $this->em = $em;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/webapp/purchase/confirm", name="op_webapp_purchase_confirm")
     * @IsGranted("ROLE_USER", message="Vous devez être inscrit sur la plateforme pour confirmer votre commande")
     */
    public function confirm(Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(CartConfirmationType::class);
        $form->handleRequest($request);

        if(!$form->isSubmitted()) {
            $this->addFlash('warning', 'vous devez compléter le formulaire');
            return $this->redirectToRoute('op_webapp_cart_showcart');
        }

        $user = $this->getUser();

        $cartItems = $this->cartService->getDetailedCartItem();
        if(count($cartItems) === 0){
            $this->addFlash('warning', 'le panier est vide, impossible de commander');
            return $this->redirectToRoute('op_webapp_cart_showcart');
        }

        /** @var Purchase */
        $purchase = $form->getData();
        //dd($this->cartService->getTotal());

        $uuid = substr($this->requestStack->getSession()->getId(), 0, 8);
        $purchase
            ->setCustomer($user)
            ->setNumPurchase($uuid)
            ->setPurchasedAt(new DateTime())
            ->setTotal($this->cartService->getTotal());

        $this->em->persist($purchase);

        foreach($this->cartService->getDetailedCartItem() as $cartItem){
            //récupération des personnalisation du produit
            $product = $cartItem->product;
            //dd($product);
            $idproduct = $product->getId();
            //dd($idproduct);
            $listCustom = $em->getRepository(ProductCustomize::class)->findOneBy(array('product'=> $product), array('id'=>'DESC'));
            //dd($listCustom);
            $format = $listCustom->getFormat();

            $purchaseItem = new PurchaseItem;
            $purchaseItem
                ->setPurchase($purchase)
                ->setProduct($cartItem->product)
                ->setProductName($cartItem->product->getName())
                ->setProductQty($cartItem->qty)
                ->setProductPrice($cartItem->product->getPrice())
                ->setTotalItem($cartItem->getTotal())
                ->setFormat($listCustom->getFormat())
                ->setCustomerName($listCustom->getName())
            ;
            $this->em->persist($purchaseItem);
            $em->remove($listCustom);
        }

        $this->em->flush();
        $this->cartService->emptyCart();
        $this->addFlash('success', "La commande est enregistré");

        // Renouvellement de la session
        $session = $this->get('session');
        $session->migrate();


        return $this->redirectToRoute('op_webapp_purchases_index');
    }

    /**
     * Supprime la commande sélectionnée en amont et rafraichi la page de l'utilisateur courant
     * @param Purchase $purchase
     * @Route("/gestapp/purchase/delete/{id}", name="op_gestapp_purchase_delete")
     */
    public function deletePurchase(Purchase $purchase)
    {
        $this->em->remove($purchase);
        $this->em->flush();

        $user = $this->getUser();
        $purchases =  $this->em->getRepository(Purchase::class)->findBy(array('customer'=>$user));

        return $this->json([
            'code'          => 200,
            'message'       => 'La commande et son contenu à été effacée.',
            'liste'         =>  $this->renderView('gestapp/purchase/include/_liste.html.twig', [
                'purchases' => $purchases,
                'hide' => 0
            ])
        ], 200);
    }
}