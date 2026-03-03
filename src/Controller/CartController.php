<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('', name: 'cart_index', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'items' => $cartService->getItems(),
            'subtotal' => $cartService->getSubtotal(),
            'total_items' => $cartService->getTotal(),
        ]);
    }

    #[Route('/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(int $id, Request $request, ProductRepository $productRepository, CartService $cartService): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $quantity = (int) $request->request->get('quantity', 1);
        $quantity = max(1, $quantity); // Ensure quantity is at least 1

        $cartService->addItem($product, $quantity);
        $this->addFlash('success', "{$product->getName()} added to cart!");

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('product_list');
    }

    #[Route('/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id, CartService $cartService): Response
    {
        $cartService->removeItem($id);
        $this->addFlash('success', 'Product removed from cart');

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/update/{id}', name: 'cart_update', methods: ['POST'])]
    public function update(int $id, Request $request, CartService $cartService): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        $cartService->updateQuantity($id, $quantity);

        $this->addFlash('success', 'Cart updated');
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(CartService $cartService): Response
    {
        $cartService->clear();
        $this->addFlash('success', 'Cart cleared');

        return $this->redirectToRoute('cart_index');
    }
}
