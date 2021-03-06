<?php

namespace App\Controller\Gestapp;

use App\Cart\CartService;
use App\Entity\Gestapp\Product;
use App\Entity\Gestapp\ProductCategory;
use App\Entity\Gestapp\ProductCustomize;
use App\Entity\Gestapp\ProductNature;
use App\Form\Gestapp\ProductType;
use App\Repository\Gestapp\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 *
 */
class ProductController extends AbstractController
{
    protected $productRepository;
    protected $cartService;

    public function __construct(ProductRepository $productRepository, CartService $cartService)
    {
        $this->productRepository = $productRepository;
        $this->cartService = $cartService;
    }

    /**
     * @Route("/gestapp/product/", name="op_gestapp_product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $natures = $this->getDoctrine()->getRepository(ProductNature::class)->findAll();
        $categories = $this->getDoctrine()->getRepository(ProductCategory::class)->findAll();

        $data = $productRepository->findAll();
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            300
        );

        return $this->render('gestapp/product/index.html.twig', [
            'products' => $products,
            'natures' => $natures,
            'categories' => $categories
        ]);
    }

    /**
     * @Route("/gestapp/product/new", name="op_gestapp_product_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $natures = $this->getDoctrine()->getRepository(ProductNature::class)->findAll();
        $categories = $this->getDoctrine()->getRepository(ProductCategory::class)->findAll();
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('op_gestapp_product_index');
        }

        return $this->render('gestapp/product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'natures' => $natures,
            'categories' => $categories
        ]);
    }

    /**
     * @Route("/webbapp/product/{id}", name="op_gestapp_product_show", methods={"GET"})
     * @param Product $product
     * @param Request $request
     * @return Response
     */
    public function show(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession()->get('name_uuid');
        // On teste si le panier existe en session
        $cart = $request->getSession()->get('cart');
        if($cart){
            //dd($cart);
            // r??cup??ration des items du panier
            $detailedCart = $this->cartService->getDetailedCartItem();
            //dd($detailedCart);
            $productCustomize = $em->getRepository(ProductCustomize::class)->findOneBy(array('product' => $product->getId()), array('id'=>'DESC'));
            // Dans le cas ou le panier existe et contient un produit
            if(!$productCustomize){
                $lsformats = $product->getFormats();
                $format = $lsformats[0];
                // cr??ation d'une personnalisation du produit
                $productCustomize = new ProductCustomize();
                $productCustomize->setUuid($session);
                $productCustomize->setName('');
                $productCustomize->setProduct($product);
                $productCustomize->setFormat($format);
                $em->persist($productCustomize);
                $em->flush();

                //dd($productCustomize);

                return $this->render('gestapp/product/show.html.twig', [
                    'product' => $product,
                    'session' => $session,
                    'items' => $detailedCart,
                    'customizes' => $productCustomize
                ]);

            }


            // On retourne la vue du produit avec les ??l??ments du panier
            return $this->render('gestapp/product/show.html.twig', [
                'product' => $product,
                'items' => $detailedCart,
                'session' => $session,
                'customizes' => $productCustomize
            ]);
        }

        //dd($format);
        //$productCustomize->setFormat();
        //$em->flush();

        return $this->render('gestapp/product/show.html.twig', [
            'product' => $product,
            'session' => $session
        ]);
    }

    /**
     * @Route("/webbapp/product/showjson/{id}", name="op_gestapp_product_showjson", methods={"GET"})
     * @param Product $product
     * @param Request $request
     * @return Response
     */
    public function showjson(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $detailedCart = $this->cartService->getDetailedCartItem();

        $session = $request->getSession()->get('name_uuid');
        // R??cup??ration des personnalisations du produit
        $productCustomize = $em->getRepository(ProductCustomize::class)->findBy(array('product' => $product->getId()));
        // Retourne une r??ponse en json
        return $this->json([
            'code'          => 200,
            'message'       => "Le produit a ??t?? correctement ajout??.",
            'count'         => $this->renderView('gestapp/product/include/_count.html.twig', [
                'items' => $detailedCart,
                'product' => $product,
                'session' => $session,
                'customizes' => $productCustomize
            ])
        ], 200);
    }

