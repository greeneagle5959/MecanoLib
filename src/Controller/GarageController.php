<?php

namespace App\Controller;

use App\Entity\Associer;
use App\Entity\Categorie;
use App\Entity\Garage;
use App\Entity\Historique;
use App\Entity\Horaire;
use App\Entity\Jour;
use App\Entity\Prestation;
use App\Entity\RendezVous;
use App\Entity\StatusRdv;
use App\Entity\Vehicule;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Repository\AssocierRepository;
use App\Repository\GarageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'app_garage_')]
final class GarageController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('api/v1/profil', name: 'profil_show', methods: ['GET'])]
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

    #[Route('api/v1/profil', name: 'profil_update', methods: ['PUT', 'PATCH'])]
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

    #[Route('api/v1/profil', name: 'profil_delete', methods: ['DELETE'])]
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

    #[Route('/api/v1/horaires/ouvertures-fermetures', name: 'horaires_ouvertures-fermetures', methods: ['PATCH'])]
    public function mettreAJourOuverturesFermetures(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        foreach (['hreOuvreMatin', 'hreFermeMatin', 'hreOuvreSoir', 'hreFermeSoir'] as $field) {
            if (!isset($payload[$field])) {
                return $this->json(['error' => sprintf('Champ requis: %s (format HH:MM)', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        $requestedHoraireId = $payload['horaireId'] ?? $payload['idHoraire'] ?? $payload['id_horaire'] ?? null;
        $horaire = null;

        if ($requestedHoraireId !== null) {
            $horaire = $this->entityManager->getRepository(Horaire::class)->find((int) $requestedHoraireId);
            if ($horaire !== null && $horaire->getGarage()?->getIdGarage() !== $garage->getIdGarage()) {
                return $this->json(['error' => 'Horaire introuvable pour ce garage'], Response::HTTP_NOT_FOUND);
            }
        }

        if ($horaire === null) {
            $horaire = $this->entityManager->getRepository(Horaire::class)->findOneBy(
                ['garage' => $garage],
                ['idHoraire' => 'DESC']
            );
        }

        if ($horaire === null) {
            $horaire = new Horaire();
            $horaire->setGarage($garage);
            $this->entityManager->persist($horaire);
        }

        $ouvreMatin = $this->parserHeure((string) $payload['hreOuvreMatin']);
        $fermeMatin = $this->parserHeure((string) $payload['hreFermeMatin']);
        $ouvreSoir = $this->parserHeure((string) $payload['hreOuvreSoir']);
        $fermeSoir = $this->parserHeure((string) $payload['hreFermeSoir']);

        if ($ouvreMatin === null || $fermeMatin === null || $ouvreSoir === null || $fermeSoir === null) {
            return $this->json(['error' => 'Format heure invalide. Utiliser HH:MM'], Response::HTTP_BAD_REQUEST);
        }

        $horaire
            ->setHreOuvreMatin($ouvreMatin)
            ->setHreFermeMatin($fermeMatin)
            ->setHreOuvreSoir($ouvreSoir)
            ->setHreFermeSoir($fermeSoir);

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Ouvertures/fermetures mises a jour',
            'horaire' => $this->serialiserHoraire($horaire),
        ]);
    }

    #[Route('api/v1/planning/semaine', name: 'planning_semaine_update', methods: ['PUT', 'PATCH'])]
    public function mettreAJourPlanningSemaine(
        Request $request,
        AssocierRepository $associerRepository
    ): JsonResponse {
        $data = $this->recupererPayload($request);

        $garageId = $data['idGarage'] ?? $data['id_garage'] ?? $data['garageId'] ?? null;
        $planning = $data['planning'] ?? $data['semaine'] ?? $data['semainePlanning'] ?? null;

        if (!$garageId || !is_array($planning)) {
            return $this->json([
                'success' => false,
                'message' => 'Donnees invalides.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $garage = $this->entityManager->getRepository(Garage::class)->find((int) $garageId);
        if ($garage === null) {
            return $this->json([
                'success' => false,
                'message' => 'Garage introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        $jourRepository = $this->entityManager->getRepository(Jour::class);

        foreach ($planning as $item) {
            if (!is_array($item)) {
                continue;
            }

            $jourId = $item['jourId'] ?? $item['idJour'] ?? $item['id_jour'] ?? null;
            if (!$jourId) {
                continue;
            }

            $jour = $jourRepository->find((int) $jourId);
            if ($jour === null) {
                continue;
            }

            $associationExistante = null;
            foreach ($associerRepository->findBy(['jour' => $jour]) as $associer) {
                if ($associer->getHoraire()?->getGarage()?->getIdGarage() === $garage->getIdGarage()) {
                    $associationExistante = $associer;
                    break;
                }
            }

            $horaireId = $item['horaireId'] ?? $item['idHoraire'] ?? $item['id_horaire'] ?? null;
            $horaire = null;

            if ($horaireId) {
                $horaire = $this->entityManager->getRepository(Horaire::class)->find((int) $horaireId);
                if ($horaire !== null && $horaire->getGarage()?->getIdGarage() !== $garage->getIdGarage()) {
                    $horaire = null;
                }
            }

            if ($horaire === null && $associationExistante !== null) {
                $horaire = $associationExistante->getHoraire();
            }

            $hreOuvreMatin = $item['hreOuvreMatin'] ?? $item['hre_ouvre_matin'] ?? '08:00';
            $hreFermeMatin = $item['hreFermeMatin'] ?? $item['hre_ferme_matin'] ?? '12:00';
            $hreOuvreSoir = $item['hreOuvreSoir'] ?? $item['hre_ouvre_soir'] ?? '14:00';
            $hreFermeSoir = $item['hreFermeSoir'] ?? $item['hre_ferme_soir'] ?? '18:00';

            $ouvreMatin = $this->parserHeure((string) $hreOuvreMatin);
            $fermeMatin = $this->parserHeure((string) $hreFermeMatin);
            $ouvreSoir = $this->parserHeure((string) $hreOuvreSoir);
            $fermeSoir = $this->parserHeure((string) $hreFermeSoir);

            if ($ouvreMatin === null || $fermeMatin === null || $ouvreSoir === null || $fermeSoir === null) {
                continue;
            }

            $mustCreateDedicatedHoraire = $horaire === null;

            if ($horaire !== null) {
                $sameHours =
                    $horaire->getHreOuvreMatin()?->format('H:i') === $ouvreMatin->format('H:i') &&
                    $horaire->getHreFermeMatin()?->format('H:i') === $fermeMatin->format('H:i') &&
                    $horaire->getHreOuvreSoir()?->format('H:i') === $ouvreSoir->format('H:i') &&
                    $horaire->getHreFermeSoir()?->format('H:i') === $fermeSoir->format('H:i');

                $isShared = count($associerRepository->findBy(['horaire' => $horaire])) > 1;

                if ($isShared && !$sameHours) {
                    $mustCreateDedicatedHoraire = true;
                }
            }

            if ($mustCreateDedicatedHoraire) {
                $horaire = new Horaire();
                $horaire->setGarage($garage);
                $this->entityManager->persist($horaire);
            }

            $horaire
                ->setHreOuvreMatin($ouvreMatin)
                ->setHreFermeMatin($fermeMatin)
                ->setHreOuvreSoir($ouvreSoir)
                ->setHreFermeSoir($fermeSoir);

            if ($associationExistante !== null) {
                if ($associationExistante->getHoraire() !== $horaire) {
                    $associationExistante->setHoraire($horaire);
                }
                continue;
            }

            $association = new Associer();
            $association->setJour($jour);
            $association->setHoraire($horaire);
            $this->entityManager->persist($association);
        }

        $this->entityManager->flush();
        $this->supprimerHorairesOrphelinsGarage($garage, $associerRepository);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Planning mis a jour avec succes.',
            'planning' => $planning,
        ]);
    }

    #[Route('app/v1/prestations', name: 'prestations_add', methods: ['POST'])]
    public function ajouterPrestation(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        foreach (['nomPrestation', 'descriptionPrestation', 'dureePrestation', 'categorieId'] as $field) {
            if (!isset($payload[$field])) {
                return $this->json(['error' => sprintf('Champ requis: %s', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        $categorie = $this->entityManager->getRepository(Categorie::class)->find((int) $payload['categorieId']);
        if ($categorie === null) {
            return $this->json(['error' => 'Categorie introuvable'], Response::HTTP_BAD_REQUEST);
        }

        $prestation = new Prestation();
        $prestation
            ->setNomPrestation((string) $payload['nomPrestation'])
            ->setDescriptionPrestation((string) $payload['descriptionPrestation'])
            ->setDureePrestation((string) $payload['dureePrestation'])
            ->setCategorie($categorie);

        $this->entityManager->persist($prestation);

        $proposer = new \App\Entity\Proposer();
        $proposer->setGarage($garage);
        $proposer->setPrestation($prestation);
        if (isset($payload['prix'])) {
            $proposer->setPrix((float) $payload['prix']);
        }
        $this->entityManager->persist($proposer);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Prestation ajoutee',
            'prestation' => [
                'id' => $prestation->getIdPrestation(),
                'nomPrestation' => $prestation->getNomPrestation(),
                'categorie' => $prestation->getCategorie()->getNomCategorie(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('app/v1/prestations/{id}', name: 'prestations_delete', methods: ['DELETE'])]
    public function supprimerPrestation(int $id, Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $prestation = $this->entityManager->getRepository(Prestation::class)->find($id);
        $proposer = $prestation ? $this->entityManager->getRepository(\App\Entity\Proposer::class)->findOneBy([
            'garage' => $garage,
            'prestation' => $prestation,
        ]) : null;

        if ($prestation === null || $proposer === null) {
            return $this->json(['error' => 'Prestation introuvable pour ce garage'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($proposer);
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

    #[Route('api/v1/rdv', name: 'rdv_list', methods: ['GET'])]
    public function listerRdv(Request $request): JsonResponse
    {
        $garage = $this->resoudreGarage($request);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }

        $qb = $this->entityManager->createQueryBuilder();

        $rdvList = $qb
            ->select('rdv', 'client', 'vehicule', 'prestation', 'status')
            ->from(RendezVous::class, 'rdv')
            ->leftJoin('rdv.client', 'client')
            ->leftJoin('rdv.vehicule', 'vehicule')
            ->leftJoin('rdv.prestations', 'prestation')
            ->leftJoin('rdv.status', 'status')
            ->where('rdv.garage = :garage')
            ->setParameter('garage', $garage)
            ->orderBy('rdv.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->json([
            'garage_id' => $garage->getIdGarage(),
            'rdv' => array_map(fn (RendezVous $rdv) => $this->serialiserRdv($rdv), $rdvList),
        ]);
    }

    #[Route('app/v1/rdv/{id}', name: 'rdv_show', methods: ['GET'])]
    public function afficherRdv(int $id): JsonResponse
    {
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

    #[Route('app/v1/rdv/{id}/prestations', name: 'rdv_prestations_show', methods: ['GET'])]
    public function afficherPrestationsRdv(int $id): JsonResponse
    {
        $rdv = $this->entityManager->getRepository(RendezVous::class)->find($id);
        if ($rdv === null) {
            return $this->json(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'rdv_id' => $id,
            'prestations' => $this->trouverPrestationsPourRdv($id),
        ]);
    }

    #[Route('app/v1/rdv/{id}/gestion', name: 'rdv_manage', methods: ['PATCH'])]
    public function gererRdv(int $id, Request $request): JsonResponse
    {
        $rdv = $this->entityManager->getRepository(RendezVous::class)->find($id);
        if ($rdv === null) {
            return $this->json(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->recupererPayload($request);

        if (isset($payload['statusId'])) {
            $status = $this->entityManager->getRepository(StatusRdv::class)->find((int) $payload['statusId']);
            if ($status === null) {
                return $this->json(['error' => 'StatusRdv introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $rdv->setStatusRdv($status);
        }

        if (isset($payload['statusLabel'])) {
            $status = $this->entityManager->getRepository(StatusRdv::class)->findOneBy([
                'libStatusRdv' => (string) $payload['statusLabel'],
            ]);
            if ($status === null) {
                return $this->json(['error' => 'StatusRdv introuvable pour ce libelle'], Response::HTTP_BAD_REQUEST);
            }
            $rdv->setStatusRdv($status);
        }

        if (isset($payload['motifRefus'])) {
            $rdv->setMotifRefus((string) $payload['motifRefus']);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Rendez-vous mis a jour',
            'rdv_id' => $id,
            'rdv' => $this->serialiserRdv($rdv),
        ]);
    }

    #[Route('app/v1/rdv/{id}/historique', name: 'rdv_historique_complete', methods: ['POST'])]
    public function completerHistoriqueClient(int $id, Request $request): JsonResponse
    {
        $rdv = $this->entityManager->getRepository(RendezVous::class)->find($id);
        if ($rdv === null) {
            return $this->json(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
        }

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

    private function resoudreGarage(Request $request, array $payload = []): ?Garage
    {
        $garageId = $payload['garageId']
            ?? $payload['idGarage']
            ?? $payload['id_garage']
            ?? $request->query->get('garageId')
            ?? $request->query->get('idGarage')
            ?? $request->query->get('id_garage');

        $repository = $this->entityManager->getRepository(Garage::class);

        if ($garageId !== null && $garageId !== '') {
            return $repository->find((int) $garageId);
        }

        $utilisateur = $this->getUser();
        if ($utilisateur instanceof \App\Entity\Utilisateur) {
            return $repository->findOneBy(
                ['utilisateur' => $utilisateur],
                ['idGarage' => 'DESC']
            );
        }

        return null;
    }

    private function parserHeure(string $value): ?\DateTime
    {
        $time = \DateTime::createFromFormat('H:i', $value);
        return $time ?: null;
    }

    private function serialiserHoraire(Horaire $horaire): array
    {
        return [
            'idHoraire' => $horaire->getIdHoraire(),
            'hreOuvreMatin' => $horaire->getHreOuvreMatin()->format('H:i'),
            'hreFermeMatin' => $horaire->getHreFermeMatin()->format('H:i'),
            'hreOuvreSoir' => $horaire->getHreOuvreSoir()->format('H:i'),
            'hreFermeSoir' => $horaire->getHreFermeSoir()->format('H:i'),
        ];
    }

    private function serialiserGarage(Garage $garage): array
    {
        return [
            'idGarage' => $garage->getIdGarage(),
            'id_garage' => $garage->getIdGarage(),
            'nomGarage' => $garage->getNomGarage(),
            'nom_garage' => $garage->getNomGarage(),
            'emailGarage' => $garage->getEmailGarage(),
            'email_garage' => $garage->getEmailGarage(),
            'telephoneGarage' => $garage->getTelephoneGarage(),
            'telephone_garage' => $garage->getTelephoneGarage(),
            'adresseGarage' => $garage->getAdresseGarage(),
            'adresse_garage' => $garage->getAdresseGarage(),
            'isValide' => $garage->getIsValide(),
            'is_valide' => $garage->getIsValide(),
        ];
    }

    private function hasBackOfficeAccess(): bool
    {
        return $this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted('ROLE_ADMIN');
    }

    private function serialiserRdv(RendezVous $rdv): array
    {
        $vehicule = $rdv->getVehicule();

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
            // Client (via le véhicule)
            'client' => ($client = $rdv->getVehicule()?->getClient()) ? [
                'prenom' => $client->getPrenomClient(),
                'nom' => $client->getNomClient(),
                'email' => $client->getUtilisateur()?->getEmailUtilisateur(),
                'telephone' => $client->getTelephoneClient(),
            ] : null,
        ];
    }

    #[Route('/api/v1/garages', name: 'garages_liste', methods: ['GET'])]
    public function listerGarages(Request $request): JsonResponse
    {
        if (!$this->hasBackOfficeAccess()) {
            return $this->json([
                'success' => false,
                'error' => 'Acces refuse. ROLE_ADMIN ou ROLE_SUPER_ADMIN requis.',
            ], Response::HTTP_FORBIDDEN);
        }

        $pendingOnly = filter_var($request->query->get('pending', '0'), FILTER_VALIDATE_BOOL);

        $criteria = [];
        if ($pendingOnly) {
            $criteria['isValide'] = false;
        }

        $garages = $this->entityManager->getRepository(Garage::class)->findBy($criteria, ['idGarage' => 'DESC']);
        $serializedGarages = array_map(fn (Garage $garage) => $this->serialiserGarage($garage), $garages);

        return $this->json([
            'success' => true,
            'count' => count($serializedGarages),
            'pending' => $pendingOnly,
            'garages' => $serializedGarages,
            'pendingGarages' => $pendingOnly ? $serializedGarages : [],
        ]);
    }

    #[Route('/api/v1/garages/{id}/validation', name: 'api_garage_valider', methods: ['PATCH'])]
    #[Route('/app/v1/garages/{id}/validation', name: 'garage_valider', methods: ['PATCH'])]
    public function validerGarage(int $id, Request $request): JsonResponse
    {
        if (!$this->hasBackOfficeAccess()) {
            return $this->json([
                'success' => false,
                'error' => 'Acces refuse. ROLE_ADMIN ou ROLE_SUPER_ADMIN requis.',
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

    #[Route('/api/v1/garages/init_planning_all', name: 'garages_init_planning_all', methods: ['POST'])]
    public function initialiserPlanningTousLesGarages(AssocierRepository $associerRepository): JsonResponse
    {
        $garageRepository = $this->entityManager->getRepository(Garage::class);
        $jourRepository = $this->entityManager->getRepository(Jour::class);
        $joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $resume = [];

        foreach ($garageRepository->findBy([], ['idGarage' => 'ASC']) as $garage) {
            $createdForGarage = 0;

            foreach ($joursSemaine as $libJour) {
                $jour = $jourRepository->findOneBy(['libJour' => $libJour]);

                if ($jour === null) {
                    $jour = new Jour();
                    $jour->setLibJour($libJour);
                    $this->entityManager->persist($jour);
                    $this->entityManager->flush();
                }

                $associationExistante = $this->trouverAssociationGarageJour($garage, $jour, $associerRepository);
                $horaireExistant = $associationExistante?->getHoraire();
                $isShared = $horaireExistant !== null && count($associerRepository->findBy(['horaire' => $horaireExistant])) > 1;

                if ($associationExistante !== null && !$isShared) {
                    continue;
                }

                [$ouvreMatin, $fermeMatin, $ouvreSoir, $fermeSoir] = $this->determinerHorairesParDefaut($garage, $libJour, $associerRepository);

                $nouveauHoraire = new Horaire();
                $nouveauHoraire->setGarage($garage);
                $nouveauHoraire->setHreOuvreMatin($ouvreMatin);
                $nouveauHoraire->setHreFermeMatin($fermeMatin);
                $nouveauHoraire->setHreOuvreSoir($ouvreSoir);
                $nouveauHoraire->setHreFermeSoir($fermeSoir);
                $this->entityManager->persist($nouveauHoraire);

                if ($associationExistante !== null) {
                    $associationExistante->setHoraire($nouveauHoraire);
                } else {
                    $association = new Associer();
                    $association->setJour($jour);
                    $association->setHoraire($nouveauHoraire);
                    $this->entityManager->persist($association);
                }

                ++$createdForGarage;
            }

            $resume[] = [
                'id_garage' => $garage->getIdGarage(),
                'nom_garage' => $garage->getNomGarage(),
                'horaires_crees_ou_dedies' => $createdForGarage,
            ];
        }

        $this->entityManager->flush();

        foreach ($garageRepository->findBy([], ['idGarage' => 'ASC']) as $garage) {
            $this->supprimerHorairesOrphelinsGarage($garage, $associerRepository);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Tous les garages disposent maintenant de 7 jours et d\'horaires modifiables independamment.',
            'garages' => $resume,
        ]);
    }

    private function trouverAssociationGarageJour(Garage $garage, Jour $jour, AssocierRepository $associerRepository): ?Associer
    {
        foreach ($associerRepository->findBy(['jour' => $jour]) as $associer) {
            if ($associer->getHoraire()?->getGarage()?->getIdGarage() === $garage->getIdGarage()) {
                return $associer;
            }
        }

        return null;
    }

    private function supprimerHorairesOrphelinsGarage(Garage $garage, AssocierRepository $associerRepository): void
    {
        $horaireRepository = $this->entityManager->getRepository(Horaire::class);

        foreach ($horaireRepository->findBy(['garage' => $garage]) as $horaire) {
            if (count($associerRepository->findBy(['horaire' => $horaire])) === 0) {
                $this->entityManager->remove($horaire);
            }
        }
    }

    private function determinerHorairesParDefaut(Garage $garage, string $libJour, AssocierRepository $associerRepository): array
    {
        $horaireRepository = $this->entityManager->getRepository(Horaire::class);
        $jourRepository = $this->entityManager->getRepository(Jour::class);

        $horaireTemplate = $horaireRepository->findOneBy(['garage' => $garage], ['idHoraire' => 'DESC']);

        if ($horaireTemplate === null && $garage->getIdGarage() !== 2) {
            $garageModele = $this->entityManager->getRepository(Garage::class)->find(2);
            $jourModele = $jourRepository->findOneBy(['libJour' => $libJour]);

            if ($garageModele !== null && $jourModele !== null) {
                $associationModele = $this->trouverAssociationGarageJour($garageModele, $jourModele, $associerRepository);
                $horaireTemplate = $associationModele?->getHoraire();
            }
        }

        $ouvreMatin = $horaireTemplate?->getHreOuvreMatin()?->format('H:i') ?? '08:30';
        $fermeMatin = $horaireTemplate?->getHreFermeMatin()?->format('H:i') ?? '12:00';
        $ouvreSoir = $horaireTemplate?->getHreOuvreSoir()?->format('H:i') ?? '14:00';
        $fermeSoir = $horaireTemplate?->getHreFermeSoir()?->format('H:i') ?? '17:30';

        return [
            $this->parserHeure($ouvreMatin) ?? new \DateTime('08:30'),
            $this->parserHeure($fermeMatin) ?? new \DateTime('12:00'),
            $this->parserHeure($ouvreSoir) ?? new \DateTime('14:00'),
            $this->parserHeure($fermeSoir) ?? new \DateTime('17:30'),
        ];
    }

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

    #[Route('api/v1/profil/change-password', name: 'profil_change_password', methods: ['POST'])]
    public function changerMotDePasse(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return $this->json(['error' => 'Utilisateur non authentifie'], Response::HTTP_UNAUTHORIZED);
        }

        $currentPassword = $payload['currentPassword'] ?? $payload['ancienMdp'] ?? '';
        $newPassword = $payload['newPassword'] ?? $payload['mdp'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            return $this->json(['error' => 'Mots de passe requis'], Response::HTTP_BAD_REQUEST);
        }

        // Debug
        $ancienHash = $utilisateur->getMdpUtilisateur();
        error_log('Ancien hash: ' . substr($ancienHash, 0, 20) . '...');
        error_log('Verification ancien mdp: ' . ($passwordHasher->isPasswordValid($utilisateur, $currentPassword) ? 'OK' : 'ECHEC'));

        if (!$passwordHasher->isPasswordValid($utilisateur, $currentPassword)) {
            return $this->json(['error' => 'Mot de passe actuel incorrect'], Response::HTTP_BAD_REQUEST);
        }

        // Hasher le nouveau
        $nouveauHash = $passwordHasher->hashPassword($utilisateur, $newPassword);
        error_log('Nouveau hash: ' . substr($nouveauHash, 0, 20) . '...');
        
        $utilisateur->setMdpUtilisateur($nouveauHash);
        $this->entityManager->flush();

        // Vérifier que c'est bien sauvegardé
        $this->entityManager->refresh($utilisateur);
        error_log('Hash après flush: ' . substr($utilisateur->getMdpUtilisateur(), 0, 20) . '...');

        return $this->json([
            'message' => 'Mot de passe mis a jour',
            'debug' => [
                'ancien_prefix' => substr($ancienHash, 0, 10),
                'nouveau_prefix' => substr($nouveauHash, 0, 10),
            ]
        ]);
    }

    #[Route('api/v1/check_garage/{siret}', name: 'api_check_garage', methods: ['GET'])]
    public function checkGarage(string $siret, GarageRepository $repo): JsonResponse
    {
        $garage = $repo->findOneBy(['siret' => $siret]);
        return $this->json(['exists' => $garage ? true : false]);
    }
}
