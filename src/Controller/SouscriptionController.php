<?php

namespace App\Controller;

use App\Entity\Souscription;
use App\Repository\AbonnementRepository;
use App\Repository\GarageRepository;
use App\Repository\SouscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class SouscriptionController extends AbstractController
{
	// methode pour recuperer toutes les souscriptions
	#[Route('/api/v1/get_souscriptions', name: 'api_get_souscriptions', methods: ['GET'])]
	public function getAll(SouscriptionRepository $souscriptionRepository): JsonResponse
	{
		$souscriptions = $souscriptionRepository->findAll();

		$result = array_map(
			fn (Souscription $souscription): array => $this->formatSouscription($souscription),
			$souscriptions
		);

		return $this->json($result);
	}

	// methode pour recuperer les souscriptions d'un garage
	#[Route('/api/v1/get_souscriptions_by_garage/{idGarage}', name: 'api_get_souscriptions_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
	public function getByGarage(int $idGarage, GarageRepository $garageRepository, SouscriptionRepository $souscriptionRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$souscriptions = $souscriptionRepository->findBy(['garage' => $garage]);
		$result = array_map(
			fn (Souscription $souscription): array => $this->formatSouscription($souscription),
			$souscriptions
		);

		return $this->json($result);
	}

	// methode pour recuperer les souscriptions d'un abonnement
	#[Route('/api/v1/get_souscriptions_by_abonnement/{idAbonnement}', name: 'api_get_souscriptions_by_abonnement', methods: ['GET'], requirements: ['idAbonnement' => '\\d+'])]
	public function getByAbonnement(int $idAbonnement, AbonnementRepository $abonnementRepository, SouscriptionRepository $souscriptionRepository): JsonResponse
	{
		$abonnement = $abonnementRepository->find($idAbonnement);
		if (!$abonnement) {
			return $this->json(['message' => 'Abonnement introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$souscriptions = $souscriptionRepository->findBy(['abonnement' => $abonnement]);
		$result = array_map(
			fn (Souscription $souscription): array => $this->formatSouscription($souscription),
			$souscriptions
		);

		return $this->json($result);
	}

	// methode pour recuperer une souscription
	#[Route('/api/v1/get_souscription/{id}', name: 'api_get_souscription', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, SouscriptionRepository $souscriptionRepository): JsonResponse
	{
		$souscription = $souscriptionRepository->find($id);
		if (!$souscription) {
			return $this->json(['message' => 'Souscription introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatSouscription($souscription));
	}

	// methode pour ajouter une souscription
	#[Route('/api/v1/new_souscription', name: 'api_new_souscription', methods: ['POST'])]
	public function create(
		Request $request,
		GarageRepository $garageRepository,
		AbonnementRepository $abonnementRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$dateDebutRaw = trim((string) ($data['date_debut'] ?? $data['dateDebut'] ?? ''));
		$dateFinRaw = trim((string) ($data['date_fin'] ?? $data['dateFin'] ?? ''));
		$prix = trim((string) ($data['prix'] ?? ''));
		$status = $data['status'] ?? null;
		$idGarage = (int) ($data['id_garage'] ?? $data['idGarage'] ?? 0);
		$idAbonnement = (int) ($data['id_abonnement'] ?? $data['idAbonnement'] ?? 0);

		if ($dateDebutRaw === '' || $dateFinRaw === '' || $prix === '' || !is_bool($status) || $idGarage <= 0 || $idAbonnement <= 0) {
			return $this->json(['message' => 'date_debut, date_fin, prix, status, id_garage et id_abonnement sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		try {
			$dateDebut = new \DateTime($dateDebutRaw);
			$dateFin = new \DateTime($dateFinRaw);
		} catch (\Throwable) {
			return $this->json(['message' => 'Format de date invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (!is_numeric($prix)) {
			return $this->json(['message' => 'prix invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$garage = $garageRepository->find($idGarage);
		$abonnement = $abonnementRepository->find($idAbonnement);

		if (!$garage || !$abonnement) {
			return $this->json(['message' => 'Garage ou abonnement introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$souscription = new Souscription();
		$souscription->setDateDebut($dateDebut);
		$souscription->setDateFin($dateFin);
		$souscription->setPrix($prix);
		$souscription->setStatus($status);
		$souscription->setGarage($garage);
		$souscription->setAbonnement($abonnement);

		$entityManager->persist($souscription);
		$entityManager->flush();

		return $this->json([
			'message' => 'La souscription a ete ajoutee avec succes.',
			'souscription' => $this->formatSouscription($souscription),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier une souscription
	#[Route('/api/v1/edit_souscription/{id}', name: 'api_edit_souscription', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		SouscriptionRepository $souscriptionRepository,
		GarageRepository $garageRepository,
		AbonnementRepository $abonnementRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$souscription = $souscriptionRepository->find($id);
		if (!$souscription) {
			return $this->json(['message' => 'Souscription introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (isset($data['date_debut']) || isset($data['dateDebut'])) {
			$raw = (string) ($data['date_debut'] ?? $data['dateDebut']);
			try {
				$souscription->setDateDebut(new \DateTime($raw));
			} catch (\Throwable) {
				return $this->json(['message' => 'date_debut invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
		}

		if (isset($data['date_fin']) || isset($data['dateFin'])) {
			$raw = (string) ($data['date_fin'] ?? $data['dateFin']);
			try {
				$souscription->setDateFin(new \DateTime($raw));
			} catch (\Throwable) {
				return $this->json(['message' => 'date_fin invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
		}

		if (isset($data['prix'])) {
			$prix = trim((string) $data['prix']);
			if ($prix === '' || !is_numeric($prix)) {
				return $this->json(['message' => 'prix invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$souscription->setPrix($prix);
		}

		if (array_key_exists('status', $data)) {
			if (!is_bool($data['status'])) {
				return $this->json(['message' => 'status doit etre un booleen.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$souscription->setStatus($data['status']);
		}

		if (isset($data['id_garage']) || isset($data['idGarage'])) {
			$idGarage = (int) ($data['id_garage'] ?? $data['idGarage']);
			if ($idGarage <= 0) {
				return $this->json(['message' => 'id_garage invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$garage = $garageRepository->find($idGarage);
			if (!$garage) {
				return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}
			$souscription->setGarage($garage);
		}

		if (isset($data['id_abonnement']) || isset($data['idAbonnement'])) {
			$idAbonnement = (int) ($data['id_abonnement'] ?? $data['idAbonnement']);
			if ($idAbonnement <= 0) {
				return $this->json(['message' => 'id_abonnement invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$abonnement = $abonnementRepository->find($idAbonnement);
			if (!$abonnement) {
				return $this->json(['message' => 'Abonnement introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}
			$souscription->setAbonnement($abonnement);
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'La souscription a ete modifiee avec succes.',
			'souscription' => $this->formatSouscription($souscription),
		]);
	}

	// methode pour supprimer une souscription
	#[Route('/api/v1/delete_souscription/{id}', name: 'api_delete_souscription', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Souscription $souscription, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($souscription);
		$entityManager->flush();

		return $this->json([
			'message' => 'La souscription a ete supprimee avec succes.',
		]);
	}

	private function formatSouscription(Souscription $souscription): array
	{
		return [
			'id_souscription' => $souscription->getIdSouscription(),
			'date_debut' => $souscription->getDateDebut()->format('Y-m-d H:i:s'),
			'date_fin' => $souscription->getDateFin()->format('Y-m-d H:i:s'),
			'prix' => $souscription->getPrix(),
			'status' => $souscription->getStatus(),
			'garage' => [
				'id_garage' => $souscription->getGarage()?->getIdGarage(),
				'nom_garage' => $souscription->getGarage()?->getNomGarage(),
			],
			'abonnement' => [
				'id_abonnement' => $souscription->getAbonnement()?->getIdAbonnement(),
				'lib_abonnement' => $souscription->getAbonnement()?->getLibAbonnement(),
				'tarif_abonnement' => $souscription->getAbonnement()?->getTarifAbonnement(),
			],
		];
	}
}
