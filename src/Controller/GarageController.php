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
use App\Entity\Ville;
use App\Repository\GarageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'app_garage_')]
final class GarageController extends AbstractController
{
    //  On recupere l'EntityManager une fois pour tout le controleur.
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }



    // Ici on renvoie le profil du garage demande.
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

    // Ici on met a jour les infos de base du profil garage.
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
            $ville = $this->entityManager->getRepository(\App\Entity\Ville::class)->find((int) $payload['villeId']);
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

    // Ici on enregistre les horaires d'ouverture et de fermeture du garage.
    #[Route('/api/v1/horaires/ouvertures-fermetures', name: 'horaires_ouvertures-fermetures', methods: ['PATCH'])]
    public function mettreAJourOuverturesFermetures(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }
//        on verifie que les champs horaires sont tous presents dans le payload.
        foreach (['hreOuvreMatin', 'hreFermeMatin', 'hreOuvreSoir', 'hreFermeSoir'] as $field) {
            if (!isset($payload[$field])) {
                return $this->json(['error' => sprintf('Champ requis: %s (format HH:MM)', $field)], Response::HTTP_BAD_REQUEST);
            }
        }
//        on cherche un horaire existant pour ce garage, sinon on en cree un nouveau.
        $horaire = null;
        if (isset($payload['horaireId'])) {
            $horaire = $this->entityManager->getRepository(Horaire::class)->find((int) $payload['horaireId']);
            if ($horaire !== null && $horaire->getGarage()?->getIdGarage() !== $garage->getIdGarage()) {
                return $this->json(['error' => 'Horaire introuvable pour ce garage'], Response::HTTP_NOT_FOUND);
            }
        }
//        si aucun horaire trouve, on en cree un nouveau et on l'associe au garage.
        if ($horaire === null) {
            $horaire = new Horaire();
            $horaire->setGarage($garage);
            $this->entityManager->persist($horaire);
        }

        $ouvreMatin = $this->parserHeure((string) $payload['hreOuvreMatin']);
        $fermeMatin = $this->parserHeure((string) $payload['hreFermeMatin']);
        $ouvreSoir = $this->parserHeure((string) $payload['hreOuvreSoir']);
        $fermeSoir = $this->parserHeure((string) $payload['hreFermeSoir']);
//        on verifie que les heures sont valides.
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

    // Ici on branche les jours de la semaine avec les bons horaires.
    #[Route('api/v1/planning/semaine', name: 'planning_semaine_update', methods: ['PUT', 'PATCH'])]
    public function mettreAJourPlanningSemaine(Request $request): JsonResponse
    { //       on recupere le garage et le planning du payload, puis on verifie que tout est bien forme.
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }
//       on verifie que le planning est present et bien forme dans le payload.
        $planning = $payload['planning'] ?? null;
        if (!is_array($planning)) {
            return $this->json([
                'error' => 'Le payload doit contenir planning: [{jourId, horaireId}]',
            ], Response::HTTP_BAD_REQUEST);
        }
//       on boucle sur chaque entree du planning pour verifier que les jours et horaires existent et sont associes.
        foreach ($planning as $item) {
            if (!is_array($item) || !isset($item['jourId'], $item['horaireId'])) {
                return $this->json(['error' => 'Chaque entree doit avoir jourId et horaireId'], Response::HTTP_BAD_REQUEST);
            }
//         on verifie que le jour et l'horaire existent en base, et que l'horaire est bien associe a ce garage.
            $jour = $this->entityManager->getRepository(Jour::class)->find((int) $item['jourId']);
            $horaire = $this->entityManager->getRepository(Horaire::class)->find((int) $item['horaireId']);
            if ($jour === null || $horaire === null) {
                return $this->json(['error' => 'Jour ou horaire introuvable'], Response::HTTP_BAD_REQUEST);
            } //         on verifie que l'horaire appartient bien au garage avant de l'associer au jour.
            if ($horaire->getGarage()?->getIdGarage() !== $garage->getIdGarage()) {
                return $this->json(['error' => 'Horaire non associe a ce garage'], Response::HTTP_BAD_REQUEST);
            }
//         on vire les anciennes associations du jour avant d'en creer une nouvelle.
            foreach ($jour->getAssociers() as $associer) {
                $this->entityManager->remove($associer);
            }

            $associer = new Associer();
            $associer->setJour($jour);
            $associer->setHoraire($horaire);
            $this->entityManager->persist($associer);
        }
