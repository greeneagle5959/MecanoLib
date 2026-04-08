<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\StatusRdv;
use App\Repository\StatusRdvRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class StatusRdvController extends AbstractController
{
	// methode pour recuperer tous les statuts RDV
	#[Route('/api/v1/get_status_rdvs', name: 'api_get_status_rdvs', methods: ['GET'])]
	public function getAll(StatusRdvRepository $statusRdvRepository): JsonResponse
	{
		$statusRdvs = $statusRdvRepository->findAll();

		$result = array_map(
			static fn (StatusRdv $statusRdv): array => [
				'id_status_rdv' => $statusRdv->getIdStatusRdv(),
				'lib_status_rdv' => $statusRdv->getLibStatusRdv(),
			],
			$statusRdvs
		);

		return $this->json($result);
	}

	// methode pour recuperer un statut RDV
	#[Route('/api/v1/get_status_rdv/{id}', name: 'api_get_status_rdv', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, StatusRdvRepository $statusRdvRepository): JsonResponse
	{
		$statusRdv = $statusRdvRepository->find($id);

		if (!$statusRdv) {
			return $this->json(['message' => 'Status RDV introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json([
			'id_status_rdv' => $statusRdv->getIdStatusRdv(),
			'lib_status_rdv' => $statusRdv->getLibStatusRdv(),
		]);
	}

	// methode pour ajouter un statut RDV
	#[Route('/api/v1/new_status_rdv', name: 'api_new_status_rdv', methods: ['POST'])]
	public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$libStatusRdv = trim((string) ($data['lib_status_rdv'] ?? $data['libStatusRdv'] ?? ''));
		if ($libStatusRdv === '') {
			return $this->json(['message' => 'Le libelle du status RDV est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$statusRdv = new StatusRdv();
		$statusRdv->setLibStatusRdv($libStatusRdv);

		$entityManager->persist($statusRdv);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le status RDV a ete ajoute avec succes.',
			'id_status_rdv' => $statusRdv->getIdStatusRdv(),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier un statut RDV
	#[Route('/api/v1/edit_status_rdv/{id}', name: 'api_edit_status_rdv', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(Request $request, StatusRdv $statusRdv, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$libStatusRdv = trim((string) ($data['lib_status_rdv'] ?? $data['libStatusRdv'] ?? ''));
		if ($libStatusRdv === '') {
			return $this->json(['message' => 'Le libelle du status RDV est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$statusRdv->setLibStatusRdv($libStatusRdv);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le status RDV a ete modifie avec succes.',
		]);
	}

	// methode pour supprimer un statut RDV
	#[Route('/api/v1/delete_status_rdv/{id}', name: 'api_delete_status_rdv', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(StatusRdv $statusRdv, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($statusRdv);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le status RDV a ete supprime avec succes.',
		]);
	}
    // methode pour changer le status d'un RDV
    #[Route('/api/v1/changer_status', name: 'api_changer_status', methods: ['POST'])]
public function changerStatus(Request $request, EntityManagerInterface $manager): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    $rdv = $manager->getRepository(RendezVous::class)->find($data['id_rdv']);
    $status = $manager->getRepository(StatusRdv::class)->find($data['id_status_rdv']);

    if (!$rdv || !$status) {
        return $this->json(['message' => 'Introuvable'], 400);
    }

    $rdv->setStatusRdv($status);
    $manager->flush(); // Important !

    return $this->json([
        'message' => 'Statut modifié',
        'id_rdv' => $rdv->getIdRdv(),
        'status' => $status->getLibStatusRdv()
    ]);
}
}
