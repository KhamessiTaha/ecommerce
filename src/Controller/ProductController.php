<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(ProductRepository $productRepository): Response
    {
        return $this->render('product/list.html.twig', [
            'products' => $productRepository->findAll()
        ]);
    }

    #[Route('/products', name: 'product_list')]
    public function list(ProductRepository $productRepository): Response
    {
        return $this->render('product/list.html.twig', [
            'products' => $productRepository->findAll()
        ]);
    }

    #[Route('/products/{id}', name: 'product_show')]
    public function show(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/api/products', name: 'api_products')]
    public function api(ProductRepository $productRepository): Response
    {
        return $this->json($productRepository->findAll());
    }
}