//       on flush une fois a la fin pour optimiser les requetes.
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Planning de la semaine mis a jour',
            'planning' => $planning,
        ]);
    }

    // Ici on ajoute une nouvelle prestation au garage.
    #[Route('app/v1/prestations', name: 'prestations_add', methods: ['POST'])]
    public function ajouterPrestation(Request $request): JsonResponse
    {
        $payload = $this->recupererPayload($request);
        $garage = $this->resoudreGarage($request, $payload);
        if ($garage === null) {
            return $this->json(['error' => 'Garage introuvable'], Response::HTTP_NOT_FOUND);
        }
//       on verifie que tous les champs requis sont presents dans le payload.
        foreach (['nomPrestation', 'descriptionPrestation', 'dureePrestation', 'categoriePrestation', 'categorieId'] as $field) {
            if (!isset($payload[$field])) {
                return $this->json(['error' => sprintf('Champ requis: %s', $field)], Response::HTTP_BAD_REQUEST);
            }
        }
//       on verifie que la categorie existe en base avant de creer la prestation.
        $categorie = $this->entityManager->getRepository(Categorie::class)->find((int) $payload['categorieId']);
        if ($categorie === null) {
            return $this->json(['error' => 'Categorie introuvable'], Response::HTTP_BAD_REQUEST);
        }
//       on cree la prestation et on l'associe au garage avant de la persister en base.
        $prestation = new Prestation();
        $prestation
            ->setNomPrestation((string) $payload['nomPrestation'])
            ->setDescriptionPrestation((string) $payload['descriptionPrestation'])
            ->setDureePrestation((string) $payload['dureePrestation'])
            ->setCategoriePrestation((string) $payload['categoriePrestation'])
            ->setCategorie($categorie);

        $garage->addPrestation($prestation);
        $this->entityManager->persist($prestation);
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

    // Ici on vire une prestation du garage, et de la base si plus utilisee.
    #[Route('app/v1/prestations/{id}', name: 'prestations_delete', methods: ['DELETE'])]
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
    #[Route('api/v1/rdv', name: 'rdv_list', methods: ['GET'])]
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

    // Ici on affiche le detail complet d'un rendez-vous.
    #[Route('app/v1/rdv/{id}', name: 'rdv_show', methods: ['GET'])]
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
    #[Route('app/v1/rdv/{id}/prestations', name: 'rdv_prestations_show', methods: ['GET'])]
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
    #[Route('app/v1/rdv/{id}/gestion', name: 'rdv_manage', methods: ['PATCH'])]
    public function gererRdv(int $id, Request $request): JsonResponse
    { //       on recupere le rdv demande, sinon on retourne une erreur.
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

    // Ici on ajoute le compte-rendu d'intervention dans l'historique client.
    #[Route('app/v1/rdv/{id}/historique', name: 'rdv_historique_complete', methods: ['POST'])]
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

    //  on retrouve le garage via garageId, sinon on prend le premier.
    private function resoudreGarage(Request $request, array $payload = []): ?Garage
    {
        $garageId = $payload['garageId'] ?? $request->query->get('garageId');
        $repository = $this->entityManager->getRepository(Garage::class);
        if ($garageId !== null) {
            return $repository->find((int) $garageId);
        }

        return $repository->findOneBy([]);
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
            'hreOuvreMatin' => $horaire->getHreOuvreMatin()->format('H:i'),
            'hreFermeMatin' => $horaire->getHreFermeMatin()->format('H:i'),
            'hreOuvreSoir' => $horaire->getHreOuvreSoir()->format('H:i'),
            'hreFermeSoir' => $horaire->getHreFermeSoir()->format('H:i'),
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
    #[Route('app/v1/garages/{id}/validation', name: 'garage_valider', methods: ['PATCH'])]
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
    #[Route('api/v1/check_garage/{siret}', name: 'api_check_garage', methods: ['GET'])]
    public function checkGarage(string $siret, GarageRepository $repo): JsonResponse
    {
        $garage = $repo->findOneBy(['siret' => $siret]);
        return $this->json(['exists' => $garage ? true : false]);
    }

}
