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
    #[Route('/api/v1/creer_rdv', name: 'api_creer_rdv', methods: ['POST'])]
    public function createRdv(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $garage = $em->getRepository(Garage::class)->find($data['id_garage'] ?? 0);
        $vehicule = $em->getRepository(Vehicule::class)->find($data['id_vehicule'] ?? 0);
        $prestation = $em->getRepository(Prestation::class)->find($data['id_prestation'] ?? 0);

        if (!$garage || !$vehicule || !$prestation) {
            return $this->json([
                'message' => 'Garage, véhicule ou prestation introuvable'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer le statut "En attente"
        $statusEnAttente = $em->getRepository(StatusRdv::class)
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

        $em->persist($rdv);

        // Lier la prestation au RDV
        $lier = new Lier();
        $lier->setRdv($rdv)
            ->setPrestation($prestation);

        $em->persist($lier);
        $em->flush();

        return $this->json([
            'message' => 'Rendez-vous créé avec succès',
            'id_rdv' => $rdv->getIdRdv()
        ]);
    }
}