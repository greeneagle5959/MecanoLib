<?php

namespace App\Controller;

use App\Entity\Valeur;
use App\Repository\GarageRepository;
use App\Repository\ReseauSociauxRepository;
use App\Repository\ValeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ValeurController extends AbstractController
{
	// methode pour recuperer toutes les valeurs
	#[Route('/api/v1/get_valeurs', name: 'api_get_valeurs', methods: ['GET'])]
	public function getAll(ValeurRepository $valeurRepository): JsonResponse
	{
		$valeurs = $valeurRepository->findAll();

		$result = array_map(
			fn (Valeur $valeur): array => $this->formatValeur($valeur),
			$valeurs
		);

		return $this->json($result);
	}

	// methode pour recuperer les valeurs d'un garage
	#[Route('/api/v1/get_valeurs_by_garage/{idGarage}', name: 'api_get_valeurs_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
	public function getByGarage(int $idGarage, GarageRepository $garageRepository, ValeurRepository $valeurRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$valeurs = $valeurRepository->findBy(['garage' => $garage]);
		$result = array_map(
			fn (Valeur $valeur): array => $this->formatValeur($valeur),
			$valeurs
		);

		return $this->json($result);
	}

	// methode pour recuperer les valeurs d'un reseau social
	#[Route('/api/v1/get_valeurs_by_reseau/{idReseau}', name: 'api_get_valeurs_by_reseau', methods: ['GET'], requirements: ['idReseau' => '\\d+'])]
	public function getByReseau(int $idReseau, ReseauSociauxRepository $reseauSociauxRepository, ValeurRepository $valeurRepository): JsonResponse
	{
		$reseau = $reseauSociauxRepository->find($idReseau);
		if (!$reseau) {
			return $this->json(['message' => 'Reseau social introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$valeurs = $valeurRepository->findBy(['reseau' => $reseau]);
		$result = array_map(
			fn (Valeur $valeur): array => $this->formatValeur($valeur),
			$valeurs
		);

		return $this->json($result);
	}

	// methode pour recuperer une valeur
	#[Route('/api/v1/get_valeur/{id}', name: 'api_get_valeur', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, ValeurRepository $valeurRepository): JsonResponse
	{
		$valeur = $valeurRepository->find($id);
		if (!$valeur) {
			return $this->json(['message' => 'Valeur introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatValeur($valeur));
	}

	// methode pour ajouter une valeur
	#[Route('/api/v1/new_valeur', name: 'api_new_valeur', methods: ['POST'])]
	public function create(
		Request $request,
		GarageRepository $garageRepository,
		ReseauSociauxRepository $reseauSociauxRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$libValeur = trim((string) ($data['lib_valeur'] ?? $data['libValeur'] ?? ''));
		$qrcode = array_key_exists('qrcode', $data) ? (string) $data['qrcode'] : null;
		$idGarage = (int) ($data['id_garage'] ?? $data['idGarage'] ?? 0);
		$idReseau = (int) ($data['id_reseau'] ?? $data['idReseau'] ?? 0);

		if ($libValeur === '' || $idGarage <= 0 || $idReseau <= 0) {
			return $this->json(['message' => 'lib_valeur, id_garage et id_reseau sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$garage = $garageRepository->find($idGarage);
		$reseau = $reseauSociauxRepository->find($idReseau);

		if (!$garage || !$reseau) {
			return $this->json(['message' => 'Garage ou reseau social introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$valeur = new Valeur();
		$valeur->setLibValeur($libValeur);
		$valeur->setQrcode($qrcode !== '' ? $qrcode : null);
		$valeur->setGarage($garage);
		$valeur->setReseau($reseau);

		$entityManager->persist($valeur);
		$entityManager->flush();

		return $this->json([
			'message' => 'La valeur a ete ajoutee avec succes.',
			'valeur' => $this->formatValeur($valeur),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier une valeur
	#[Route('/api/v1/edit_valeur/{id}', name: 'api_edit_valeur', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		ValeurRepository $valeurRepository,
		GarageRepository $garageRepository,
		ReseauSociauxRepository $reseauSociauxRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$valeur = $valeurRepository->find($id);
		if (!$valeur) {
			return $this->json(['message' => 'Valeur introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (isset($data['lib_valeur']) || isset($data['libValeur'])) {
			$libValeur = trim((string) ($data['lib_valeur'] ?? $data['libValeur']));
			if ($libValeur === '') {
				return $this->json(['message' => 'lib_valeur ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$valeur->setLibValeur($libValeur);
		}

		if (array_key_exists('qrcode', $data)) {
			$qrcode = (string) $data['qrcode'];
			$valeur->setQrcode($qrcode !== '' ? $qrcode : null);
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
			$valeur->setGarage($garage);
		}

		if (isset($data['id_reseau']) || isset($data['idReseau'])) {
			$idReseau = (int) ($data['id_reseau'] ?? $data['idReseau']);
			if ($idReseau <= 0) {
				return $this->json(['message' => 'id_reseau invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$reseau = $reseauSociauxRepository->find($idReseau);
			if (!$reseau) {
				return $this->json(['message' => 'Reseau social introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}
			$valeur->setReseau($reseau);
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'La valeur a ete modifiee avec succes.',
			'valeur' => $this->formatValeur($valeur),
		]);
	}

	// methode pour supprimer une valeur
	#[Route('/api/v1/delete_valeur/{id}', name: 'api_delete_valeur', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Valeur $valeur, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($valeur);
		$entityManager->flush();

		return $this->json([
			'message' => 'La valeur a ete supprimee avec succes.',
		]);
	}

	private function formatValeur(Valeur $valeur): array
	{
		return [
			'id_valeur' => $valeur->getIdValeur(),
			'lib_valeur' => $valeur->getLibValeur(),
			'qrcode' => $valeur->getQrcode(),
			'garage' => [
				'id_garage' => $valeur->getGarage()?->getIdGarage(),
				'nom_garage' => $valeur->getGarage()?->getNomGarage(),
			],
			'reseau' => [
				'id_reseau' => $valeur->getReseau()?->getIdReseau(),
				'nom_reseaux' => $valeur->getReseau()?->getNomReseaux(),
			],
		];
	}
}