    /**
     * @Route("/gestapp/product/{id}/edit", name="op_gestapp_product_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        $natures = $em->getRepository(ProductNature::class)->findAll();
        $categories = $em->getRepository(ProductCategory::class)->findAll();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('op_gestapp_product_index');
        }

        return $this->render('gestapp/product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'natures' => $natures,
            'categories' => $categories
        ]);
    }

    /**
     * @Route("/gestapp/product/{id}", name="op_gestapp_product_delete", methods={"POST"})
     */
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('op_gestapp_product_index');
    }

    /**
     * Permet d'activer ou de d??sactiver la mise en ligne d'un produit
     * @Route("/gestapp/product/online/{id}/json", name="op_gestapp_product_online")
     */
    public function jsonline(Product $product, EntityManagerInterface $em) : Response
    {
        $user = $this->getUser();
        $isonline = $product->getIsOnLine();
        // renvoie une erreur car l'utilisateur n'est pas connect??
        if(!$user) return $this->json([
            'code' => 403,
            'message'=> "Vous n'??tes pas connect??"
        ], 403);
        // Si la page est d??ja publi??e, alors on d??publie
        if($isonline == true){
            $product->setIsOnLine(0);
            $em->flush();
            return $this->json([
                'code'      => 200,
                'message'   => "Le produit est n'est plus publi?? en ligne."
            ], 200);
        }
        // Si la page est d??ja d??publi??e, alors on publie
        $product->setIsOnLine(1);
        $em->flush();
        return $this->json([
            'code'          => 200,
            'message'       => 'Le produit est mis en ligne.'
        ], 200);
    }

    /**
     * Permet d'activer ou de d??sactiver la mise en ligne d'un produit
     * @Route("/gestapp/product/jsstar/{id}/json", name="op_gestapp_product_star")
     */
    public function jsstar(Product $product, EntityManagerInterface $em) : Response
    {
        $user = $this->getUser();
        $isstar = $product->getIsStar();
        // renvoie une erreur car l'utilisateur n'est pas connect??
        if(!$user) return $this->json([
            'code' => 403,
            'message'=> "Vous n'??tes pas connect??"
        ], 403);
        // Si la page est d??ja publi??e, alors on d??publie
        if($isstar == true){
            $product->setIsStar(0);
            $em->flush();
            return $this->json([
                'code'      => 200,
                'message'   => "Le produit est n'est plus publi?? en vedette."
            ], 200);
        }
        // Si la page est d??ja d??publi??e, alors on publie
        $product->setIsStar(1);
        $em->flush();
        return $this->json([
            'code'          => 200,
            'message'       => 'Le produit est publi?? en vedette.'
        ], 200);
    }

    /**
     * Espace E-Commerce : Liste les produits
     * @Route("/gestapp/product/alldispo", name="op_gestapp_product_alldispo", methods={"GET","POST"})
     */
    public function ListAllProductDispo()
    {
        $products = $this->getDoctrine()->getRepository(Product::class)->listAllProduct();

        return $this->render('gestapp/product/listallproductdispo.html.twig',[
            'products' => $products
        ]);
    }



    /**
     * Espace E-Commerce : Liste les produits sur les natures
     * @Route("/gestapp/product/oneNat/{idnat}", name="op_gestapp_product_onecat", methods={"POST"})
     */
    public function ListOneNatProduct(Request $request, PaginatorInterface $paginator, $idnat)
    {
        $data = $this->getDoctrine()->getRepository(Product::class)->oneNature($idnat);

        $nature = $this->getDoctrine()->getRepository(ProductNature::class)->find($idnat);
        $categories = $this->getDoctrine()->getRepository(ProductCategory::class)->findBy(array('Nature'=> $idnat));

        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('gestapp/product/listonenatproduct.html.twig',[
            'products' => $products,
            'categories' => $categories,
            'nat' => $idnat,
            'natorcat' => 'nature'
        ]);
    }

    /**
     * Espace E-Commerce : Renvoie les produits filtr??s par nature
     * @Route("/gestapp/product/oneNat/filternature", name="op_gestapp_products_filternature", methods={"GET","POST"})
     */
    public function filternature(Request $request, ProductRepository $productRepository,PaginatorInterface $paginator): Response
    {
        $filters = $request->get("categories");
        $page = $request->get('page');

        $data = $productRepository->ListFilterscategories($filters);

        $products = $paginator->paginate(
            $data, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            8 /*limit per page*/
        );
        if(!$page){
            return $this->json([
                'code'      => 200,
                'message'   => "Ok",
                'liste' => $this->renderView('gestapp/product/include/_product.html.twig', [
                    'products' => $products,
                    'page' => $request->query->getInt('page', 1),
                ])
            ], 200);
        }else{
            return $this->json([
                'code'      => 200,
                'message'   => "Ok",
                'liste' => $this->renderView('gestapp/product/include/_product.html.twig', [
                    'products' => $products,
                    'page' => $request->query->getInt('page', $page),
                ])
            ], 200);
        }
    }

    /**
     * Espace E-Commerce : Liste les produits sur les cat??gories
     * @Route("/gestapp/product/oneCat/{idcat}", name="op_gestapp_product_onecat", methods={"POST"})
     */
    public function ListOneCatProduct(Request $request, PaginatorInterface $paginator, $idcat)
    {
        $childs = $this->getDoctrine()->getRepository(ProductCategory::class)->findChilds($idcat);
        //dd($idcat);
        if (!$childs){
            $data = $this->getDoctrine()->getRepository(Product::class)->oneCategory($idcat);
        }
        else{
            $data = $this->getDoctrine()->getRepository(Product::class)->childCategory($childs);
        }

        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            8
        );

        $category = $this->getDoctrine()->getRepository(ProductCategory::class)->find($idcat);

        return $this->render('gestapp/product/listonecatproduct.html.twig',[
            'products' => $products,
            'category' => $category,
            'childs' => $childs,
            'cat' => $idcat->getId(),
            'natorcat' => 'category'
        ]);
    }

    /**
     * @Route("/gestapp/product/oneCat/filtercategories", name="op_gestapp_products_filtercategories", methods={"GET","POST"})
     */
    public function filtercategories(Request $request, ProductRepository $productRepository,PaginatorInterface $paginator): Response
    {
        $filters = $request->get("categories");
        $page = $request->get('page');

        $data = $productRepository->ListFilterscategories($filters);

        $products = $paginator->paginate(
            $data, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            8 /*limit per page*/
        );
        if(!$page){
            return $this->json([
                'code'      => 200,
                'message'   => "Ok",
                'liste' => $this->renderView('gestapp/product/include/_product.html.twig', [
                    'products' => $products,
                    'page' => $request->query->getInt('page', 1),
                ])
            ], 200);
        }else{
            return $this->json([
                'code'      => 200,
                'message'   => "Ok",
                'liste' => $this->renderView('gestapp/product/include/_product.html.twig', [
                    'products' => $products,
                    'page' => $request->query->getInt('page', $page),
                ])
            ], 200);
        }
    }

    /**
     * Espace E-Commerce : Renvoie les produits filtr??s par cat??gories selon la nature.
     * @Route("/gestapp/product/filterwebapp", name="op_gestapp_products_filterwebapp", methods={"GET","POST"})
     */
    public function filterWebapp(Request $request, ProductRepository $productRepository,PaginatorInterface $paginator): Response
    {
        // on r??cup??re les ??l??ments n??cessaires ?? la filtration et le renvoie des donn??es en JSON
        $filters = $request->get("categories");
        $idcat = $request->get('category');
        $nature = $request->get('nature');
        $page = $request->get('page');

        if($filters){
            $data = $productRepository->ListFilterscategories($filters);
        }else{
            if($nature){
                $data = $this->getDoctrine()->getRepository(Product::class)->oneNatureName($nature);
            }
            else{
                $childs = $this->getDoctrine()->getRepository(ProductCategory::class)->findChilds($idcat);
                if (!$childs){
                    $data = $this->getDoctrine()->getRepository(Product::class)->oneCategory($idcat);
                }
                else{
                    $data = $this->getDoctrine()->getRepository(Product::class)->childCategory($childs);
                }
            }
        }
        $products = $paginator->paginate(
            $data, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            8 /*limit per page*/
        );
        if(!$page){
            return $this->json([
                'code'      => 200,
                'message'   => "Ok",
                'liste' => $this->renderView('gestapp/product/include/_product.html.twig', [
                    'products' => $products,
                    'page' => $request->query->getInt('page', 1),
                ])
            ], 200);
        }else{
            return $this->json([
                'code'      => 200,
                'message'   => "Ok",
                'liste' => $this->renderView('gestapp/product/include/_product.html.twig', [
                    'products' => $products,
                    'page' => $request->query->getInt('page', $page),
                ])
            ], 200);
        }
    }

    /**
     * @Route("/gestapp/product/oneCat/filtercategory", name="op_gestapp_products_filtercategory", methods={"GET","POST"})
     */
    public function filtercategory(Request $request, ProductRepository $productRepository,PaginatorInterface $paginator): Response
    {
        //dd($request->get('category'));
        $filter = $request->get("category");
        $page = $request->get('page');

        $data = $productRepository->ListFilterscategory($filter);
        //dd($data);

        $products = $paginator->paginate(
            $data, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            8 /*limit per page*/
        );
        if(!$page){
            return $this->json([
                'code'      => 200,
                'message'   => "Ok",
                'liste' => $this->renderView('gestapp/product/include/_product.html.twig', [
                    'products' => $products,
                    'page' => $request->query->getInt('page', 1),
                ])
            ], 200);
        }else{
            return $this->json([
                'code'      => 200,
                'message'   => "Ok",
                'liste' => $this->renderView('gestapp/product/include/_product.html.twig', [
                    'products' => $products,
                    'page' => $request->query->getInt('page', $page),
                ])
            ], 200);
        }
    }


    /**
     * Espace E-Commerce : Liste les produits
     * @Route("/gestapp/product/del/{id}", name="op_gestapp_product_del", methods={"POST"}, requirements={"id":"\d+"})
     */
    public function ProductDel(Product $product, Request $request, EntityManagerInterface $em)
    {
        $user = $this->getUser();
        // si utilisateur non connect??
        if(!$user) return $this->json([
            'code' => 403,
            'message'=> "Vous n'??tes pas connect??"
        ], 403);

        // code de suppression
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($product);
        $entityManager->flush();
        $em->flush();

        // on r??cup??re toute le liste de produits pour le rafraichissement
        $products = $em->getRepository(Product::class)->findAll();

        // Retourne une r??ponse en json
        return $this->json([
            'code'          => 200,
            'message'       => "Le produit a ??t?? correctement supprim??.",
            'liste'         => $this->renderView('gestapp/product/include/_liste.html.twig', [
                'products' => $products
            ])
        ], 200);
    }

    /**
     * Affiche une section contenant les 3 derni??res cartes de la nature de produit
     * @Route("/gestapp/product/lastnatproduct/{idnat}", name="op_gestapp_product_del", methods={"POST"}, requirements={"id":"\d+"})
     */
    public function LastNatProduct($idnat, EntityManagerInterface $entityManager)
    {
        $products = $entityManager->getRepository(Product::class)->lastProduct($idnat);

        return $this->render('gestapp/product/lastnatproduct.html.twig',[
            'products' => $products,
            'idnat' => $idnat
        ]);
    }
}
