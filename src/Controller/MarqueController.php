<?php

namespace App\Controller;

use App\Entity\Marque;
use App\Entity\Modele;
use App\Repository\MarqueRepository;
use App\Repository\ModeleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class MarqueController extends AbstractController
{
	// methode pour recuperer toutes les marques
	#[Route('/api/v1/get_marques', name: 'api_get_marques', methods: ['GET'])]
	public function getAll(MarqueRepository $marqueRepository): JsonResponse
	{
		$marques = $marqueRepository->findAll();

		$unique = [];
		foreach ($marques as $marque) {
			$nom = trim($marque->getNomMarque());
			if ($nom === '' || isset($unique[$nom])) {
				continue;
			}

			$unique[$nom] = $this->formatMarque($marque);
		}

		return $this->json(array_values($unique));
	}

	// methode pour recuperer les marques d'un modele
	#[Route('/api/v1/get_marques_by_modele/{idModele}', name: 'api_get_marques_by_modele', methods: ['GET'], requirements: ['idModele' => '\\d+'])]
	public function getByModele(int $idModele, ModeleRepository $modeleRepository, MarqueRepository $marqueRepository): JsonResponse
	{
		$modele = $modeleRepository->find($idModele);
		if (!$modele) {
			return $this->json(['message' => 'Modele introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$marque = $modele->getMarque();
		if (!$marque) {
			return $this->json([]);
		}

		return $this->json([
			$this->formatMarque($marque),
		]);
	}

	// methode pour recuperer une marque
	#[Route('/api/v1/get_marque/{id}', name: 'api_get_marque', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, MarqueRepository $marqueRepository): JsonResponse
	{
		$marque = $marqueRepository->find($id);
		if (!$marque) {
			return $this->json(['message' => 'Marque introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatMarque($marque));
	}

	// methode pour ajouter une marque
	#[Route('/api/v1/new_marque', name: 'api_new_marque', methods: ['POST'])]
	public function create(
		Request $request,
		ModeleRepository $modeleRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomMarque = trim((string) ($data['nom_marque'] ?? $data['nomMarque'] ?? ''));
		$idModele = (int) ($data['id_modele'] ?? $data['idModele'] ?? 0);

		if ($nomMarque === '') {
			return $this->json(['message' => 'nom_marque est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$marque = new Marque();
		$marque->setNomMarque($nomMarque);
		$entityManager->persist($marque);

		if ($idModele > 0) {
			$modele = $modeleRepository->find($idModele);
			if (!$modele) {
				return $this->json(['message' => 'Modele introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}

			$modele->setMarque($marque);
		}

		$entityManager->persist($marque);
		$entityManager->flush();

		return $this->json([
			'message' => 'La marque a ete ajoutee avec succes.',
			'marque' => $this->formatMarque($marque),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier une marque
	#[Route('/api/v1/edit_marque/{id}', name: 'api_edit_marque', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		MarqueRepository $marqueRepository,
		ModeleRepository $modeleRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$marque = $marqueRepository->find($id);
		if (!$marque) {
			return $this->json(['message' => 'Marque introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (isset($data['nom_marque']) || isset($data['nomMarque'])) {
			$nomMarque = trim((string) ($data['nom_marque'] ?? $data['nomMarque']));
			if ($nomMarque === '') {
				return $this->json(['message' => 'nom_marque ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$marque->setNomMarque($nomMarque);
		}

		if (isset($data['id_modele']) || isset($data['idModele'])) {
			$idModele = (int) ($data['id_modele'] ?? $data['idModele']);
			if ($idModele <= 0) {
				return $this->json(['message' => 'id_modele invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$modele = $modeleRepository->find($idModele);
			if (!$modele) {
				return $this->json(['message' => 'Modele introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}

			$modele->setMarque($marque);
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'La marque a ete modifiee avec succes.',
			'marque' => $this->formatMarque($marque),
		]);
	}











	// methode pour supprimer une marque
	#[Route('/api/v1/delete_marque/{id}', name: 'api_delete_marque', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Marque $marque, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($marque);
		$entityManager->flush();

		return $this->json([
			'message' => 'La marque a ete supprimee avec succes.',
		]);
	}

	private function formatMarque(Marque $marque): array
	{
		$modeles = array_map(
			static fn (Modele $modele): array => [
				'id_modele' => $modele->getIdModele(),
				'nom_modele' => $modele->getNomModele(),
			],
			$marque->getModeles()->toArray()
		);

		return [
			'id_marque' => $marque->getIdMarque(),
			'nom_marque' => $marque->getNomMarque(),
			'modele' => $modeles[0] ?? null,
			'modeles' => $modeles,
		];
	}
}
