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

final class ModeleController extends AbstractController
{
	// methode pour recuperer tous les modeles
	#[Route('/api/v1/get_modeles', name: 'api_get_modeles', methods: ['GET'])]
	public function getAll(ModeleRepository $modeleRepository): JsonResponse
	{
		$modeles = $modeleRepository->findAll();

		$result = array_map(
			fn (Modele $modele): array => $this->formatModele($modele),
			$modeles
		);

		return $this->json($result);
	}

	// methode pour recuperer un modele
	#[Route('/api/v1/get_modele/{id}', name: 'api_get_modele', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, ModeleRepository $modeleRepository): JsonResponse
	{
		$modele = $modeleRepository->find($id);
		if (!$modele) {
			return $this->json(['message' => 'Modele introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatModele($modele));
	}

	// methode pour recuperer les marques associees a un modele
	#[Route('/api/v1/get_marques_modele/{id}', name: 'api_get_marques_modele', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function getMarquesByModele(int $id, ModeleRepository $modeleRepository): JsonResponse
	{
		$modele = $modeleRepository->find($id);
		if (!$modele) {
			return $this->json(['message' => 'Modele introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$marques = array_map(
			static fn (Marque $marque): array => [
				'id_marque' => $marque->getIdMarque(),
				'nom_marque' => $marque->getNomMarque(),
			],
			$modele->getMarques()->toArray()
		);

		return $this->json([
			'id_modele' => $modele->getIdModele(),
			'nom_modele' => $modele->getNomModele(),
			'marques' => $marques,
		]);
	}

	// methode pour recuperer le modele a partir d'une marque
	#[Route('/api/v1/get_modele_by_marque/{id}', name: 'api_get_modele_by_marque', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function getModeleByMarque(int $id, MarqueRepository $marqueRepository): JsonResponse
	{
		$marque = $marqueRepository->find($id);
		if (!$marque) {
			return $this->json(['message' => 'Marque introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$modeles = array_map(
			fn (Modele $modele): array => $this->formatModele($modele),
			$marque->getModeles()->toArray()
		);

		return $this->json([
			'id_marque' => $marque->getIdMarque(),
			'nom_marque' => $marque->getNomMarque(),
			'modele' => $modeles[0] ?? null,
			'modeles' => array_values($modeles),
		]);
	}

	// methode pour ajouter un modele
	#[Route('/api/v1/new_modele', name: 'api_new_modele', methods: ['POST'])]
	public function create(Request $request, MarqueRepository $marqueRepository, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomModele = trim((string) ($data['nom_modele'] ?? $data['nomModele'] ?? ''));
		$idMarque = (int) ($data['id_marque'] ?? $data['idMarque'] ?? 0);
		if ($nomModele === '' || $idMarque <= 0) {
			return $this->json(['message' => 'Le nom du modele et id_marque sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$marque = $marqueRepository->find($idMarque);
		if (!$marque) {
			return $this->json(['message' => 'Marque introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$modele = new Modele();
		$modele->setNomModele($nomModele);
		$modele->setMarque($marque);

		$entityManager->persist($modele);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le modele a ete ajoute avec succes.',
			'modele' => $this->formatModele($modele),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier un modele
	#[Route('/api/v1/edit_modele/{id}', name: 'api_edit_modele', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(int $id, Request $request, ModeleRepository $modeleRepository, MarqueRepository $marqueRepository, EntityManagerInterface $entityManager): JsonResponse
	{
		$modele = $modeleRepository->find($id);
		if (!$modele) {
			return $this->json(['message' => 'Modele introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomModele = trim((string) ($data['nom_modele'] ?? $data['nomModele'] ?? $modele->getNomModele()));
		if ($nomModele === '') {
			return $this->json(['message' => 'Le nom du modele est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$modele->setNomModele($nomModele);

		if (isset($data['id_marque']) || isset($data['idMarque'])) {
			$idMarque = (int) ($data['id_marque'] ?? $data['idMarque']);
			if ($idMarque <= 0) {
				return $this->json(['message' => 'id_marque invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$marque = $marqueRepository->find($idMarque);
			if (!$marque) {
				return $this->json(['message' => 'Marque introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}

			$modele->setMarque($marque);
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'Le modele a ete modifie avec succes.',
			'modele' => $this->formatModele($modele),
		]);
	}

	// methode pour supprimer un modele
	#[Route('/api/v1/delete_modele/{id}', name: 'api_delete_modele', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Modele $modele, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($modele);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le modele a ete supprime avec succes.',
		]);
	}

	private function formatModele(Modele $modele): array
	{
		return [
			'id_modele' => $modele->getIdModele(),
			'nom_modele' => $modele->getNomModele(),
			'marque' => [
				'id_marque' => $modele->getMarque()?->getIdMarque(),
				'nom_marque' => $modele->getMarque()?->getNomMarque(),
			],
		];
	}
}
