<?php

namespace App\Controller;

use App\Entity\ReseauSociaux;
use App\Entity\Valeur;
use App\Repository\GarageRepository;
use App\Repository\ReseauSociauxRepository;
use App\Repository\ValeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ReseauSociauxController extends AbstractController
{
	// methode pour recuperer tous les reseaux sociaux
	#[Route('/api/v1/get_reseaux_sociaux', name: 'api_get_reseaux_sociaux', methods: ['GET'])]
	public function getAll(ReseauSociauxRepository $reseauSociauxRepository): JsonResponse
	{
		$reseaux = $reseauSociauxRepository->findAll();

		$result = array_map(
			fn (ReseauSociaux $reseau): array => $this->formatReseau($reseau),
			$reseaux
		);

		return $this->json($result);
	}

	// methode pour recuperer les reseaux utilises par un garage
	#[Route('/api/v1/get_reseaux_by_garage/{idGarage}', name: 'api_get_reseaux_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
	public function getByGarage(
		int $idGarage,
		GarageRepository $garageRepository,
		ValeurRepository $valeurRepository
	): JsonResponse {
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$valeurs = $valeurRepository->findBy(['garage' => $garage]);

		$reseaux = array_map(
			static fn (Valeur $valeur): array => [
				'id_reseau' => $valeur->getReseau()?->getIdReseau(),
				'nom_reseaux' => $valeur->getReseau()?->getNomReseaux(),
				'lib_valeur' => $valeur->getLibValeur(),
				'qrcode' => $valeur->getQrcode(),
			],
			$valeurs
		);

		return $this->json($reseaux);
	}

	// methode pour recuperer un reseau social
	#[Route('/api/v1/get_reseau_social/{id}', name: 'api_get_reseau_social', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, ReseauSociauxRepository $reseauSociauxRepository): JsonResponse
	{
		$reseau = $reseauSociauxRepository->find($id);
		if (!$reseau) {
			return $this->json(['message' => 'Reseau social introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatReseau($reseau));
	}

	// methode pour ajouter un reseau social
	#[Route('/api/v1/new_reseau_social', name: 'api_new_reseau_social', methods: ['POST'])]
	public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomReseaux = trim((string) ($data['nom_reseaux'] ?? $data['nomReseaux'] ?? ''));
		if ($nomReseaux === '') {
			return $this->json(['message' => 'Le nom du reseau est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$reseau = new ReseauSociaux();
		$reseau->setNomReseaux($nomReseaux);

		$entityManager->persist($reseau);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le reseau social a ete ajoute avec succes.',
			'reseau' => $this->formatReseau($reseau),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier un reseau social
	#[Route('/api/v1/edit_reseau_social/{id}', name: 'api_edit_reseau_social', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		ReseauSociauxRepository $reseauSociauxRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$reseau = $reseauSociauxRepository->find($id);
		if (!$reseau) {
			return $this->json(['message' => 'Reseau social introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomReseaux = trim((string) ($data['nom_reseaux'] ?? $data['nomReseaux'] ?? ''));
		if ($nomReseaux === '') {
			return $this->json(['message' => 'Le nom du reseau est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$reseau->setNomReseaux($nomReseaux);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le reseau social a ete modifie avec succes.',
			'reseau' => $this->formatReseau($reseau),
		]);
	}

	// methode pour supprimer un reseau social
	#[Route('/api/v1/delete_reseau_social/{id}', name: 'api_delete_reseau_social', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(ReseauSociaux $reseau, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($reseau);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le reseau social a ete supprime avec succes.',
		]);
	}

	private function formatReseau(ReseauSociaux $reseau): array
	{
		return [
			'id_reseau' => $reseau->getIdReseau(),
			'nom_reseaux' => $reseau->getNomReseaux(),
		];
	}
}
