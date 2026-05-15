<?php

namespace App\Controller;

use App\Entity\Associer;
use App\Entity\Categorie;
use App\Entity\Garage;
use App\Entity\Historique;
use App\Entity\Horaire;
use App\Entity\Jour;
use App\Entity\Prestation;
use App\Entity\Proposer;
use App\Entity\RendezVous;
use App\Entity\StatusRdv;
use App\Entity\Vehicule;
use App\Entity\Ville;
use App\Repository\GarageRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Utilisateur;

#[Route('', name: '/api_garage_')]
final class GarageController extends AbstractController
{
    private function parseClientDateTime(?string $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $tz = new \DateTimeZone('Europe/Paris');

        try {
            $dt = new \DateTimeImmutable($value, $tz);
        } catch (\Throwable) {
            return null;
        }

        return $dt->setTimezone($tz);
    }
    //  On recupere l'EntityManager une fois pour tout le controleur.
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerService $mailerService
    )
    {
    }



    // Ici on renvoie le profil du garage demande.
    #[Route('/api/v1/profil', name: 'profil_show', methods: ['GET'])]
    public function afficherProfil(Request $request): JsonResponse
    {
        $garage = $this->resoudreGarage($request);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'garage' => $this->serialiserGarage($garage),
        ]);
    }

    // Ici on met a jour les infos de base du profil garage.
    #[Route('/api/v1/profil', name: 'profil_update', methods: ['PUT', 'PATCH'])]
    public function mettreAJourProfil(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (isset($payload['nomGarage'])) {
            $garage->setNomGarage((string) $payload['nomGarage']);
        }
        if (isset($payload['emailGarage'])) {
            $garage->setEmailGarage((string) $payload['emailGarage']);
        }
        if (isset($payload['telephoneGarage'])) {
            $garage->setTelephoneGarage((string) $payload['telephoneGarage']);
        }
        if (isset($payload['adresseGarage'])) {
            $garage->setAdresseGarage((string) $payload['adresseGarage']);
        }
        if (isset($payload['siret'])) {
            $garage->setSiret((string) $payload['siret']);
        }
        if (isset($payload['tva'])) {
            $garage->setTva((string) $payload['tva']);
        }
        if (array_key_exists('imgGarage', $payload)) {
            $garage->setImgGarage($payload['imgGarage'] !== null ? (string) $payload['imgGarage'] : null);
        }
        if (array_key_exists('imgLogo', $payload)) {
            $garage->setImgLogo($payload['imgLogo'] !== null ? (string) $payload['imgLogo'] : null);
        }

        if (isset($payload['villeId'])) {
            $ville = $this->entityManager->getRepository(Ville::class)->find((int) $payload['villeId']);
            if ($ville === null) {
                return $this->json(['error' => 'Ville introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $garage->setVille($ville);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Profil garage mis a jour',
            'garage' => $this->serialiserGarage($garage),
        ]);
    }

    // Ici on supprime le profil garage si les contraintes le permettent.
    #[Route('/api/v1/profil', name: 'profil_delete', methods: ['DELETE'])]
    public function supprimerProfil(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($garage);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Suppression impossible (contraintes relationnelles)',
                'details' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'message' => 'Profil garage supprime',
        ]);
    }

    // Ici on enregistre les horaires d'ouverture et de fermeture du garage.
    #[Route('/api/v1/horaires/ouvertures_fermetures', name: 'horaires_ouvertures_fermetures', methods: ['PATCH'])]
    public function mettreAJourOuverturesFermetures(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }
        // on verifie que les champs horaires sont tous presents dans le payload.
        foreach (['hreOuvreMatin', 'hreFermeMatin', 'hreOuvreSoir', 'hreFermeSoir'] as $field) {
            if (!isset($payload[$field])) {
                return $this->json(['error' => sprintf('Champ requis: %s (format HH:MM)', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        $ouvreMatin = $this->parserHeure((string) $payload['hreOuvreMatin']);
        $fermeMatin = $this->parserHeure((string) $payload['hreFermeMatin']);
        $ouvreSoir = $this->parserHeure((string) $payload['hreOuvreSoir']);
        $fermeSoir = $this->parserHeure((string) $payload['hreFermeSoir']);
//        on verifie que les heures sont valides.
        if ($ouvreMatin === null || $fermeMatin === null || $ouvreSoir === null || $fermeSoir === null) {
            return $this->json(['error' => 'Format heure invalide. Utiliser HH:MM'], Response::HTTP_BAD_REQUEST);
        }

        // Template global: on le reutilise si une ligne identique existe, sinon on cree.
        $horaireRepository = $this->entityManager->getRepository(Horaire::class);
        $horaire = method_exists($horaireRepository, 'findExistingHoraire')
            ? $horaireRepository->findExistingHoraire($ouvreMatin, $fermeMatin, $ouvreSoir, $fermeSoir)
            : null;

        if ($horaire === null) {
            $horaire = new Horaire();
            $horaire
                ->setHreOuvreMatin($ouvreMatin)
                ->setHreFermeMatin($fermeMatin)
                ->setHreOuvreSoir($ouvreSoir)
                ->setHreFermeSoir($fermeSoir);
            $this->entityManager->persist($horaire);
            $this->entityManager->flush();
        }

        // Par defaut, on applique cet horaire sur les 7 jours pour ce garage.
        $jours = $this->entityManager->getRepository(Jour::class)->findBy([], ['idJour' => 'ASC']);
        foreach ($jours as $jour) {
            $associer = $this->entityManager->getRepository(Associer::class)->findOneBy([
                'garage' => $garage,
                'jour' => $jour,
            ]);
            if ($associer) {
                $associer->setHoraire($horaire);
                continue;
            }

            $associer = new Associer();
            $associer->setGarage($garage);
            $associer->setJour($jour);
            $associer->setHoraire($horaire);
            $this->entityManager->persist($associer);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Ouvertures/fermetures mises a jour',
            'horaire' => $this->serialiserHoraire($horaire),
        ]);
    }

    // Ici on branche les jours de la semaine avec les bons horaires.
   #[Route('/api/v1/planning/semaine', name: 'planning_semaine_show', methods: ['GET'])]
    public function afficherPlanningSemaine(Request $request): JsonResponse
    {
        $garage = $this->resoudreGarage($request);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $jours = $this->entityManager->getRepository(Jour::class)->findBy([], ['idJour' => 'ASC']);
        $associers = $this->entityManager->getRepository(Associer::class)->findBy(['garage' => $garage]);
        $horairesByJour = [];
        foreach ($associers as $associer) {
            $jourId = $associer->getJour()?->getIdJour();
            if ($jourId === null) {
                continue;
            }
            $horairesByJour[$jourId] = $associer->getHoraire();
        }

        return $this->json([
            'garage_id' => $garage->getIdGarage(),
            'jours' => array_map(
                function (Jour $jour) use ($horairesByJour) {
                    $jourId = $jour->getIdJour();
                    $horaire = $horairesByJour[$jourId] ?? null;

                    return [
                        'jourId' => $jourId,
                        'libJour' => $jour->getLibJour(),
                        'isActive' => $horaire !== null,
                        'horaire' => $horaire !== null ? $this->serialiserHoraire($horaire) : null,
                    ];
                },
                $jours
            ),
        ]);
    }


    #[Route('/api/v1/planning/semaine', name: 'planning_semaine_update', methods: ['PUT', 'PATCH'])]
    public function mettreAJourPlanningSemaine(Request $request): JsonResponse
    {
        $garage = $this->resoudreGarage($request);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->recupererPayload($request);
        $planning = $payload['planning'] ?? null;
        if (!is_array($planning)) {
            return $this->json([
                'error' => 'Le payload doit contenir planning: [{jourId, horaireId}]',
            ], Response::HTTP_BAD_REQUEST);
        }

        foreach ($planning as $item) {
            if (!is_array($item) || !isset($item['jourId'])) {
                return $this->json([
                    'error' => 'Chaque entree doit avoir jourId',
                ], Response::HTTP_BAD_REQUEST);
            }

            $jour = $this->entityManager->getRepository(Jour::class)->find((int) $item['jourId']);
            if ($jour === null) {
                return $this->json(['error' => 'Jour introuvable'], Response::HTTP_BAD_REQUEST);
            }

            $horaireId = $item['horaireId'] ?? null;
            $horaire = null;
            if ($horaireId !== null && $horaireId !== '') {
                $horaire = $this->entityManager->getRepository(Horaire::class)->find((int) $horaireId);
                if ($horaire === null) {
                    return $this->json(['error' => 'Horaire introuvable'], Response::HTTP_BAD_REQUEST);
                }
            }

            $existingAssocier = $this->entityManager->getRepository(Associer::class)->findOneBy([
                'garage' => $garage,
                'jour' => $jour,
            ]);

            if ($horaire === null) {
                if ($existingAssocier) {
                    $this->entityManager->remove($existingAssocier);
                }

                continue;
            }

            if ($existingAssocier) {
                $existingAssocier->setHoraire($horaire);
                continue;
            }

            $associer = new Associer();
            $associer->setGarage($garage);
            $associer->setJour($jour);
            $associer->setHoraire($horaire);
            $this->entityManager->persist($associer);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Planning de la semaine mis a jour',
            'planning' => $planning,
        ]);
    }

    #[Route('/api/v1/horaires/demande_super_admin', name: 'horaires_demande_super_admin', methods: ['POST'])]
    public function demanderHoraireSuperAdmin(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);

        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $jours = $payload['jours'] ?? [];
        $motif = trim((string) ($payload['motif'] ?? 'Aucun horaire predefini ne correspond au besoin.'));

        $superAdmins = $this->entityManager->getRepository(Utilisateur::class)
            ->createQueryBuilder('u')
            ->join('u.role', 'r')
            ->where('r.nomRole = :role')
            ->setParameter('role', 'ROLE_SUPER_ADMIN')
            ->getQuery()
            ->getResult();

        $emails = array_values(array_filter(array_map(
            static fn (Utilisateur $utilisateur): ?string => $utilisateur->getEmailUtilisateur() ?: null,
            $superAdmins
        )));

        if ($emails === []) {
            return $this->json([
                'error' => 'Aucun super admin disponible pour recevoir la demande',
            ], Response::HTTP_NOT_FOUND);
        }

        $joursTexte = is_array($jours) ? implode(', ', array_map('strval', $jours)) : '';
        $subject = sprintf('Demande d horaire - %s', $garage->getNomGarage());
        $html = sprintf(
            '<h2>Demande de horaire predefini</h2><p><strong>Garage :</strong> %s</p><p><strong>Jours concernes :</strong> %s</p><p><strong>Besoin :</strong> %s</p><p>Merci de valider ou proposer un modele standard.</p>',
            htmlspecialchars($garage->getNomGarage(), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($joursTexte !== '' ? $joursTexte : 'Non precise', ENT_QUOTES, 'UTF-8'),
            nl2br(htmlspecialchars($motif, ENT_QUOTES, 'UTF-8'))
        );

        foreach ($emails as $email) {
            $this->mailerService->sendEmail($email, $subject, $html);
        }

        return $this->json([
            'message' => 'Demande envoyee au super admin',
            'emails' => $emails,
        ]);
    }


    // Ici on rattache une prestation existante au garage avec son prix.
    #[Route('/api/v1/prestations', name: 'prestations_add', methods: ['POST'])]
    public function ajouterPrestation(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $items = [];
        if (isset($payload['prestations']) && is_array($payload['prestations'])) {
            $items = $payload['prestations'];
        } elseif (isset($payload['prestationId'])) {
            $items = [$payload];
        }

        if ($items === []) {
            return $this->json([
                'error' => 'Une prestation existante et un prix sont requis',
            ], Response::HTTP_BAD_REQUEST);
        }

        $prestationRepo = $this->entityManager->getRepository(Prestation::class);
        $proposerRepo = $this->entityManager->getRepository(Proposer::class);
        $updated = 0;

        foreach ($items as $item) {
            $prestationId = (int) ($item['id'] ?? $item['prestationId'] ?? 0);
            $prix = $item['prix'] ?? null;

            if ($prestationId <= 0 || $prix === null || $prix === '') {
                continue;
            }

            $prestation = $prestationRepo->find($prestationId);
            if ($prestation === null) {
                continue;
            }

            $prixNormalise = number_format((float) $prix, 2, '.', '');
            $existing = $proposerRepo->findOneBy([
                'garage' => $garage,
                'prestation' => $prestation,
            ]);

            if ($existing instanceof Proposer) {
                $existing->setPrix($prixNormalise);
            } else {
                $proposer = new Proposer();
                $proposer->setGarage($garage);
                $proposer->setPrestation($prestation);
                $proposer->setPrix($prixNormalise);
                $this->entityManager->persist($proposer);
            }

            $updated++;
        }

        if ($updated === 0) {
            return $this->json([
                'error' => 'Aucune prestation valide à enregistrer',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Prestation associee au garage',
            'updated' => $updated,
        ], Response::HTTP_CREATED);
    }

    // Ici on vire une prestation du garage, et de la base si plus utilisee.
    #[Route('/api/v1/prestations/{id}', name: 'prestations_delete', methods: ['DELETE'])]
    public function supprimerPrestation(int $id, Request $request): JsonResponse
    { 
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $prestation = $this->entityManager->getRepository(Prestation::class)->find($id);
        if ($prestation === null || !$garage->getPrestations()->contains($prestation)) {
            return $this->json(['error' => 'Prestation introuvable pour ce garage'], Response::HTTP_NOT_FOUND);
        }

        try {
            $garage->removePrestation($prestation);
            if ($prestation->getGarages()->isEmpty()) {
                $this->entityManager->remove($prestation);
            }
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Suppression impossible (prestation referencee)',
                'details' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'message' => 'Prestation supprimee',
            'prestation_id' => $id,
        ]);
    }

    // Ici on liste tous les rendez-vous du garage, tries par date.
    #[Route('/api/v1/rdv', name: 'rdv_list', methods: ['GET'])]
    public function listerRdv(Request $request): JsonResponse
    { //       
        $garage = $this->resoudreGarage($request);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }
//     on recupere tous les rendez-vous du garage tries par date de debut.
        $rdvList = $this->entityManager->getRepository(RendezVous::class)->findBy(
            ['garage' => $garage],
            ['dateDebut' => 'ASC']
        );

        return $this->json([
            'garage_id' => $garage->getIdGarage(),
            'rdv' => array_map(fn (RendezVous $rdv) => $this->serialiserRdv($rdv), $rdvList),
        ]);
    }

    // Créer un nouveau rendez-vous
    #[Route('/api/v1/rdv', name: 'rdv_create', methods: ['POST'])]
    public function creerRdv(Request $request): JsonResponse
    {
        $garage = $this->resoudreGarage($request);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->recupererPayload($request);

        // Validate required fields
        if (!isset($payload['vehiculeId'], $payload['dateDebut'], $payload['dateFin'])) {
            return $this->json([
                'error' => 'Required fields: vehiculeId, dateDebut, dateFin'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Get vehicule and verify it belongs to the garage
        $vehicule = $this->entityManager->getRepository(Vehicule::class)->find((int) $payload['vehiculeId']);
        if ($vehicule === null) {
            return $this->json(['error' => 'Véhicule introuvable'], Response::HTTP_BAD_REQUEST);
        }

        // Parse dates
        $dateDebut = $this->parseClientDateTime(isset($payload['dateDebut']) ? (string) $payload['dateDebut'] : null);
        $dateFin = $this->parseClientDateTime(isset($payload['dateFin']) ? (string) $payload['dateFin'] : null);
        if ($dateDebut === null || $dateFin === null) {
            return $this->json(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
        }

        if ($dateFin <= $dateDebut) {
            return $this->json(['error' => 'La date de fin doit être après la date de début'], Response::HTTP_BAD_REQUEST);
        }

        // Get status (default to "En attente")
        $defaultStatus = $this->entityManager->getRepository(StatusRdv::class)->findOneBy(['libStatusRdv' => 'En attente']);
        $status = $defaultStatus;
        if (isset($payload['statusId'])) {
            $status = $this->entityManager->getRepository(StatusRdv::class)->find((int) $payload['statusId']);
            if ($status === null) {
                return $this->json(['error' => 'StatusRdv introuvable'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Create RDV
        $rdv = new RendezVous();
        $rdv->setDateDebut($dateDebut);
        $rdv->setDateFin($dateFin);
        $rdv->setMotifRefus($payload['motifRefus'] ?? '');
        $rdv->setCommantaireClient($payload['commentaire'] ?? '');
        $rdv->setVehicule($vehicule);
        $rdv->setGarage($garage);
        $rdv->setStatusRdv($status);

        $this->entityManager->persist($rdv);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Rendez-vous créé avec succès',
            'rdv' => $this->serialiserRdv($rdv)
        ], Response::HTTP_CREATED);
    }

    // Ici on affiche le detail complet d'un rendez-vous.
    #[Route('/api/v1/rdv/{id}', name: 'rdv_show', methods: ['GET'])]
    public function afficherRdv(int $id): JsonResponse
    { //       on recupere le rdv demande, sinon on retourne une erreur.
        $rdv = $this->entityManager->getRepository(RendezVous::class)->find($id);
        if ($rdv === null) {
            return $this->json(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'rdv' => $this->serialiserRdv($rdv),
            'prestations' => $this->trouverPrestationsPourRdv($id),
            'historique' => array_map(
                static fn (Historique $historique) => [
                    'idHistorique' => $historique->getIdHistorique(),
                    'dateIntervention' => $historique->getDateIntervention()->format('Y-m-d'),
                    'compteRendu' => $historique->getCompteRendu(),
                ],
                $rdv->getHistoriques()->toArray()
            ),
        ]);
    }

    // Ici on recupere juste les prestations liees a un rendez-vous.
    #[Route('/api/v1/rdv/{id}/prestations', name: 'rdv_prestations_show', methods: ['GET'])]
    public function afficherPrestationsRdv(int $id): JsonResponse
    { //       on recupere le rdv demande, sinon on retourne une erreur.
        $rdv = $this->entityManager->getRepository(RendezVous::class)->find($id);
        if ($rdv === null) {
            return $this->json(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'rdv_id' => $id,
            'prestations' => $this->trouverPrestationsPourRdv($id),
        ]);
    }

    // Ici on gere le statut d'un rendez-vous (accepte, refuse, etc.).
    #[Route('/api/v1/rdv/{id}/gestion', name: 'rdv_manage', methods: ['PATCH'])]
    public function gererRdv(int $id, Request $request): JsonResponse
    { //       on recupere le rdv demande, sinon on retourne une erreur.
        $rdv = $this->entityManager->getRepository(RendezVous::class)->find($id);
        if ($rdv === null) {
            return $this->json(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
        }

        $ancienStatut = $rdv->getStatusRdv()?->getLibStatusRdv();

        $payload = $this->recupererPayload($request);
        if (isset($payload['statusId'])) {
            $status = $this->entityManager->getRepository(StatusRdv::class)->find((int) $payload['statusId']);
            if ($status === null) {
                return $this->json(['error' => 'StatusRdv introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $rdv->setStatusRdv($status);
        }

        if (isset($payload['statusLabel'])) {
            $status = $this->trouverOuCreerStatusRdv((string) $payload['statusLabel']);
            $rdv->setStatusRdv($status);
        }

        if (isset($payload['motifRefus'])) {
            $rdv->setMotifRefus((string) $payload['motifRefus']);
        }

        $this->entityManager->flush();

        $mailResult = $this->notifierClientChangementStatut($rdv, $ancienStatut, $rdv->getStatusRdv()?->getLibStatusRdv(), $payload['motifRefus'] ?? null);

        return $this->json([
            'message' => 'Rendez-vous mis a jour',
            'rdv_id' => $id,
            'rdv' => $this->serialiserRdv($rdv),
            'mail' => $mailResult,
        ]);
    }

    // Ici on ajoute le compte-rendu d'intervention dans l'historique client.
    #[Route('/api/v1/rdv/{id}/historique', name: 'rdv_historique_complete', methods: ['POST'])]
    public function completerHistoriqueClient(int $id, Request $request): JsonResponse
    {
        $rdv = $this->entityManager->getRepository(RendezVous::class)->find($id);
        if ($rdv === null) {
            return $this->json(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
        }
 //    on recupere le compte-rendu et la date d'intervention dans le payload, et on verifie que le compte-rendu est present.
        $payload = $this->recupererPayload($request);
        if (!isset($payload['compteRendu'])) {
            return $this->json(['error' => 'Champ requis: compteRendu'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($payload['vehiculeId'])) {
            $vehicule = $this->entityManager->getRepository(Vehicule::class)->find((int) $payload['vehiculeId']);
            if ($vehicule === null) {
                return $this->json(['error' => 'Vehicule introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $rdv->setVehicule($vehicule);
        }

        $historique = new Historique();
        $historique->setRdv($rdv);
        $historique->setDateIntervention(
            isset($payload['dateIntervention'])
                ? new \DateTimeImmutable((string) $payload['dateIntervention'])
                : new \DateTimeImmutable('today')
        );
 //     on construit le compte-rendu en concatenant le champ compteRendu du payload avec les infos de vehicule si elles sont presentes.
        $compteRendu = (string) $payload['compteRendu'];
        if (isset($payload['vehiculeIntervention'])) {
            $compteRendu .= "\nVehicule intervention: " . (string) $payload['vehiculeIntervention'];
        }
        $historique->setCompteRendu($compteRendu);

        $this->entityManager->persist($historique);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Historique client complete',
            'rdv_id' => $id,
            'historique' => [
                'idHistorique' => $historique->getIdHistorique(),
                'dateIntervention' => $historique->getDateIntervention()->format('Y-m-d'),
                'compteRendu' => $historique->getCompteRendu(),
                'vehicule' => $rdv->getVehicule()?->getImatriculationVehicule(),
            ],
        ]);
    }

    //  on lit le JSON si present, sinon on prend les params classiques.
    private function recupererPayload(Request $request): array
    {
        $content = trim((string) $request->getContent());
        if ($content === '') {
            return $request->request->all();
        }

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return $request->request->all();
    }

    //  on retrouve le garage via garageId explicite, sinon via l'utilisateur connecte.
    private function resoudreGarage(Request $request, array $payload = []): ?Garage
    {
        $garageId = $payload['garageId'] ?? $request->query->get('garageId');
        $userId = $payload['userId'] ?? $request->query->get('userId');
        $repository = $this->entityManager->getRepository(Garage::class);

        if ($garageId !== null) {
            return $repository->find((int) $garageId);
        }

        if ($userId !== null) {
            $user = $this->entityManager->getRepository(Utilisateur::class)->find((int) $userId);
            if ($user !== null) {
                $garage = $repository->findOneBy(['utilisateur' => $user]);
                if ($garage !== null) {
                    return $garage;
                }
            }
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof Utilisateur) {
            $userGarage = $repository->findOneBy(['utilisateur' => $currentUser]);
            if ($userGarage !== null) {
                return $userGarage;
            }

            // Fallback pour les donnees historiques ou l'association utilisateur->garage est manquante.
            return $repository->findOneBy(['emailGarage' => $currentUser->getEmailUtilisateur()]);
        }

        return null;
    }

    //  on transforme une heure HH:MM en objet DateTime compatible avec Doctrine type "time".
    private function parserHeure(string $value): ?\DateTime
    {
        $time = \DateTime::createFromFormat('H:i', $value);
        return $time ?: null;
    }



    //  on formate un horaire pour l'envoyer au front.
   private function serialiserHoraire(Horaire $horaire): array
    {
        return [
            'idHoraire' => $horaire->getIdHoraire(),
            'hreOuvreMatin' => $horaire->getHreOuvreMatin()?->format('H:i'),
            'hreFermeMatin' => $horaire->getHreFermeMatin()?->format('H:i'),
            'hreOuvreSoir' => $horaire->getHreOuvreSoir()?->format('H:i'),
            'hreFermeSoir' => $horaire->getHreFermeSoir()?->format('H:i'),
        ];
    }


    //  on formate un garage pour l'envoyer au front.
    private function serialiserGarage(Garage $garage): array
    {
        return [
            'idGarage' => $garage->getIdGarage(),
            'nomGarage' => $garage->getNomGarage(),
            'emailGarage' => $garage->getEmailGarage(),
            'telephoneGarage' => $garage->getTelephoneGarage(),
            'adresseGarage' => $garage->getAdresseGarage(),
            'isValide' => $garage->getIsValide(),
        ];
    }

    //  on formate un rendez-vous avec ses infos client/vehicule.
    private function serialiserRdv(RendezVous $rdv): array
    {
        $vehicule = $rdv->getVehicule();
        $client = $vehicule?->getClient();

        return [
            'idRdv' => $rdv->getIdRdv(),
            'dateDebut' => $rdv->getDateDebut()->format('Y-m-d H:i:s'),
            'dateFin' => $rdv->getDateFin()->format('Y-m-d H:i:s'),
            'status' => $rdv->getStatusRdv()?->getLibStatusRdv(),
            'motifRefus' => $rdv->getMotifRefus(),
            'commentaireClient' => $rdv->getCommantaireClient(),
            'vehicule' => [
                'idVehicule' => $vehicule?->getIdVehicule(),
                'immatriculation' => $vehicule?->getImatriculationVehicule(),
                'annee' => $vehicule?->getAnneeVehicule(),
                'marque' => $vehicule?->getMarque()?->getNomMarque(),
            ],
            'client' => [
                'idClient' => $client?->getIdClient(),
                'nom' => $client?->getNomClient(),
                'prenom' => $client?->getPrenomClient(),
                'telephone' => $client?->getTelephoneClient(),
            ],
        ];
    }

    //  route de validation d'un garage par un super admin pour affichage.
    #[Route('/api/v1/garages/{id}/validation', name: 'garage_valider', methods: ['PATCH'])]
    public function validerGarage(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->json([
                'error' => 'Acces refuse. ROLE_SUPER_ADMIN requis.',
            ], Response::HTTP_FORBIDDEN);
        }

        $garage = $this->entityManager->getRepository(Garage::class)->find($id);
        if ($garage === null) {
            return $this->json([
                'error' => 'Garage introuvable',
            ], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->recupererPayload($request);
        $isValide = isset($payload['isValide']) ? (bool) $payload['isValide'] : true;

        $garage->setIsValide($isValide);
        $this->entityManager->flush();

        return $this->json([
            'message' => $isValide
                ? 'Garage valide pour affichage'
                : 'Garage retire de l\'affichage',
            'garage' => [
                'idGarage' => $garage->getIdGarage(),
                'nomGarage' => $garage->getNomGarage(),
                'isValide' => $garage->getIsValide(),
            ],
        ]);
    }

    //  on va chercher les prestations rattachees a un rendez-vous.
    private function trouverPrestationsPourRdv(int $rdvId): array
    {
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        if (!in_array('lier', $schemaManager->listTableNames(), true)) {
            return [];
        }

        try {
            return $connection->createQueryBuilder()
                ->select('p.id_prestation', 'p.nom_prestation', 'p.description_prestation', 'p.duree_prestation')
                ->from('lier', 'l')
                ->innerJoin('l', 'prestation', 'p', 'p.id_prestation = l.id_prestation')
                ->where('l.id_rdv = :rdvId')
                ->setParameter('rdvId', $rdvId)
                ->orderBy('p.nom_prestation', 'ASC')
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (\Throwable) {
            return [];
        }
    }
    // verification si le garage existe avec le numero siret 
   #[Route('//api/v1/check_garage/{siret}', name: '/api_check_garage', methods: ['GET'])]
    public function checkGarage(string $siret, GarageRepository $repo): JsonResponse
    {
        $garage = $repo->findOneBy(['siret' => $siret]);

        return $this->json([
            'exists' => $garage ? true : false
        ]);
    }

    #[Route('//api/v1/garages/search', name: '/api_garages_search', methods: ['GET'])]
    public function rechercherGarages(Request $request): JsonResponse
    {
        $ville = trim((string) $request->query->get('ville', ''));
        $prestationId = $request->query->get('prestationId');
        $onlyValidated = filter_var($request->query->get('onlyValidated', '0'), FILTER_VALIDATE_BOOLEAN);

        $qb = $this->entityManager
            ->getRepository(Garage::class)
            ->createQueryBuilder('g')
            ->leftJoin('g.ville', 'v')
            ->addSelect('v');

        if ($onlyValidated) {
            $qb
                ->andWhere('g.isValide = :isValide')
                ->setParameter('isValide', true);
        }

        if ($ville !== '') {
            $normalizedVille = mb_strtolower($ville);
            $qb
                ->andWhere('LOWER(v.nomVille) LIKE :ville OR v.codePostal LIKE :codePostal OR LOWER(g.nomGarage) LIKE :garageName OR LOWER(g.adresseGarage) LIKE :garageAddress')
                ->setParameter('ville', '%' . $normalizedVille . '%')
                ->setParameter('codePostal', '%' . $ville . '%')
                ->setParameter('garageName', '%' . $normalizedVille . '%')
                ->setParameter('garageAddress', '%' . $normalizedVille . '%');
        }

        if ($prestationId !== null && $prestationId !== '') {
            $qb
                ->innerJoin('g.proposers', 'pr')
                ->innerJoin('pr.prestation', 'fp')
                ->andWhere('fp.idPrestation = :prestationId')
                ->setParameter('prestationId', (int) $prestationId);
        }

        $garages = $qb
            ->orderBy('g.nomGarage', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->json([
            'filters' => [
                'ville' => $ville,
                'prestationId' => $prestationId !== null && $prestationId !== '' ? (int) $prestationId : null,
                'onlyValidated' => $onlyValidated,
            ],
            'count' => count($garages),
            'garages' => array_map(fn (Garage $garage) => $this->serialiserGarageRecherche($garage), $garages),
        ]);
    }

    private function serialiserGarageRecherche(Garage $garage): array
    {
        $ville = $garage->getVille();

        return [
            'idGarage' => $garage->getIdGarage(),
            'nomGarage' => $garage->getNomGarage(),
            'telephoneGarage' => $garage->getTelephoneGarage(),
            'emailGarage' => $garage->getEmailGarage(),
            'adresseGarage' => $garage->getAdresseGarage(),
            'ville' => [
                'idVille' => $ville?->getIdVille(),
                'nomVille' => $ville?->getNomVille(),
                'codePostal' => $ville?->getCodePostal(),
            ],
            'prestations' => array_map(
                static fn (Prestation $prestation) => [
                    'idPrestation' => $prestation->getIdPrestation(),
                    'nomPrestation' => $prestation->getNomPrestation(),
                    'dureePrestation' => $prestation->getDureePrestation(),
                ],
                $garage->getPrestations()->toArray()
            ),
        ];
    }

    private function trouverOuCreerStatusRdv(string $label): StatusRdv
    {
        $label = trim($label);
        $status = $this->entityManager->getRepository(StatusRdv::class)->findOneBy([
            'libStatusRdv' => $label,
        ]);

        if ($status instanceof StatusRdv) {
            return $status;
        }

        $status = new StatusRdv();
        $status->setLibStatusRdv($label);
        $this->entityManager->persist($status);

        return $status;
    }

    private function notifierClientChangementStatut(RendezVous $rdv, ?string $ancienStatut, ?string $nouveauStatut, ?string $motifRefus = null): array
    {
        $vehicule = $rdv->getVehicule();
        $client = $vehicule?->getClient();
        $utilisateur = $client?->getUtilisateur();
        $email = $utilisateur?->getEmailUtilisateur();

        if (!$email) {
            return [
                'sent' => false,
                'reason' => 'email_client_introuvable',
            ];
        }

        $status = trim((string) $nouveauStatut);
        $label = $this->normaliserStatut($status);
        $vehicleName = trim(($vehicule?->getMarque()?->getNomMarque() ?? '') . ' ' . ($vehicule?->getModele()?->getNomModele() ?? ''));
        $clientName = trim(($client?->getPrenomClient() ?? '') . ' ' . ($client?->getNomClient() ?? ''));
        $dateDebut = $rdv->getDateDebut()?->format('d/m/Y à H:i');
        $dateFin = $rdv->getDateFin()?->format('d/m/Y à H:i');
        $plate = $vehicule?->getImatriculationVehicule() ?? '-';

        $title = match ($label) {
            'en attente' => 'Votre rendez-vous est en attente de validation',
            'confirmer' => 'Votre rendez-vous est confirmé',
            'en cours' => 'Votre véhicule est en cours de réparation',
            'reparation terminee' => 'Votre réparation est terminée',
            'refuser' => 'Votre rendez-vous a été refusé',
            'termine', 'terminee' => 'Votre rendez-vous est terminé',
            default => 'Mise à jour de votre rendez-vous',
        };

        $message = match ($label) {
            'en attente' => 'Nous avons bien reçu votre demande. Elle est en attente de validation par le garage.',
            'confirmer' => 'Votre rendez-vous est confirmé. Le garage prépare votre passage.',
            'en cours' => 'Votre véhicule est actuellement en cours de réparation.',
            'reparation terminee' => 'La réparation est terminée. Vous pouvez venir récupérer votre voiture quand vous le souhaitez.',
            'refuser' => 'Votre rendez-vous a été refusé par le garage.',
            'termine', 'terminee' => 'Le rendez-vous est terminé.',
            default => 'Le statut de votre rendez-vous a été mis à jour.',
        };

        $extra = '';
        if ($label === 'refuser' && $motifRefus) {
            $extra = '<p><strong>Motif :</strong> ' . htmlspecialchars((string) $motifRefus, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $html = sprintf(
            '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a">'
            . '<h2 style="margin:0 0 12px">%s</h2>'
            . '<p>%s</p>'
            . '<p><strong>Client :</strong> %s</p>'
            . '<p><strong>Véhicule :</strong> %s</p>'
            . '<p><strong>Plaque :</strong> %s</p>'
            . '<p><strong>Marque / Modèle :</strong> %s</p>'
            . '<p><strong>Date début :</strong> %s</p>'
            . '<p><strong>Date fin :</strong> %s</p>'
            . '<p><strong>Ancien statut :</strong> %s</p>'
            . '<p><strong>Nouveau statut :</strong> %s</p>'
            . '%s'
            . '</div>',
            htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($clientName !== '' ? $clientName : '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($vehicleName !== '' ? $vehicleName : '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($plate, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($vehicleName !== '' ? $vehicleName : '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($dateDebut ?? '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($dateFin ?? '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($ancienStatut ?? '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($status !== '' ? $status : '-', ENT_QUOTES, 'UTF-8'),
            $extra
        );

        try {
            $this->mailerService->sendEmail($email, $title, $html);

            return [
                'sent' => true,
                'to' => $email,
            ];
        } catch (\Throwable $e) {
            return [
                'sent' => false,
                'to' => $email,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function normaliserStatut(string $status): string
    {
        $status = trim(mb_strtolower($status));

        return str_replace(['é', 'è', 'ê', 'à', 'ç', 'ô', 'î', 'ï', ' '], ['e', 'e', 'e', 'a', 'c', 'o', 'i', 'i', ' '], $status);
    }
    
}
