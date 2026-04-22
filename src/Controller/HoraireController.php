<?php

namespace App\Controller;

use App\Entity\Associer;
use App\Entity\Garage;
use App\Entity\Horaire;
use App\Entity\Jour;
use App\Repository\AssocierRepository;
use App\Repository\GarageRepository;
use App\Repository\HoraireRepository;
use App\Repository\JourRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HoraireController extends AbstractController
{
    private function getJourByLabel(JourRepository $jourRepository, string $label): ?Jour
    {
        return $jourRepository->findOneBy(['libJour' => strtoupper($label)]);
    }

    private function findAssocierForGarageAndJour(
        EntityManagerInterface $em,
        Garage $garage,
        Jour $jour
    ): ?Associer {
        $qb = $em->getRepository(Associer::class)->createQueryBuilder('a')
            ->where('a.jour = :jour')
            ->andWhere('a.garage = :garage')
            ->setParameter('jour', $jour)
            ->setParameter('garage', $garage)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

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
	#[Route('/api/v1/get_horaires_by_garage/{idGarage}', name: 'api_get_horaires_by_garage', methods: ['GET'],)]
	public function getByGarage(int $idGarage, GarageRepository $garageRepository, AssocierRepository $associerRepository): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$horaires = $associerRepository->findDistinctHorairesByGarage($garage->getIdGarage());
		$result = array_map(
			fn (Horaire $horaire): array => $this->formatHoraire($horaire),
			$horaires
		);

		return $this->json($result);
	}

	// méthode pour récupérer le planning d'un garage pour une date donnée
	#[Route('/api/v1/garage/{idGarage}/planning/{date}', name: 'api_get_planning_garage_by_date', methods: ['GET'], requirements: ['idGarage' => '\d+', 'date' => '\\d{4}-\\d{2}-\\d{2}'])]
	public function getPlanningByGarageDate(int $idGarage, string $date, GarageRepository $garageRepository, JourRepository $jourRepository, RendezVousRepository $rdvRepository, EntityManagerInterface $em): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$dateObj = \DateTime::createFromFormat('Y-m-d', $date);
		if (!$dateObj) {
			return $this->json(['message' => 'Date invalide. Utiliser le format YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$dayNumber = (int) $dateObj->format('N');
		$dayLabels = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
		$dayLabel = $dayLabels[$dayNumber];
		$jour = $this->getJourByLabel($jourRepository, $dayLabel);

		if (!$jour) {
			return $this->json(['message' => 'Jour introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

        $associer = $this->findAssocierForGarageAndJour($em, $garage, $jour);

		$horaire = $associer?->getHoraire();

		$dayStart = (clone $dateObj)->setTime(0, 0, 0);
		$dayEnd = (clone $dateObj)->setTime(23, 59, 59);

		$rdvs = $rdvRepository->createQueryBuilder('r')
			->where('r.garage = :garage')
			->andWhere('r.dateDebut >= :dayStart')
			->andWhere('r.dateDebut <= :dayEnd')
			->setParameter('garage', $garage)
			->setParameter('dayStart', $dayStart)
			->setParameter('dayEnd', $dayEnd)
			->orderBy('r.dateDebut', 'ASC')
			->getQuery()
			->getResult();

		$rdvList = array_map(static function ($rdv) {
			return [
				'id_rdv' => $rdv->getIdRdv(),
				'date_debut' => $rdv->getDateDebut()->format('H:i'),
				'date_fin' => $rdv->getDateFin()->format('H:i'),
				'motif_refus' => $rdv->getMotifRefus(),
				'id_status_rdv' => $rdv->getStatusRdv()?->getIdStatusRdv(),
			];
		}, $rdvs);

		return $this->json([
			'date' => $dateObj->format('Y-m-d'),
			'jour' => [
				'id_jour' => $jour->getIdJour(),
				'lib_jour' => $dayLabel,
			],
			'horaire' => $horaire ? [
				'id_horaire' => $horaire->getIdHoraire(),
				'hre_ouvre_matin' => $horaire->getHreOuvreMatin()->format('H:i'),
				'hre_ferme_matin' => $horaire->getHreFermeMatin()->format('H:i'),
				'hre_ouvre_soir' => $horaire->getHreOuvreSoir()->format('H:i'),
				'hre_ferme_soir' => $horaire->getHreFermeSoir()->format('H:i'),
			] : null,
			'rdvs' => $rdvList,
		]);
	}

	// méthode pour récupérer le planning complet d'un garage sur 7 jours
	#[Route('/api/v1/get_planning_by_garage/{idGarage}', name: 'api_get_planning_by_garage', methods: ['GET'], requirements: ['idGarage' => '\d+'])]
	public function getPlanningByGarage(int $idGarage, GarageRepository $garageRepository, JourRepository $jourRepository, EntityManagerInterface $em): JsonResponse
	{
		$garage = $garageRepository->find($idGarage);
		if (!$garage) {
			return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$planning = [];
		$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

		foreach ($days as $dayLabel) {
			$jour = $this->getJourByLabel($jourRepository, $dayLabel);
			
			$associerGarage = $jour ? $this->findAssocierForGarageAndJour($em, $garage, $jour) : null;

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

		$horaire = $horaireRepository->findExistingHoraire($ouvreMatin, $fermeMatin, $ouvreSoir, $fermeSoir);
		$isNew = $horaire === null;

		if ($horaire === null) {
			$horaire = new Horaire();
			$horaire->setHreOuvreMatin($ouvreMatin);
			$horaire->setHreFermeMatin($fermeMatin);
			$horaire->setHreOuvreSoir($ouvreSoir);
			$horaire->setHreFermeSoir($fermeSoir);
			$entityManager->persist($horaire);
			$entityManager->flush();
		}

		$this->ensureSevenDaysForGarageHoraire($garage, $horaire, $jourRepository, $entityManager);
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

		// Si un garage est passe, on ne modifie pas un template partage: on reassigne le planning.
		if (isset($data['id_garage']) || isset($data['idGarage'])) {
			$idGarage = (int) ($data['id_garage'] ?? $data['idGarage']);
			if ($idGarage <= 0) {
				return $this->json(['message' => 'id_garage invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$garage = $garageRepository->find($idGarage);
			if (!$garage) {
				return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}

			$existing = $horaireRepository->findExistingHoraire(
				$horaire->getHreOuvreMatin(),
				$horaire->getHreFermeMatin(),
				$horaire->getHreOuvreSoir(),
				$horaire->getHreFermeSoir()
			);

			if ($existing === null) {
				$existing = new Horaire();
				$existing
					->setHreOuvreMatin($horaire->getHreOuvreMatin())
					->setHreFermeMatin($horaire->getHreFermeMatin())
					->setHreOuvreSoir($horaire->getHreOuvreSoir())
					->setHreFermeSoir($horaire->getHreFermeSoir());
				$entityManager->persist($existing);
				$entityManager->flush();
			}

			$this->ensureSevenDaysForGarageHoraire($garage, $existing, $jourRepository, $entityManager);
			$entityManager->flush();

			return $this->json([
				'message' => 'Planning du garage mis a jour avec un nouvel horaire.',
				'horaire' => $this->formatHoraire($existing),
			]);
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
		Garage $garage,
		Horaire $horaire,
		JourRepository $jourRepository,
		EntityManagerInterface $entityManager
	): void {
		$days = ['LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'];

		foreach ($days as $dayLabel) {
			$jour = $jourRepository->findOneBy(['libJour' => $dayLabel]);

			if (!$jour) {
				$jour = new Jour();
				$jour->setLibJour($dayLabel);
				$entityManager->persist($jour);
			}

			$existing = $entityManager->getRepository(Associer::class)->findOneBy([
				'garage' => $garage,
				'jour' => $jour,
			]);

			if ($existing) {
				$existing->setHoraire($horaire);
				continue;
			}

            $association = new Associer();
			$association->setGarage($garage);
            $association->setJour($jour);
            $association->setHoraire($horaire);
            $entityManager->persist($association);
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
		$order = ['LUNDI' => 1, 'MARDI' => 2, 'MERCREDI' => 3, 'JEUDI' => 4, 'VENDREDI' => 5, 'SAMEDI' => 6, 'DIMANCHE' => 7];

		foreach ($horaire->getAssociers() as $associer) {
			$libJour = $associer->getJour()?->getLibJour();
			if ($libJour === null) {
				continue;
			}

            $jours[] = [
                'id_jour' => $associer->getJour()?->getIdJour(),
                'lib_jour' => $libJour,
                'order' => $order[strtoupper($libJour)] ?? 99,
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
		];
	}
    

	#[Route('/api/v1/horaires/create', name: 'create', methods: ['POST'])]
    // Ici, c'est pour créer un nouvel horaire, easy
    public function create(Request $request): JsonResponse
    {
        // On vérifie si t'as le droit de faire ça (faut être Superadmin, cousin)
        if (($response = $this->verifierAccesGestionHoraires()) !== null) {
            return $response;
        }

        // On récupère les données envoyées (payload)
        $payload = $this->recupererPayload($request);

        // On check que t'as bien tout rempli, sinon on râle
        foreach (['garageId', 'hreOuvreMatin', 'hreFermeMatin', 'hreOuvreSoir', 'hreFermeSoir'] as $field) {
            if (!isset($payload[$field])) {
                return $this->json([
                    'message' => sprintf('Champ requis: %s', $field),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // On va chercher le garage qui va avec l'id
        $garage = $this->entityManager->getRepository(Garage::class)->find((int) $payload['garageId']);
        if ($garage === null) {
            return $this->json(['message' => 'Garage introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        // On convertit les heures en objets DateTime (sinon c'est le dawa)
        $ouvreMatin = $this->parserHeure((string) $payload['hreOuvreMatin']);
        $fermeMatin = $this->parserHeure((string) $payload['hreFermeMatin']);
        $ouvreSoir = $this->parserHeure((string) $payload['hreOuvreSoir']);
        $fermeSoir = $this->parserHeure((string) $payload['hreFermeSoir']);

        if ($ouvreMatin === null || $fermeMatin === null || $ouvreSoir === null || $fermeSoir === null) { // Si une heure est foireuse, on te le dit
            return $this->json(['message' => 'Format heure invalide. Utiliser HH:MM.'], Response::HTTP_BAD_REQUEST);
        }

        // On vérifie s'il existe déjà un horaire pour ce garage
        $horaireRepository = $this->entityManager->getRepository(Horaire::class);
        $horaire = $horaireRepository->findOneBy(['garage' => $garage]);

        if ($horaire) {
            // S'il existe, on le met à jour
            $horaire
                ->setHreOuvreMatin($ouvreMatin)
                ->setHreFermeMatin($fermeMatin)
                ->setHreOuvreSoir($ouvreSoir)
                ->setHreFermeSoir($fermeSoir);
            $message = 'Horaire existant mis à jour avec succès.';
            $status = Response::HTTP_OK;
        } else {
            // Sinon, on crée un nouvel horaire
            $horaire = new Horaire();
            $horaire
                ->setGarage($garage)
                ->setHreOuvreMatin($ouvreMatin)
                ->setHreFermeMatin($fermeMatin)
                ->setHreOuvreSoir($ouvreSoir)
                ->setHreFermeSoir($fermeSoir);
            $this->entityManager->persist($horaire);
            $message = 'Horaire cree avec succes.';
            $status = Response::HTTP_CREATED;
        }

        $this->entityManager->flush();

        // On te confirme le résultat
        return $this->json([
            'message' => $message,
            'horaire' => $this->serialiserHoraire($horaire),
        ], $status);
    }

}
