<?php

namespace App\Controller;

use App\Entity\Lier;
use App\Repository\LierRepository;
use App\Repository\PrestationRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class LierController extends AbstractController
{
	// methode pour recuperer tous les liens prestation/rdv
	#[Route('/api/v1/get_liaisons', name: 'api_get_liaisons', methods: ['GET'])]
	public function getAll(LierRepository $lierRepository): JsonResponse
	{
		$liaisons = $lierRepository->findAll();

		$result = array_map(
			fn (Lier $lier): array => $this->formatLiaison($lier),
			$liaisons
		);

		return $this->json($result);
	}

	// methode pour recuperer les liaisons d'un rendez-vous
	#[Route('/api/v1/get_liaisons_by_rdv/{idRdv}', name: 'api_get_liaisons_by_rdv', methods: ['GET'], requirements: ['idRdv' => '\\d+'])]
	public function getByRdv(int $idRdv, RendezVousRepository $rendezVousRepository, LierRepository $lierRepository): JsonResponse
	{
		$rdv = $rendezVousRepository->find($idRdv);
		if (!$rdv) {
			return $this->json(['message' => 'Rendez-vous introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$liaisons = $lierRepository->findBy(['rdv' => $rdv]);
		$result = array_map(
			fn (Lier $lier): array => $this->formatLiaison($lier),
			$liaisons
		);

		return $this->json($result);
	}

	// methode pour recuperer les liaisons d'une prestation
	#[Route('/api/v1/get_liaisons_by_prestation/{idPrestation}', name: 'api_get_liaisons_by_prestation', methods: ['GET'], requirements: ['idPrestation' => '\\d+'])]
	public function getByPrestation(int $idPrestation, PrestationRepository $prestationRepository, LierRepository $lierRepository): JsonResponse
	{
		$prestation = $prestationRepository->find($idPrestation);
		if (!$prestation) {
			return $this->json(['message' => 'Prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$liaisons = $lierRepository->findBy(['prestation' => $prestation]);
		$result = array_map(
			fn (Lier $lier): array => $this->formatLiaison($lier),
			$liaisons
		);

		return $this->json($result);
	}

	// methode pour recuperer une liaison specifique
	#[Route('/api/v1/get_liaison/{idRdv}/{idPrestation}', name: 'api_get_liaison', methods: ['GET'], requirements: ['idRdv' => '\\d+', 'idPrestation' => '\\d+'])]
	public function show(
		int $idRdv,
		int $idPrestation,
		RendezVousRepository $rendezVousRepository,
		PrestationRepository $prestationRepository,
		LierRepository $lierRepository
	): JsonResponse {
		$rdv = $rendezVousRepository->find($idRdv);
		$prestation = $prestationRepository->find($idPrestation);

		if (!$rdv || !$prestation) {
			return $this->json(['message' => 'Rendez-vous ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$liaison = $lierRepository->findOneBy([
			'rdv' => $rdv,
			'prestation' => $prestation,
		]);

		if (!$liaison) {
			return $this->json(['message' => 'Liaison introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatLiaison($liaison));
	}

	// methode pour creer une liaison
	#[Route('/api/v1/new_liaison', name: 'api_new_liaison', methods: ['POST'])]
	public function create(
		Request $request,
		RendezVousRepository $rendezVousRepository,
		PrestationRepository $prestationRepository,
		LierRepository $lierRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$idRdv = (int) ($data['id_rdv'] ?? $data['idRdv'] ?? 0);
		$idPrestation = (int) ($data['id_prestation'] ?? $data['idPrestation'] ?? 0);

		if ($idRdv <= 0 || $idPrestation <= 0) {
			return $this->json(['message' => 'id_rdv et id_prestation sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$rdv = $rendezVousRepository->find($idRdv);
		$prestation = $prestationRepository->find($idPrestation);

		if (!$rdv || !$prestation) {
			return $this->json(['message' => 'Rendez-vous ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$existing = $lierRepository->findOneBy([
			'rdv' => $rdv,
			'prestation' => $prestation,
		]);

		if ($existing) {
			return $this->json(['message' => 'Cette liaison existe deja.'], JsonResponse::HTTP_CONFLICT);
		}

		$liaison = new Lier();
		$liaison->setRdv($rdv);
		$liaison->setPrestation($prestation);

		$entityManager->persist($liaison);
		$entityManager->flush();

		return $this->json([
			'message' => 'Liaison creee avec succes.',
			'liaison' => $this->formatLiaison($liaison),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier une liaison (suppression + recreation pour cle composite)
	#[Route('/api/v1/edit_liaison/{idRdv}/{idPrestation}', name: 'api_edit_liaison', methods: ['POST'], requirements: ['idRdv' => '\\d+', 'idPrestation' => '\\d+'])]
	public function edit(
		int $idRdv,
		int $idPrestation,
		Request $request,
		RendezVousRepository $rendezVousRepository,
		PrestationRepository $prestationRepository,
		LierRepository $lierRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$oldRdv = $rendezVousRepository->find($idRdv);
		$oldPrestation = $prestationRepository->find($idPrestation);

		if (!$oldRdv || !$oldPrestation) {
			return $this->json(['message' => 'Rendez-vous ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$existing = $lierRepository->findOneBy([
			'rdv' => $oldRdv,
			'prestation' => $oldPrestation,
		]);

		if (!$existing) {
			return $this->json(['message' => 'Liaison introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$newIdRdv = (int) ($data['id_rdv'] ?? $data['idRdv'] ?? $idRdv);
		$newIdPrestation = (int) ($data['id_prestation'] ?? $data['idPrestation'] ?? $idPrestation);

		if ($newIdRdv <= 0 || $newIdPrestation <= 0) {
			return $this->json(['message' => 'id_rdv et id_prestation doivent etre valides.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$newRdv = $rendezVousRepository->find($newIdRdv);
		$newPrestation = $prestationRepository->find($newIdPrestation);

		if (!$newRdv || !$newPrestation) {
			return $this->json(['message' => 'Nouveau rendez-vous ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		if ($newIdRdv === $idRdv && $newIdPrestation === $idPrestation) {
			return $this->json([
				'message' => 'Aucune modification detectee.',
				'liaison' => $this->formatLiaison($existing),
			]);
		}

		$targetExists = $lierRepository->findOneBy([
			'rdv' => $newRdv,
			'prestation' => $newPrestation,
		]);

		if ($targetExists) {
			return $this->json(['message' => 'Liaison cible deja existante.'], JsonResponse::HTTP_CONFLICT);
		}

		$entityManager->remove($existing);

		$newLiaison = new Lier();
		$newLiaison->setRdv($newRdv);
		$newLiaison->setPrestation($newPrestation);
		$entityManager->persist($newLiaison);
		$entityManager->flush();

		return $this->json([
			'message' => 'Liaison modifiee avec succes.',
			'liaison' => $this->formatLiaison($newLiaison),
		]);
	}

	// methode pour supprimer une liaison
	#[Route('/api/v1/delete_liaison/{idRdv}/{idPrestation}', name: 'api_delete_liaison', methods: ['DELETE'], requirements: ['idRdv' => '\\d+', 'idPrestation' => '\\d+'])]
	public function delete(
		int $idRdv,
		int $idPrestation,
		RendezVousRepository $rendezVousRepository,
		PrestationRepository $prestationRepository,
		LierRepository $lierRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$rdv = $rendezVousRepository->find($idRdv);
		$prestation = $prestationRepository->find($idPrestation);

		if (!$rdv || !$prestation) {
			return $this->json(['message' => 'Rendez-vous ou prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$liaison = $lierRepository->findOneBy([
			'rdv' => $rdv,
			'prestation' => $prestation,
		]);

		if (!$liaison) {
			return $this->json(['message' => 'Liaison introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$entityManager->remove($liaison);
		$entityManager->flush();

		return $this->json(['message' => 'Liaison supprimee avec succes.']);
	}

	private function formatLiaison(Lier $lier): array
	{
		return [
			'id_rdv' => $lier->getRdv()?->getIdRdv(),
			'date_debut_rdv' => $lier->getRdv()?->getDateDebut()->format('Y-m-d H:i:s'),
			'date_fin_rdv' => $lier->getRdv()?->getDateFin()->format('Y-m-d H:i:s'),
			'id_prestation' => $lier->getPrestation()?->getIdPrestation(),
			'nom_prestation' => $lier->getPrestation()?->getNomPrestation(),
		];
	}
}
