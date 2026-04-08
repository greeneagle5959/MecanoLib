<?php

namespace App\Controller;

use App\Entity\Garage;
use App\Entity\Lier;
use App\Entity\Prestation;
use App\Entity\RendezVous;
use App\Entity\StatusRdv;
use App\Entity\Vehicule;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RendezVousController extends AbstractController
{
    // RendezVousController gère la création, modification et consultation des rendez-vous entre les clients et les garages, ainsi que le changement de statut des RDV.
    #[Route('/api/v1/creer_rdv', name: 'api_creer_rdv', methods: ['POST'])]
    public function createRdv(Request $request, EntityManagerInterface $manager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $garage = $manager->getRepository(Garage::class)->find($data['id_garage'] ?? 0);
        $vehicule = $manager->getRepository(Vehicule::class)->find($data['id_vehicule'] ?? 0);
        $prestation = $manager->getRepository(Prestation::class)->find($data['id_prestation'] ?? 0);

        if (!$garage || !$vehicule || !$prestation) {
            return $this->json([
                'message' => 'Garage, véhicule ou prestation introuvable'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer le statut
        $statusEnAttente = $manager->getRepository(StatusRdv::class)
            ->findOneBy(['libStatusRdv' => 'En attente']);

        if (!$statusEnAttente) {
            return $this->json([
                'message' => 'Statut "En attente" introuvable en base'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
        $rdv = new RendezVous();
        $rdv->setGarage($garage)
            ->setVehicule($vehicule)
            ->setDateDebut(new \DateTime($data['date_debut']))
            ->setDateFin(new \DateTime($data['date_fin']))
            ->setCommantaireClient($data['commantaire_client'] ?? '')
            ->setMotifRefus($data['motif_refus'] ?? '')
            ->setStatusRdv($statusEnAttente);

        $manager->persist($rdv);

        // Lier la prestation au RDV
        $lier = new Lier();
        $lier->setRdv($rdv)
            ->setPrestation($prestation);

        $manager->persist($lier);
        $manager->flush();

        return $this->json([
            'message' => 'Rendez-vous créé avec succès',
            'id_rdv' => $rdv->getIdRdv()
        ]);
    }
#[Route('/api/v1/rdv/{id}', name: 'api_delete_rdv', methods: ['DELETE'])]
public function deleteRdv(int $id, EntityManagerInterface $entityManager): JsonResponse
{
    $rdv = $entityManager->getRepository(RendezVous::class)->find($id);

    if (!$rdv) {
        return $this->json(['message' => 'Rendez-vous introuvable'], JsonResponse::HTTP_NOT_FOUND);
    }

    $entityManager->remove($rdv);
    $entityManager->flush();

    return $this->json(['message' => 'Rendez-vous annulé avec succès']);
}
    //le methode pour changer status RDV
    #[Route('/api/v1/changer_status', name: 'api_changer_status', methods: ['POST'])]
    public function changerStatus(Request $request, EntityManagerInterface $manager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $rdv = $manager->getRepository(RendezVous::class)
            ->find($data['id_rdv']);

        $status = $manager->getRepository(StatusRdv::class)
            ->find($data['id_status_rdv']);

        if (!$rdv) {
            return $this->json([
                'message' => 'Rendez-vous introuvable'
            ], 400);
        }

        if (!$status) {
            return $this->json([
                'message' => 'Status introuvable'
            ], 400);
        }

        $rdv->setStatusRdv($status);

        $manager->flush();

        return $this->json([
            'message' => 'Statut modifié',
            'id_rdv' => $rdv->getIdRdv(),
            'status' => $status->getLibStatusRdv()
        ]);
    }
    // methode pour modifier le RDV
    #[Route('/api/v1/modifier_rdv', methods:['POST'])]
    public function modifierRdv(Request $request, EntityManagerInterface $manager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $rdv = $manager->getRepository(RendezVous::class)
            ->find($data['id_rdv']);

        if(!$rdv){
            return $this->json([
                'message' => 'Rendez-vous introuvable'
            ],400);
        }

        $rdv->setDateDebut(new \DateTime($data['date_debut']));
        $rdv->setDateFin(new \DateTime($data['date_fin']));

        $manager->flush();

        return $this->json([
            'message' => 'Rendez-vous modifié'
        ]);
    }
    // afficher les RDV en fonction du garage

#[Route('/api/v1/garage/{idGarage}/rdvs', name: 'garage_rdvs', methods: ['GET'])]
public function getGarageRdvs(int $idGarage, EntityManagerInterface $manager): JsonResponse
{
    $qb = $manager->createQueryBuilder();

    $rdvs = $qb
        ->select('rdv', 'garage', 'vehicule', 'statusRdv', 'lier', 'prestation', 'client')
        ->from('App\Entity\RendezVous', 'rdv')
        ->leftJoin('rdv.garage', 'garage')
        ->leftJoin('rdv.vehicule', 'vehicule')
        ->leftJoin('rdv.statusRdv', 'statusRdv')
        ->leftJoin('rdv.liers', 'lier')
        ->leftJoin('lier.prestation', 'prestation')
        ->leftJoin('vehicule.client', 'client')
        ->where('garage.idGarage = :idGarage')
        ->setParameter('idGarage', $idGarage)
        ->orderBy('rdv.dateDebut', 'ASC')
        ->getQuery()
        ->getResult();

    $data = [];
    foreach ($rdvs as $rdv) {
        $client = $rdv->getVehicule()?->getClient();
        $vehicule = $rdv->getVehicule();
        $lier = $rdv->getLiers()->first();

        $data[] = [
            'id_rdv' => $rdv->getIdRdv(),
            'date_debut' => $rdv->getDateDebut()?->format('Y-m-d H:i'),
            'id_status_rdv' => $rdv->getStatusRdv()?->getIdStatusRdv(),
            'lib_status_rdv' => $rdv->getStatusRdv()?->getLibStatusRdv(),
            'id_garage' => $rdv->getGarage()?->getIdGarage(),
            // Client
            'client' => $client ? [
                'prenom' => $client->getPrenomClient(),
                'nom' => $client->getNomClient(),
            ] : null,
            // Véhicule - CORRIGÉ avec le bon nom de méthode
            'vehicule' => $vehicule ? [
                'immatriculation' => $vehicule->getImatriculationVehicule(), // ou getImatriculation()
                'marque' => $vehicule->getMarque(),
            ] : null,
            // Prestation
            'prestation' => $lier ? [
                'nom_prestation' => $lier->getPrestation()?->getNomPrestation(),
            ] : null,
        ];
    }

    return new JsonResponse($data);
}
#[Route('/api/v1/client/{idClient}/rdvs', name: 'client_rdvs', methods: ['GET'])]
public function getClientRdvs(int $idClient, EntityManagerInterface $manager): JsonResponse
{
    $qb = $manager->createQueryBuilder();

    $rdvs = $qb
        ->select('rdv', 'garage', 'vehicule', 'statusRdv', 'lier', 'prestation', 'client')
        ->from('App\Entity\RendezVous', 'rdv')
        ->leftJoin('rdv.garage', 'garage')
        ->leftJoin('rdv.vehicule', 'vehicule')
        ->leftJoin('rdv.statusRdv', 'statusRdv')  // IMPORTANT : joindre le statut
        ->leftJoin('rdv.liers', 'lier')
        ->leftJoin('lier.prestation', 'prestation')
        ->leftJoin('vehicule.client', 'client')
        ->where('client.idClient = :idClient')  // ou vehicule.client = :idClient
        ->setParameter('idClient', $idClient)
        ->orderBy('rdv.dateDebut', 'DESC')
        ->getQuery()
        ->getResult();

    $data = [];
    foreach ($rdvs as $rdv) {
        $client = $rdv->getVehicule()?->getClient();
        $vehicule = $rdv->getVehicule();
        $lier = $rdv->getLiers()->first();
        $status = $rdv->getStatusRdv();  // Récupérer le statut

        $data[] = [
            'id_rdv' => $rdv->getIdRdv(),
            'date_debut' => $rdv->getDateDebut()?->format('Y-m-d H:i'),
            'id_status_rdv' => $status?->getIdStatusRdv(),
            'status' => [  // IMPORTANT : inclure le statut
                'id' => $status?->getIdStatusRdv(),
                'libStatusRdv' => $status?->getLibStatusRdv(),
                'lib_status_rdv' => $status?->getLibStatusRdv(),
            ],
            'garage' => [
                'nom_garage' => $rdv->getGarage()?->getNomGarage(),
            ],
            'prestation' => [
                'nom_prestation' => $lier?->getPrestation()?->getNomPrestation(),
            ],
            'vehicule' => [
                'immatriculation' => $vehicule?->getImatriculationVehicule(),
            ],
        ];
    }

    return new JsonResponse($data);
}
}
