<?php

namespace App\Controller;

use App\Entity\Jour;
use App\Repository\JourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class JourController extends AbstractController
{
	// méthode pour récupérer tous les jours
	#[Route('/api/v1/get_jours', name: 'api_get_jours', methods: ['GET'])]
	public function getAllJours(JourRepository $jourRepository): JsonResponse
	{
		$jours = $jourRepository->findAll();

		$result = array_map(
			static fn (Jour $jour): array => [
				'id_jour' => $jour->getIdJour(),
				'lib_jour' => $jour->getLibJour(),
			],
			$jours
		);

		return $this->json($result);
	}

	// méthode pour récupérer le détail d'un jour
	#[Route('/api/v1/get_jour/{id}', name: 'api_get_jour', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, JourRepository $jourRepository): JsonResponse
	{
		$jour = $jourRepository->find($id);
		if (!$jour) {
			return $this->json(['message' => 'Jour introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json([
			'id_jour' => $jour->getIdJour(),
			'lib_jour' => $jour->getLibJour(),
		]);
	}

	// méthode pour initialiser automatiquement les 7 jours de la semaine
	#[Route('/api/v1/init_jours_semaine', name: 'api_init_jours_semaine', methods: ['POST'])]
	public function initJoursSemaine(JourRepository $jourRepository, EntityManagerInterface $entityManager): JsonResponse
	{
		$joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
		$created = [];
		$existing = [];

		foreach ($joursSemaine as $libJour) {
			$jour = $jourRepository->findOneBy(['libJour' => $libJour]);

			if ($jour) {
				$existing[] = $libJour;
				continue;
			}

			$jour = new Jour();
			$jour->setLibJour($libJour);
			$entityManager->persist($jour);
			$created[] = $libJour;
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'Initialisation des jours terminee.',
			'created' => $created,
			'existing' => $existing,
		]);
	}

	// méthode pour ajouter un jour
	#[Route('/api/v1/new_jour', name: 'api_new_jour', methods: ['POST'])]
	public function newJour(Request $request, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$libJour = trim((string) ($data['lib_jour'] ?? $data['libjour'] ?? ''));
		if ($libJour === '') {
			return $this->json(['message' => 'Le libelle du jour est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$jour = new Jour();
		$jour->setLibJour($libJour);

		$entityManager->persist($jour);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le jour a ete ajoute avec succes.',
			'id_jour' => $jour->getIdJour(),
		], JsonResponse::HTTP_CREATED);
	}

	// méthode pour modifier un jour
	#[Route('/api/v1/edit_jour/{id}', name: 'api_edit_jour', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(Request $request, Jour $jour, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$libJour = trim((string) ($data['lib_jour'] ?? $data['libjour'] ?? ''));
		if ($libJour === '') {
			return $this->json(['message' => 'Le libelle du jour est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$jour->setLibJour($libJour);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le jour a ete modifie avec succes.',
		]);
	}

	// méthode pour supprimer un jour
	#[Route('/api/v1/delete_jour/{id}', name: 'api_delete_jour', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Jour $jour, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($jour);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le jour a ete supprime avec succes.',
		]);
	}
}
