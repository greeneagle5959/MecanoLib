<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Lier;
use App\Entity\Prestation;
use App\Entity\Proposer;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class CategorieController extends AbstractController
{
    // méthode pour récupérer toutes les categories
	#[Route('/api/v1/get_categories', name: 'api_get_categories', methods: ['GET'])]
	public function getAllCategories(CategorieRepository $categorieRepository): JsonResponse
	{
		$categories = $categorieRepository->findAll();

		$nomCategories = array_map(
		    static fn (Categorie $categorie): array => [
			'id_categorie' => $categorie->getIdCategorie(),
			'nom_categorie' => $categorie->getNomCategorie(),
		    ],
		    $categories
		);

		return $this->json($nomCategories);
	}
// méthode pour récupérer les détails d'une categorie
	#[Route('/api/v1/get_categorie/{id}', name: 'api_get_categorie', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, CategorieRepository $categorieRepository): JsonResponse
	{
		$categorie = $categorieRepository->find($id);
		if (!$categorie) {
		    return $this->json(['message' => 'Categorie introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json([
		    'id_categorie' => $categorie->getIdCategorie(),
		    'nom_categorie' => $categorie->getNomCategorie(),
		]);
	}
// méthode pour ajouter une categorie
	#[Route('/api/v1/new_categorie', name: 'api_new_categorie', methods: ['POST'])]
	public function newCategorie(Request $request, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
		    return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomCategorie = trim((string) ($data['nom_categorie'] ?? $data['nomcategorie'] ?? ''));
		if ($nomCategorie === '') {
		    return $this->json(['message' => 'Le nom de categorie est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$categorie = new Categorie();
		$categorie->setNomCategorie($nomCategorie);

		$entityManager->persist($categorie);
		$entityManager->flush();

		return $this->json([
		    'message' => 'La categorie a ete ajoutee avec succes.',
		    'id_categorie' => $categorie->getIdCategorie(),
		], JsonResponse::HTTP_CREATED);
	}
// méthode pour modifier une categorie
	#[Route('/api/v1/edit_categorie/{id}', name: 'api_edit_categorie', methods: ['POST'], requirements: ['id' => '\\d+'])]
	#[Route('/api/v1/categories/{id}', name: 'api_edit_categorie_rest', methods: ['PUT', 'PATCH'], requirements: ['id' => '\\d+'])]
	public function edit(int $id, Request $request, CategorieRepository $categorieRepository, EntityManagerInterface $entityManager): JsonResponse
	{
		$categorie = $categorieRepository->find($id);
		if (!$categorie) {
		    return $this->json(['message' => 'Categorie introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
		    return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomCategorie = trim((string) ($data['nom_categorie'] ?? $data['nomcategorie'] ?? ''));
		if ($nomCategorie === '') {
		    return $this->json(['message' => 'Le nom de categorie est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$categorie->setNomCategorie($nomCategorie);
		$entityManager->flush();

		return $this->json([
		    'message' => 'La categorie a ete modifiee avec succes.',
		    'id_categorie' => $categorie->getIdCategorie(),
		    'nom_categorie' => $categorie->getNomCategorie(),
		]);
	}
// méthode pour supprimer une categorie et ses prestations liees
	#[Route('/api/v1/delete_categorie/{id}', name: 'api_delete_categorie', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	#[Route('/api/v1/categories/{id}', name: 'api_delete_categorie_rest', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(int $id, CategorieRepository $categorieRepository, EntityManagerInterface $entityManager): JsonResponse
	{
		$categorie = $categorieRepository->find($id);
		if (!$categorie) {
		    return $this->json(['message' => 'Categorie introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$prestations = $categorie->getPrestations()->toArray();
		$deletedPrestations = 0;

		foreach ($prestations as $prestation) {
			if (!$prestation instanceof Prestation) {
				continue;
			}

			$propositions = $entityManager->getRepository(Proposer::class)->findBy([
				'prestation' => $prestation,
			]);

			foreach ($propositions as $proposition) {
				$entityManager->remove($proposition);
			}

			$liers = $entityManager->getRepository(Lier::class)->findBy([
				'prestation' => $prestation,
			]);

			foreach ($liers as $lier) {
				$entityManager->remove($lier);
			}

			$entityManager->remove($prestation);
			$deletedPrestations++;
		}

		$entityManager->remove($categorie);
		$entityManager->flush();

		return $this->json([
		    'message' => 'La categorie a ete supprimee avec succes.',
		    'deleted_prestations' => $deletedPrestations,
		]);
	}
}

