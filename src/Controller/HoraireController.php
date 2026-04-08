<?php

namespace App\Controller;

use App\Entity\Associer;
use App\Entity\Horaire;
use App\Entity\Jour;
use App\Repository\GarageRepository;
use App\Repository\HoraireRepository;
use App\Repository\JourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class HoraireController extends AbstractController
{
	// méthode pour récupérer tous les horaires
	#[Route('/api/v1/get_horaires', name: 'api_get_horaires', methods: ['GET'])]
	public function getAllHoraires(HoraireRepository $horaireRepository): JsonResponse
	{
		$horaires = $horaireRepository->findAll();

		$result = array_map(
			fn (Horaire $horaire): array => $this->formatHoraire($horaire),
			$horaires
		);

		return $this->json($result);
	}

	// méthode pour récupérer les horaires d'un garage
	#[Route('/api/v1/get_horaires_by_garage/{idGarage}', name: 'api_get_horaires_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
	public function getByGarage(int $idGarage, GarageRepository $garageRepository, HoraireRepository $horaireRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$horaires = $horaireRepository->findBy(['garage' => $garage]);
		$result = array_map(
			fn (Horaire $horaire): array => $this->formatHoraire($horaire),
			$horaires
		);

		return $this->json($result);
	}

	// méthode pour récupérer le planning complet d'un garage sur 7 jours
	#[Route('/api/v1/get_planning_by_garage/{idGarage}', name: 'api_get_planning_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
	public function getPlanningByGarage(int $idGarage, GarageRepository $garageRepository, JourRepository $jourRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
		$planning = [];

		foreach ($days as $dayLabel) {
			$jour = $jourRepository->findOneBy(['libJour' => $dayLabel]);
			$associerGarage = null;

			if ($jour) {
				foreach ($jour->getAssociers() as $associer) {
					if ($associer->getHoraire()?->getGarage()?->getIdGarage() === $garage->getIdGarage()) {
						$associerGarage = $associer;
						break;
					}
				}
			}

			$planning[] = [
				'id_jour' => $jour?->getIdJour(),
				'lib_jour' => $dayLabel,
				'id_horaire' => $associerGarage?->getHoraire()?->getIdHoraire(),
				'hre_ouvre_matin' => $associerGarage?->getHoraire()?->getHreOuvreMatin()?->format('H:i'),
				'hre_ferme_matin' => $associerGarage?->getHoraire()?->getHreFermeMatin()?->format('H:i'),
				'hre_ouvre_soir' => $associerGarage?->getHoraire()?->getHreOuvreSoir()?->format('H:i'),
				'hre_ferme_soir' => $associerGarage?->getHoraire()?->getHreFermeSoir()?->format('H:i'),
			];
		}

		return $this->json([
			'id_garage' => $garage->getIdGarage(),
			'nom_garage' => $garage->getNomGarage(),
			'planning' => $planning,
		]);
	}

	// méthode pour récupérer le détail d'un horaire
	#[Route('/api/v1/get_horaire/{id}', name: 'api_get_horaire', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, HoraireRepository $horaireRepository): JsonResponse
	{
		$horaire = $horaireRepository->find($id);
		if (!$horaire) {
			return $this->json(['message' => 'Horaire introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatHoraire($horaire));
	}

	// méthode pour ajouter ou modifier l'horaire d'un garage
	#[Route('/api/v1/new_horaire', name: 'api_new_horaire', methods: ['POST'])]
	public function newHoraire(
		Request $request,
		GarageRepository $garageRepository,
		HoraireRepository $horaireRepository,
		JourRepository $jourRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$idGarage = (int) ($data['id_garage'] ?? $data['idGarage'] ?? 0);
		if ($idGarage <= 0) {
			return $this->json(['message' => 'id_garage est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$ouvreMatin = $this->parseTime((string) ($data['hre_ouvre_matin'] ?? $data['hreOuvreMatin'] ?? ''));
		$fermeMatin = $this->parseTime((string) ($data['hre_ferme_matin'] ?? $data['hreFermeMatin'] ?? ''));
		$ouvreSoir = $this->parseTime((string) ($data['hre_ouvre_soir'] ?? $data['hreOuvreSoir'] ?? ''));
		$fermeSoir = $this->parseTime((string) ($data['hre_ferme_soir'] ?? $data['hreFermeSoir'] ?? ''));

		if (!$ouvreMatin || !$fermeMatin || !$ouvreSoir || !$fermeSoir) {
			return $this->json(['message' => 'Les heures sont requises au format HH:MM.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$horaire = $horaireRepository->findOneBy([
			'garage' => $garage,
		], ['idHoraire' => 'DESC']);

		$isNew = !$horaire;

		if (!$horaire) {
			$horaire = new Horaire();
			$horaire->setGarage($garage);
			$entityManager->persist($horaire);
		}

		$horaire->setHreOuvreMatin($ouvreMatin);
		$horaire->setHreFermeMatin($fermeMatin);
		$horaire->setHreOuvreSoir($ouvreSoir);
		$horaire->setHreFermeSoir($fermeSoir);

		$this->ensureSevenDaysForGarageHoraire($horaire, $jourRepository, $entityManager);
		$entityManager->flush();

		return $this->json([
			'message' => $isNew
				? 'L horaire a ete ajoute avec succes.'
				: 'L horaire du garage a ete modifie avec succes.',
			'horaire' => $this->formatHoraire($horaire),
		], $isNew ? JsonResponse::HTTP_CREATED : JsonResponse::HTTP_OK);
	}

	// méthode pour modifier un horaire
	#[Route('/api/v1/edit_horaire/{id}', name: 'api_edit_horaire', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		HoraireRepository $horaireRepository,
		GarageRepository $garageRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$horaire = $horaireRepository->find($id);
		if (!$horaire) {
			return $this->json(['message' => 'Horaire introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
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

			$horaire->setGarage($garage);
		}

		if (isset($data['hre_ouvre_matin']) || isset($data['hreOuvreMatin'])) {
			$value = (string) ($data['hre_ouvre_matin'] ?? $data['hreOuvreMatin']);
			$time = $this->parseTime($value);
			if (!$time) {
				return $this->json(['message' => 'Format hre_ouvre_matin invalide (HH:MM).'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$horaire->setHreOuvreMatin($time);
		}

		if (isset($data['hre_ferme_matin']) || isset($data['hreFermeMatin'])) {
			$value = (string) ($data['hre_ferme_matin'] ?? $data['hreFermeMatin']);
			$time = $this->parseTime($value);
			if (!$time) {
				return $this->json(['message' => 'Format hre_ferme_matin invalide (HH:MM).'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$horaire->setHreFermeMatin($time);
		}

		if (isset($data['hre_ouvre_soir']) || isset($data['hreOuvreSoir'])) {
			$value = (string) ($data['hre_ouvre_soir'] ?? $data['hreOuvreSoir']);
			$time = $this->parseTime($value);
			if (!$time) {
				return $this->json(['message' => 'Format hre_ouvre_soir invalide (HH:MM).'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$horaire->setHreOuvreSoir($time);
		}

		if (isset($data['hre_ferme_soir']) || isset($data['hreFermeSoir'])) {
			$value = (string) ($data['hre_ferme_soir'] ?? $data['hreFermeSoir']);
			$time = $this->parseTime($value);
			if (!$time) {
				return $this->json(['message' => 'Format hre_ferme_soir invalide (HH:MM).'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$horaire->setHreFermeSoir($time);
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'L horaire a ete modifie avec succes.',
			'horaire' => $this->formatHoraire($horaire),
		]);
	}

	// méthode pour supprimer un horaire
	#[Route('/api/v1/delete_horaire/{id}', name: 'api_delete_horaire', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Horaire $horaire, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($horaire);
		$entityManager->flush();

		return $this->json([
			'message' => 'L horaire a ete supprime avec succes.',
		]);
	}

	private function ensureSevenDaysForGarageHoraire(
		Horaire $horaire,
		JourRepository $jourRepository,
		EntityManagerInterface $entityManager
	): void {
		$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

		foreach ($days as $dayLabel) {
			$jour = $jourRepository->findOneBy(['libJour' => $dayLabel]);

			if (!$jour) {
				$jour = new Jour();
				$jour->setLibJour($dayLabel);
				$entityManager->persist($jour);
			}

			$association = $entityManager->getRepository(Associer::class)->findOneBy([
				'jour' => $jour,
				'horaire' => $horaire,
			]);

			if (!$association) {
				$association = new Associer();
				$association->setJour($jour);
				$association->setHoraire($horaire);
				$entityManager->persist($association);
			}
		}
	}

	private function parseTime(string $value): ?\DateTime
	{
		$time = \DateTime::createFromFormat('H:i', trim($value));

		return $time ?: null;
	}

	private function formatHoraire(Horaire $horaire): array
	{
		$jours = [];
		$order = ['Lundi' => 1, 'Mardi' => 2, 'Mercredi' => 3, 'Jeudi' => 4, 'Vendredi' => 5, 'Samedi' => 6, 'Dimanche' => 7];

		foreach ($horaire->getAssociers() as $associer) {
			$libJour = $associer->getJour()?->getLibJour();
			if ($libJour === null) {
				continue;
			}

			$jours[] = [
				'id_jour' => $associer->getJour()?->getIdJour(),
				'lib_jour' => $libJour,
				'order' => $order[$libJour] ?? 99,
			];
		}

		usort($jours, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);
		$jours = array_map(static fn (array $jour): array => [
			'id_jour' => $jour['id_jour'],
			'lib_jour' => $jour['lib_jour'],
		], $jours);

		return [
			'id_horaire' => $horaire->getIdHoraire(),
			'hre_ouvre_matin' => $horaire->getHreOuvreMatin()->format('H:i'),
			'hre_ferme_matin' => $horaire->getHreFermeMatin()->format('H:i'),
			'hre_ouvre_soir' => $horaire->getHreOuvreSoir()->format('H:i'),
			'hre_ferme_soir' => $horaire->getHreFermeSoir()->format('H:i'),
			'jours' => $jours,
			'garage' => [
				'id_garage' => $horaire->getGarage()?->getIdGarage(),
				'nom_garage' => $horaire->getGarage()?->getNomGarage(),
			],
		];
	}
}
