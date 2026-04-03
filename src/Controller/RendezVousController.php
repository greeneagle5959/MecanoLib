<?php

namespace App\Controller;

use App\Entity\Garage;
use App\Entity\Lier;
use App\Entity\Prestation;
use App\Entity\RendezVous;
use App\Entity\StatusRdv;
use App\Entity\Vehicule;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RendezVousController extends AbstractController
{
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
    public function getGarageRdvs(int $idGarage, RendezVousRepository $rdvRepository): JsonResponse
    {
        // Récupérer les RDV liés au garage
        
        $rdvs = $rdvRepository->findBy(['garage' => $idGarage]);

        // Retourner en JSON
        $data = array_map(fn($rdv) => [
            'id_rdv' => $rdv->getIdRdv(),
            'date_debut' => $rdv->getDateDebut()->format('Y-m-d H:i'),
            'date_fin' => $rdv->getDateFin()->format('Y-m-d H:i'),
            'motif_refus' => $rdv->getMotifRefus(),
            'id_status_rdv' => $rdv->getStatusRdv()?->getIdStatusRdv(),
            'id_garage' => $rdv->getGarage()?->getIdGarage(),
        ], $rdvs);

        return new JsonResponse($data);
    }
    #[Route('/api/v1/client/{idClient}/rdvs', name: 'client_rdvs', methods: ['GET'])]
public function getClientRdvs(int $idClient, RendezVousRepository $rdvRepository): JsonResponse
{
    $rdvs = $rdvRepository->findRdvByClient($idClient);

    if (!$rdvs) {
        return $this->json([]);
    }

    $data = array_map(function ($rdv) {
        return [
            'id_rdv' => $rdv['id_rdv'],
            'date_debut' => $rdv['date_debut']->format('d/m/Y'),
            'heure_debut' => $rdv['date_debut']->format('H:i'),
            'date_fin' => $rdv['date_fin']->format('d/m/Y'),
            'heure_fin' => $rdv['date_fin']->format('H:i'),
            'garage' => $rdv['garage'],
            'immatriculation' => $rdv['immatriculation'],
            'prestation' => $rdv['prestation'],
            'status' => $rdv['status'] ?? 'En attente', // afficher le statut défini par le garage
        ];
    }, $rdvs);

    return $this->json($data);
}

}