<?php

namespace App\Controller;

use App\Entity\Proposer;
use App\Repository\GarageRepository;
use App\Repository\PrestationRepository;
use App\Repository\ProposerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ProposerController extends AbstractController
{
    // ProposerController gère les associations entre garages et prestations, avec des prix spécifiques.
	#[Route('/api/v1/get_propositions', name: 'api_get_propositions', methods: ['GET'])]
	public function getAllPropositions(ProposerRepository $proposerRepository): JsonResponse
	{
		$propositions = $proposerRepository->findAll();

		$data = array_map(fn (Proposer $proposer): array => $this->formatProposer($proposer), $propositions);

		return $this->json($data);
	}
// méthode pour récupérer les propositions d'un garage spécifique
	#[Route('/api/v1/get_propositions_by_garage/{idGarage}', name: 'api_get_propositions_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
	public function getPropositionsByGarage(int $idGarage, GarageRepository $garageRepository, ProposerRepository $proposerRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}
// Récupère toutes les propositions associées à ce garage
		$propositions = $proposerRepository->findBy(['garage' => $garage]);
		$data = array_map(fn (Proposer $proposer): array => $this->formatProposer($proposer), $propositions);

		return $this->json($data);
	}
// méthode pour récupérer les propositions d'une prestation spécifique
	#[Route('/api/v1/get_proposition/{idGarage}/{idPrestation}', name: 'api_get_proposition', methods: ['GET'], requirements: ['idGarage' => '\\d+', 'idPrestation' => '\\d+'])]
	public function getProposition(int $idGarage, int $idPrestation, GarageRepository $garageRepository, PrestationRepository $prestationRepository, ProposerRepository $proposerRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		$prestation = $prestationRepository->find($idPrestation);

		if (!$garage || !$prestation) {
			return $this->json(['message' => 'Garage ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$proposer = $proposerRepository->findOneBy([
			'garage' => $garage,
			'prestation' => $prestation,
		]);

		if (!$proposer) {
			return $this->json(['message' => 'Association introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatProposer($proposer));
	}
// méthode pour ajouter une nouvelle proposition ou mettre à jour le prix si l'association existe déjà
	#[Route('/api/v1/new_proposition', name: 'api_new_proposition', methods: ['POST'])]
	public function newProposition(
		Request $request,
		GarageRepository $garageRepository,
		PrestationRepository $prestationRepository,
		ProposerRepository $proposerRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$idGarage = (int) ($data['id_garage'] ?? $data['idGarage'] ?? 0);
		$idPrestation = (int) ($data['id_prestation'] ?? $data['idPrestation'] ?? 0);
		$prix = isset($data['prix']) ? (float) $data['prix'] : null;

		if ($idGarage <= 0 || $idPrestation <= 0) {
			return $this->json(['message' => 'id_garage et id_prestation sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$garage = $garageRepository->find($idGarage);
		$prestation = $prestationRepository->find($idPrestation);

		if (!$garage || !$prestation) {
			return $this->json(['message' => 'Garage ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$existing = $proposerRepository->findOneBy([
			'garage' => $garage,
			'prestation' => $prestation,
		]);

		if ($existing) {
			if ($prix !== null) {
				$existing->setPrix($prix);
				$entityManager->flush();
			}

			return $this->json([
				'message' => 'La proposition existait deja, prix mis a jour.',
				'proposition' => $this->formatProposer($existing),
			]);
		}

		$proposer = new Proposer();
		$proposer->setGarage($garage);
		$proposer->setPrestation($prestation);
		$proposer->setPrix($prix);

		$entityManager->persist($proposer);
		$entityManager->flush();

		return $this->json([
			'message' => 'La proposition a ete ajoutee avec succes.',
			'proposition' => $this->formatProposer($proposer),
		], JsonResponse::HTTP_CREATED);
	}
// méthode pour modifier une proposition existante
	#[Route('/api/v1/edit_proposition/{idGarage}/{idPrestation}', name: 'api_edit_proposition', methods: ['POST'], requirements: ['idGarage' => '\\d+', 'idPrestation' => '\\d+'])]
	public function editProposition(
		int $idGarage,
		int $idPrestation,
		Request $request,
		GarageRepository $garageRepository,
		PrestationRepository $prestationRepository,
		ProposerRepository $proposerRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (!array_key_exists('prix', $data)) {
			return $this->json(['message' => 'Le champ prix est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$garage = $garageRepository->find($idGarage);
		$prestation = $prestationRepository->find($idPrestation);

		if (!$garage || !$prestation) {
			return $this->json(['message' => 'Garage ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$proposer = $proposerRepository->findOneBy([
			'garage' => $garage,
			'prestation' => $prestation,
		]);

		if (!$proposer) {
			return $this->json(['message' => 'Association introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$proposer->setPrix((float) $data['prix']);
		$entityManager->flush();

		return $this->json([
			'message' => 'La proposition a ete modifiee avec succes.',
			'proposition' => $this->formatProposer($proposer),
		]);
	}
// méthode pour supprimer une proposition
	#[Route('/api/v1/delete_proposition/{idGarage}/{idPrestation}', name: 'api_delete_proposition', methods: ['DELETE'], requirements: ['idGarage' => '\\d+', 'idPrestation' => '\\d+'])]
	public function deleteProposition(
		int $idGarage,
		int $idPrestation,
		GarageRepository $garageRepository,
		PrestationRepository $prestationRepository,
		ProposerRepository $proposerRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$garage = $garageRepository->find($idGarage);
		$prestation = $prestationRepository->find($idPrestation);

		if (!$garage || !$prestation) {
			return $this->json(['message' => 'Garage ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$proposer = $proposerRepository->findOneBy([
			'garage' => $garage,
			'prestation' => $prestation,
		]);

		if (!$proposer) {
			return $this->json(['message' => 'Association introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$entityManager->remove($proposer);
		$entityManager->flush();

		return $this->json(['message' => 'La proposition a ete supprimee avec succes.']);
	}

	private function formatProposer(Proposer $proposer): array
	{
		return [
			'id_garage' => $proposer->getGarage()->getIdGarage(),
			'id_prestation' => $proposer->getPrestation()->getIdPrestation(),
			'nom_prestation' => $proposer->getPrestation()->getNomPrestation(),
			'prix' => $proposer->getPrix(),
		];
	}
}
