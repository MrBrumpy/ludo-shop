<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'app_categories')]
    public function index(CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        $includeMature = $this->isMatureAllowed();

        $categories = $categoryRepository->findAll();

        $counts = [];
        foreach ($categories as $category) {
            foreach ($categories as $category) {
                $categoryId = $category->getId();

                if (null === $categoryId) {
                    continue;
                }

                $counts[$categoryId] = $productRepository->countActiveByCategory($category, $includeMature);
            }
        }

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
            'counts' => $counts,
        ]);
    }

    #[Route('/categories/{slug}', name: 'app_category_show')]
    public function show(string $slug, CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        if (null === $category) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $includeMature = $this->isMatureAllowed();
        $products = $productRepository->findActiveByCategory($category, $includeMature);

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    private function isMatureAllowed(): bool
    {
        $user = $this->getUser();

        return $user instanceof User && $user->isAdult();
    }
}
