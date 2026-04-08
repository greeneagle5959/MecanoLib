<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Repository\ClientRepository;
use App\Repository\GarageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class AvisController extends AbstractController
{
	// methode pour recuperer tous les avis
	#[Route('/api/v1/get_avis', name: 'api_get_avis', methods: ['GET'])]
	public function getAll(AvisRepository $avisRepository): JsonResponse
	{
		$avisList = $avisRepository->findAll();

		$result = array_map(
			fn (Avis $avis): array => $this->formatAvis($avis),
			$avisList
		);

		return $this->json($result);
	}

	// methode pour recuperer les avis d'un garage
	#[Route('/api/v1/get_avis_by_garage/{idGarage}', name: 'api_get_avis_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
	public function getByGarage(int $idGarage, GarageRepository $garageRepository, AvisRepository $avisRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$avisList = $avisRepository->findBy(['garage' => $garage]);
		$result = array_map(
			fn (Avis $avis): array => $this->formatAvis($avis),
			$avisList
		);

		return $this->json($result);
	}

	// methode pour recuperer les avis d'un client
	#[Route('/api/v1/get_avis_by_client/{idClient}', name: 'api_get_avis_by_client', methods: ['GET'], requirements: ['idClient' => '\\d+'])]
	public function getByClient(int $idClient, ClientRepository $clientRepository, AvisRepository $avisRepository): JsonResponse
	{
		$client = $clientRepository->find($idClient);
		if (!$client) {
			return $this->json(['message' => 'Client introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$avisList = $avisRepository->findBy(['client' => $client]);
		$result = array_map(
			fn (Avis $avis): array => $this->formatAvis($avis),
			$avisList
		);

		return $this->json($result);
	}

	// methode pour recuperer un avis
	#[Route('/api/v1/get_un_avis/{id}', name: 'api_get_un_avis', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, AvisRepository $avisRepository): JsonResponse
	{
		$avis = $avisRepository->find($id);
		if (!$avis) {
			return $this->json(['message' => 'Avis introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatAvis($avis));
	}

	// methode pour ajouter un avis
	#[Route('/api/v1/new_avis', name: 'api_new_avis', methods: ['POST'])]
	public function create(
		Request $request,
		GarageRepository $garageRepository,
		ClientRepository $clientRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$note = isset($data['note']) ? (int) $data['note'] : -1;
		$commentaire = trim((string) ($data['commentaire'] ?? ''));
		$idGarage = (int) ($data['id_garage'] ?? $data['idGarage'] ?? 0);
		$idClient = (int) ($data['id_client'] ?? $data['idClient'] ?? 0);

		if ($note < 0 || $commentaire === '' || $idGarage <= 0 || $idClient <= 0) {
			return $this->json(['message' => 'note, commentaire, id_garage et id_client sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if ($note < 0 || $note > 5) {
			return $this->json(['message' => 'La note doit etre comprise entre 0 et 5.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$garage = $garageRepository->find($idGarage);
		$client = $clientRepository->find($idClient);

		if (!$garage || !$client) {
			return $this->json(['message' => 'Garage ou client introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$avis = new Avis();
		$avis->setNote($note);
		$avis->setCommentaire($commentaire);
		$avis->setDatePublication(new \DateTime());
		$avis->setGarage($garage);
		$avis->setClient($client);

		$entityManager->persist($avis);
		$entityManager->flush();

		return $this->json([
			'message' => 'L avis a ete ajoute avec succes.',
			'avis' => $this->formatAvis($avis),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier un avis
	#[Route('/api/v1/edit_avis/{id}', name: 'api_edit_avis', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		AvisRepository $avisRepository,
		GarageRepository $garageRepository,
		ClientRepository $clientRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$avis = $avisRepository->find($id);
		if (!$avis) {
			return $this->json(['message' => 'Avis introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (array_key_exists('note', $data)) {
			$note = (int) $data['note'];
			if ($note < 0 || $note > 5) {
				return $this->json(['message' => 'La note doit etre comprise entre 0 et 5.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$avis->setNote($note);
		}

		if (isset($data['commentaire'])) {
			$commentaire = trim((string) $data['commentaire']);
			if ($commentaire === '') {
				return $this->json(['message' => 'commentaire ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$avis->setCommentaire($commentaire);
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

			$avis->setGarage($garage);
		}

		if (isset($data['id_client']) || isset($data['idClient'])) {
			$idClient = (int) ($data['id_client'] ?? $data['idClient']);
			if ($idClient <= 0) {
				return $this->json(['message' => 'id_client invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$client = $clientRepository->find($idClient);
			if (!$client) {
				return $this->json(['message' => 'Client introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}

			$avis->setClient($client);
		}

		if (isset($data['date_publication']) || isset($data['datePublication'])) {
			$rawDate = (string) ($data['date_publication'] ?? $data['datePublication']);
			try {
				$date = new \DateTime($rawDate);
				$avis->setDatePublication($date);
			} catch (\Throwable) {
				return $this->json(['message' => 'date_publication invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'L avis a ete modifie avec succes.',
			'avis' => $this->formatAvis($avis),
		]);
	}

	// methode pour supprimer un avis
	#[Route('/api/v1/delete_avis/{id}', name: 'api_delete_avis', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Avis $avis, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($avis);
		$entityManager->flush();

		return $this->json([
			'message' => 'L avis a ete supprime avec succes.',
		]);
	}

	private function formatAvis(Avis $avis): array
	{
		return [
			'id_avis' => $avis->getIdAvis(),
			'note' => $avis->getNote(),
			'commentaire' => $avis->getCommentaire(),
			'date_publication' => $avis->getDatePublication()->format('Y-m-d H:i:s'),
			'garage' => [
				'id_garage' => $avis->getGarage()?->getIdGarage(),
				'nom_garage' => $avis->getGarage()?->getNomGarage(),
			],
			'client' => [
				'id_client' => $avis->getClient()?->getIdClient(),
				'nom_client' => $avis->getClient()?->getNomClient(),
				'prenom_client' => $avis->getClient()?->getPrenomClient(),
			],
		];
	}
}
