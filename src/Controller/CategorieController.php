<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CategorieController extends AbstractController
{
    #[Route('/api/v1/get_categories', name: 'api_get_categories', methods: ['GET'])]
    public function getCategories(CategorieRepository $categorieRepository): JsonResponse
    {
        $categories = $categorieRepository->findAll();

       
        $data = [];
        foreach ($categories as $c) {
            $data[] = [
                'id' => $c->getIdCategorie(),
                'nom' => $c->getNomCategorie(),
            ];
        }

        
        return $this->json($data);
    }
}