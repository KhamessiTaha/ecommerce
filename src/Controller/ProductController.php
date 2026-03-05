<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(Request $request, ProductRepository $productRepository): Response
    {
        $keyword = $request->query->get('keyword');
        $category = $request->query->get('category');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');

        // Parse price values
        $minPrice = $minPrice ? (float) $minPrice : null;
        $maxPrice = $maxPrice ? (float) $maxPrice : null;

        // Get products with search/filter
        $products = $productRepository->searchWithFilters($keyword, $category, $minPrice, $maxPrice);
        $categories = $productRepository->getCategories();

        return $this->render('product/list.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'current_keyword' => $keyword,
            'current_category' => $category,
            'current_min_price' => $minPrice,
            'current_max_price' => $maxPrice,
        ]);
    }

    #[Route('/products', name: 'product_list')]
    public function list(Request $request, ProductRepository $productRepository): Response
    {
        $keyword = $request->query->get('keyword');
        $category = $request->query->get('category');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');

        // Parse price values
        $minPrice = $minPrice ? (float) $minPrice : null;
        $maxPrice = $maxPrice ? (float) $maxPrice : null;

        // Get products with search/filter
        $products = $productRepository->searchWithFilters($keyword, $category, $minPrice, $maxPrice);
        $categories = $productRepository->getCategories();

        return $this->render('product/list.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'current_keyword' => $keyword,
            'current_category' => $category,
            'current_min_price' => $minPrice,
            'current_max_price' => $maxPrice,
        ]);
    }

    #[Route('/products/{id}', name: 'product_show')]
    public function show(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        // Get similar products recommendations
        $recommendations = $productRepository->getRecommendations($product, 4);

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'recommendations' => $recommendations
        ]);
    }

    #[Route('/api/products', name: 'api_products')]
    public function api(ProductRepository $productRepository): Response
    {
        return $this->json($productRepository->findAll());
    }
}