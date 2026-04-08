<?php

namespace App\Controller;

use App\Entity\Historique;
use App\Repository\GarageRepository;
use App\Repository\HistoriqueRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HistoriqueController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // methode pour recuperer tous les historiques
    #[Route('/api/v1/get_historiques', name: 'api_get_historiques', methods: ['GET'])]
    public function getAll(HistoriqueRepository $historiqueRepository): JsonResponse
    {
        $historiques = $historiqueRepository->findAll();

        $result = array_map(
            fn (Historique $historique): array => $this->formatHistorique($historique),
            $historiques
        );

        return $this->json($result);
    }

    // methode pour recuperer les historiques d'un garage
    #[Route('/api/v1/get_historiques_by_garage/{idGarage}', name: 'api_get_historiques_by_garage', methods: ['GET'], requirements: ['idGarage' => '\d+'])]
    public function getByGarage(int $idGarage, GarageRepository $garageRepository): JsonResponse
    {
        $garage = $garageRepository->find($idGarage);
        if (!$garage) {
            return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer les historiques liés aux RDV du garage
        $qb = $this->entityManager->createQueryBuilder();
        $historiques = $qb
            ->select('h', 'r')
            ->from(Historique::class, 'h')
            ->leftJoin('h.rdv', 'r')
            ->where('r.garage = :garage')
            ->setParameter('garage', $garage)
            ->orderBy('h.dateIntervention', 'DESC')
            ->getQuery()
            ->getResult();

        $result = array_map(
            fn (Historique $historique): array => $this->formatHistorique($historique),
            $historiques
        );

        return $this->json($result);
    }

    // methode pour recuperer les historiques d'un rendez-vous
    #[Route('/api/v1/get_historiques_by_rdv/{idRdv}', name: 'api_get_historiques_by_rdv', methods: ['GET'], requirements: ['idRdv' => '\d+'])]
    public function getByRdv(int $idRdv, RendezVousRepository $rendezVousRepository, HistoriqueRepository $historiqueRepository): JsonResponse
    {
        $rdv = $rendezVousRepository->find($idRdv);
        if (!$rdv) {
            return $this->json(['message' => 'Rendez-vous introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $historiques = $historiqueRepository->findBy(['rdv' => $rdv]);
        $result = array_map(
            fn (Historique $historique): array => $this->formatHistorique($historique),
            $historiques
        );

        return $this->json($result);
    }

    // methode pour recuperer un historique
    #[Route('/api/v1/get_historique/{id}', name: 'api_get_historique', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, HistoriqueRepository $historiqueRepository): JsonResponse
    {
        $historique = $historiqueRepository->find($id);
        if (!$historique) {
            return $this->json(['message' => 'Historique introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($this->formatHistorique($historique));
    }

    // methode pour ajouter un historique
    #[Route('/api/v1/new_historique', name: 'api_new_historique', methods: ['POST'])]
    public function create(
        Request $request,
        RendezVousRepository $rendezVousRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dateIntervention = trim((string) ($data['date_intervention'] ?? $data['dateIntervention'] ?? ''));
        $compteRendu = trim((string) ($data['compte_rendu'] ?? $data['compteRendu'] ?? ''));
        $idRdv = (int) ($data['id_rdv'] ?? $data['idRdv'] ?? 0);

        if ($dateIntervention === '' || $compteRendu === '' || $idRdv <= 0) {
            return $this->json(['message' => 'date_intervention, compte_rendu et id_rdv sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $date = \DateTime::createFromFormat('Y-m-d', $dateIntervention);
        if (!$date) {
            return $this->json(['message' => 'date_intervention invalide. Format attendu: YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $rdv = $rendezVousRepository->find($idRdv);
        if (!$rdv) {
            return $this->json(['message' => 'Rendez-vous introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $historique = new Historique();
        $historique->setDateIntervention($date);
        $historique->setCompteRendu($compteRendu);
        $historique->setRdv($rdv);

        $entityManager->persist($historique);
        $entityManager->flush();

        return $this->json([
            'message' => 'L historique a ete ajoute avec succes.',
            'historique' => $this->formatHistorique($historique),
        ], JsonResponse::HTTP_CREATED);
    }

    // methode pour modifier un historique
    #[Route('/api/v1/edit_historique/{id}', name: 'api_edit_historique', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        HistoriqueRepository $historiqueRepository,
        RendezVousRepository $rendezVousRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $historique = $historiqueRepository->find($id);
        if (!$historique) {
            return $this->json(['message' => 'Historique introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (isset($data['date_intervention']) || isset($data['dateIntervention'])) {
            $dateIntervention = trim((string) ($data['date_intervention'] ?? $data['dateIntervention']));
            $date = \DateTime::createFromFormat('Y-m-d', $dateIntervention);
            if (!$date) {
                return $this->json(['message' => 'date_intervention invalide. Format attendu: YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $historique->setDateIntervention($date);
        }

        if (isset($data['compte_rendu']) || isset($data['compteRendu'])) {
            $compteRendu = trim((string) ($data['compte_rendu'] ?? $data['compteRendu']));
            if ($compteRendu === '') {
                return $this->json(['message' => 'compte_rendu ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $historique->setCompteRendu($compteRendu);
        }

        if (isset($data['id_rdv']) || isset($data['idRdv'])) {
            $idRdv = (int) ($data['id_rdv'] ?? $data['idRdv']);
            if ($idRdv <= 0) {
                return $this->json(['message' => 'id_rdv invalide.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $rdv = $rendezVousRepository->find($idRdv);
            if (!$rdv) {
                return $this->json(['message' => 'Rendez-vous introuvable.'], JsonResponse::HTTP_NOT_FOUND);
            }

            $historique->setRdv($rdv);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'L historique a ete modifie avec succes.',
            'historique' => $this->formatHistorique($historique),
        ]);
    }

    // methode pour supprimer un historique
    #[Route('/api/v1/delete_historique/{id}', name: 'api_delete_historique', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, HistoriqueRepository $historiqueRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $historique = $historiqueRepository->find($id);
        if (!$historique) {
            return $this->json(['message' => 'Historique introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($historique);
        $entityManager->flush();

        return $this->json([
            'message' => 'L historique a ete supprime avec succes.',
        ]);
    }

    private function formatHistorique(Historique $historique): array
    {
        return [
            'id_historique' => $historique->getIdHistorique(),
            'date_intervention' => $historique->getDateIntervention()->format('Y-m-d'),
            'compte_rendu' => $historique->getCompteRendu(),
            'rdv' => [
                'id_rdv' => $historique->getRdv()?->getIdRdv(),
                'date_debut' => $historique->getRdv()?->getDateDebut()->format('Y-m-d H:i:s'),
                'date_fin' => $historique->getRdv()?->getDateFin()->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
