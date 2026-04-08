<?php

namespace App\Controller;

use App\Entity\Associer;
use App\Repository\AssocierRepository;
use App\Repository\GarageRepository;
use App\Repository\HoraireRepository;
use App\Repository\JourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class AssocierController extends AbstractController
{
	// methode pour recuperer toutes les associations jour/horaire
	#[Route('/api/v1/get_associations', name: 'api_get_associations', methods: ['GET'])]
	public function getAll(AssocierRepository $associerRepository): JsonResponse
	{
		$associations = $associerRepository->findAll();

		$result = array_map(
			fn (Associer $associer): array => $this->formatAssociation($associer),
			$associations
		);

		return $this->json($result);
	}

	// methode pour recuperer les associations d'un jour
	#[Route('/api/v1/get_associations_by_jour/{idJour}', name: 'api_get_associations_by_jour', methods: ['GET'], requirements: ['idJour' => '\\d+'])]
	public function getByJour(int $idJour, JourRepository $jourRepository, AssocierRepository $associerRepository): JsonResponse
	{
		$jour = $jourRepository->find($idJour);
		if (!$jour) {
			return $this->json(['message' => 'Jour introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$associations = $associerRepository->findBy(['jour' => $jour]);
		$result = array_map(
			fn (Associer $associer): array => $this->formatAssociation($associer),
			$associations
		);

		return $this->json($result);
	}

	// methode pour recuperer les associations d'un horaire
	#[Route('/api/v1/get_associations_by_horaire/{idHoraire}', name: 'api_get_associations_by_horaire', methods: ['GET'], requirements: ['idHoraire' => '\\d+'])]
	public function getByHoraire(int $idHoraire, HoraireRepository $horaireRepository, AssocierRepository $associerRepository): JsonResponse
	{
		$horaire = $horaireRepository->find($idHoraire);
		if (!$horaire) {
			return $this->json(['message' => 'Horaire introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$associations = $associerRepository->findBy(['horaire' => $horaire]);
		$result = array_map(
			fn (Associer $associer): array => $this->formatAssociation($associer),
			$associations
		);

		return $this->json($result);
	}

	// methode pour recuperer les associations d'un garage
	#[Route('/api/v1/get_associations_by_garage/{idGarage}', name: 'api_get_associations_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
	public function getByGarage(int $idGarage, GarageRepository $garageRepository, AssocierRepository $associerRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$associations = array_filter(
			$associerRepository->findAll(),
			static fn (Associer $associer): bool => $associer->getHoraire()?->getGarage()?->getIdGarage() === $idGarage
		);

		usort($associations, static fn (Associer $a, Associer $b): int =>
			($a->getJour()?->getIdJour() ?? 0) <=> ($b->getJour()?->getIdJour() ?? 0)
		);

		$result = array_map(
			fn (Associer $associer): array => $this->formatAssociation($associer),
			$associations
		);

		return $this->json($result);
	}

	// methode pour initialiser les 7 associations manquantes d'un garage
	#[Route('/api/v1/init_associations_garage/{idGarage}', name: 'api_init_associations_garage', methods: ['POST'], requirements: ['idGarage' => '\\d+'])]
	public function initAssociationsGarage(
		int $idGarage,
		Request $request,
		GarageRepository $garageRepository,
		JourRepository $jourRepository,
		HoraireRepository $horaireRepository,
		AssocierRepository $associerRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		$data = is_array($data) ? $data : [];

		$idHoraire = (int) ($data['id_horaire'] ?? $data['idHoraire'] ?? 0);
		$horaire = $idHoraire > 0
			? $horaireRepository->find($idHoraire)
			: $horaireRepository->findOneBy(['garage' => $garage], ['idHoraire' => 'DESC']);

		if (!$horaire || $horaire->getGarage()?->getIdGarage() !== $garage->getIdGarage()) {
			return $this->json(['message' => 'Horaire introuvable pour ce garage.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
		$created = [];
		$existing = [];

		foreach ($joursSemaine as $libJour) {
			$jour = $jourRepository->findOneBy(['libJour' => $libJour]);

			if (!$jour) {
				$jour = new \App\Entity\Jour();
				$jour->setLibJour($libJour);
				$entityManager->persist($jour);
				$entityManager->flush();
			}

			$associationGarage = null;
			foreach ($associerRepository->findBy(['jour' => $jour]) as $associer) {
				if ($associer->getHoraire()?->getGarage()?->getIdGarage() === $garage->getIdGarage()) {
					$associationGarage = $associer;
					break;
				}
			}

			if ($associationGarage) {
				$existing[] = [
					'id_jour' => $jour->getIdJour(),
					'lib_jour' => $jour->getLibJour(),
					'id_horaire' => $associationGarage->getHoraire()?->getIdHoraire(),
				];
				continue;
			}

			$association = new Associer();
			$association->setJour($jour);
			$association->setHoraire($horaire);
			$entityManager->persist($association);

			$created[] = [
				'id_jour' => $jour->getIdJour(),
				'lib_jour' => $jour->getLibJour(),
				'id_horaire' => $horaire->getIdHoraire(),
			];
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'Initialisation des associations du garage terminee.',
			'id_garage' => $garage->getIdGarage(),
			'id_horaire' => $horaire->getIdHoraire(),
			'created' => $created,
			'existing' => $existing,
		]);
	}

	// methode pour recuperer une association specifique
	#[Route('/api/v1/get_association/{idJour}/{idHoraire}', name: 'api_get_association', methods: ['GET'], requirements: ['idJour' => '\\d+', 'idHoraire' => '\\d+'])]
	public function show(
		int $idJour,
		int $idHoraire,
		JourRepository $jourRepository,
		HoraireRepository $horaireRepository,
		AssocierRepository $associerRepository
	): JsonResponse {
		$jour = $jourRepository->find($idJour);
		$horaire = $horaireRepository->find($idHoraire);

		if (!$jour || !$horaire) {
			return $this->json(['message' => 'Jour ou horaire introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$association = $associerRepository->findOneBy([
			'jour' => $jour,
			'horaire' => $horaire,
		]);

		if (!$association) {
			return $this->json(['message' => 'Association introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatAssociation($association));
	}

	// methode pour creer une association jour/horaire
	#[Route('/api/v1/new_association', name: 'api_new_association', methods: ['POST'])]
	public function create(
		Request $request,
		JourRepository $jourRepository,
		HoraireRepository $horaireRepository,
		AssocierRepository $associerRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$idJour = (int) ($data['id_jour'] ?? $data['idJour'] ?? 0);
		$idHoraire = (int) ($data['id_horaire'] ?? $data['idHoraire'] ?? 0);

		if ($idJour <= 0 || $idHoraire <= 0) {
			return $this->json(['message' => 'id_jour et id_horaire sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$jour = $jourRepository->find($idJour);
		$horaire = $horaireRepository->find($idHoraire);

		if (!$jour || !$horaire) {
			return $this->json(['message' => 'Jour ou horaire introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$existing = $associerRepository->findOneBy([
			'jour' => $jour,
			'horaire' => $horaire,
		]);

		if ($existing) {
			return $this->json(['message' => 'Cette association existe deja.'], JsonResponse::HTTP_CONFLICT);
		}

		$association = new Associer();
		$association->setJour($jour);
		$association->setHoraire($horaire);

		$entityManager->persist($association);
		$entityManager->flush();

		return $this->json([
			'message' => 'Association creee avec succes.',
			'association' => $this->formatAssociation($association),
		], JsonResponse::HTTP_CREATED);
	}

	#[Route('/api/v1/edit_association/{idJour}/{idHoraire}', name: 'api_edit_association', methods: ['POST'], requirements: ['idJour' => '\\d+', 'idHoraire' => '\\d+'])]
	public function edit(
		int $idJour,
		int $idHoraire,
		Request $request,
		JourRepository $jourRepository,
		HoraireRepository $horaireRepository,
		AssocierRepository $associerRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$oldJour = $jourRepository->find($idJour);
		$oldHoraire = $horaireRepository->find($idHoraire);

		if (!$oldJour || !$oldHoraire) {
			return $this->json(['message' => 'Jour ou horaire introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$existing = $associerRepository->findOneBy([
			'jour' => $oldJour,
			'horaire' => $oldHoraire,
		]);

		if (!$existing) {
			return $this->json(['message' => 'Association introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$newIdJour = (int) ($data['id_jour'] ?? $data['idJour'] ?? $idJour);
		$newIdHoraire = (int) ($data['id_horaire'] ?? $data['idHoraire'] ?? $idHoraire);

		if ($newIdJour <= 0 || $newIdHoraire <= 0) {
			return $this->json(['message' => 'id_jour et id_horaire doivent etre valides.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$newJour = $jourRepository->find($newIdJour);
		$newHoraire = $horaireRepository->find($newIdHoraire);

		if (!$newJour || !$newHoraire) {
			return $this->json(['message' => 'Nouveau jour ou horaire introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		if ($newIdJour === $idJour && $newIdHoraire === $idHoraire) {
			return $this->json([
				'message' => 'Aucune modification detectee.',
				'association' => $this->formatAssociation($existing),
			]);
		}

		$targetExists = $associerRepository->findOneBy([
			'jour' => $newJour,
			'horaire' => $newHoraire,
		]);

		if ($targetExists) {
			return $this->json(['message' => 'Association cible deja existante.'], JsonResponse::HTTP_CONFLICT);
		}

		$entityManager->remove($existing);
		$entityManager->flush();

		$entityManager->clear();

		$freshJour = $jourRepository->find($newIdJour);
		$freshHoraire = $horaireRepository->find($newIdHoraire);

		$newAssociation = new Associer();
		$newAssociation->setJour($freshJour);
		$newAssociation->setHoraire($freshHoraire);

		$entityManager->persist($newAssociation);
		$entityManager->flush();

		return $this->json([
			'message' => 'Association modifiee avec succes.',
			'association' => $this->formatAssociation($newAssociation),
		]);
	}

	// methode pour supprimer une association
	#[Route('/api/v1/delete_association/{idJour}/{idHoraire}', name: 'api_delete_association', methods: ['DELETE'], requirements: ['idJour' => '\\d+', 'idHoraire' => '\\d+'])]
	public function delete(
		int $idJour,
		int $idHoraire,
		JourRepository $jourRepository,
		HoraireRepository $horaireRepository,
		AssocierRepository $associerRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$jour = $jourRepository->find($idJour);
		$horaire = $horaireRepository->find($idHoraire);

		if (!$jour || !$horaire) {
			return $this->json(['message' => 'Jour ou horaire introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$association = $associerRepository->findOneBy([
			'jour' => $jour,
			'horaire' => $horaire,
		]);

		if (!$association) {
			return $this->json(['message' => 'Association introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$entityManager->remove($association);
		$entityManager->flush();

		return $this->json(['message' => 'Association supprimee avec succes.']);
	}

	private function formatAssociation(Associer $associer): array
	{
		return [
			'id_jour' => $associer->getJour()?->getIdJour(),
			'lib_jour' => $associer->getJour()?->getLibJour(),
			'id_horaire' => $associer->getHoraire()?->getIdHoraire(),
			'hre_ouvre_matin' => $associer->getHoraire()?->getHreOuvreMatin()->format('H:i'),
			'hre_ferme_matin' => $associer->getHoraire()?->getHreFermeMatin()->format('H:i'),
			'hre_ouvre_soir' => $associer->getHoraire()?->getHreOuvreSoir()->format('H:i'),
			'hre_ferme_soir' => $associer->getHoraire()?->getHreFermeSoir()->format('H:i'),
			'id_garage' => $associer->getHoraire()?->getGarage()?->getIdGarage(),
		];
	}
}
