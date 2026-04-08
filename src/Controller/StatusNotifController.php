<?php

namespace App\Controller;

use App\Entity\Notifications;
use App\Entity\StatusNotif;
use App\Repository\StatusNotifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class StatusNotifController extends AbstractController
{
	// methode pour recuperer tous les statuts de notification
	#[Route('/api/v1/get_status_notifs', name: 'api_get_status_notifs', methods: ['GET'])]
	public function getAll(StatusNotifRepository $statusNotifRepository): JsonResponse
	{
		$statusNotifs = $statusNotifRepository->findAll();

		$result = array_map(
			fn (StatusNotif $statusNotif): array => $this->formatStatusNotif($statusNotif),
			$statusNotifs
		);

		return $this->json($result);
	}

	// methode pour recuperer un statut de notification
	#[Route('/api/v1/get_status_notif/{id}', name: 'api_get_status_notif', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, StatusNotifRepository $statusNotifRepository): JsonResponse
	{
		$statusNotif = $statusNotifRepository->find($id);
		if (!$statusNotif) {
			return $this->json(['message' => 'Status notification introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatStatusNotif($statusNotif));
	}

	// methode pour recuperer les notifications d'un statut
	#[Route('/api/v1/get_notifications_status_notif/{id}', name: 'api_get_notifications_status_notif', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function getNotificationsByStatus(int $id, StatusNotifRepository $statusNotifRepository): JsonResponse
	{
		$statusNotif = $statusNotifRepository->find($id);
		if (!$statusNotif) {
			return $this->json(['message' => 'Status notification introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$notifications = array_map(
			static fn (Notifications $notification): array => [
				'id_notifications' => $notification->getIdNotifications(),
				'contenue' => $notification->getContenue(),
				'date_envoi' => $notification->getDateEnvoi()->format('Y-m-d H:i:s'),
				'id_rdv' => $notification->getRdv()?->getIdRdv(),
			],
			$statusNotif->getNotificationsList()->toArray()
		);

		return $this->json([
			'id_status_notif' => $statusNotif->getIdStatusNotif(),
			'lib_status_notif' => $statusNotif->getLibStatusNotif(),
			'notifications' => $notifications,
		]);
	}

	// methode pour ajouter un statut de notification
	#[Route('/api/v1/new_status_notif', name: 'api_new_status_notif', methods: ['POST'])]
	public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$libStatusNotif = trim((string) ($data['lib_status_notif'] ?? $data['libStatusNotif'] ?? ''));
		if ($libStatusNotif === '') {
			return $this->json(['message' => 'Le libelle du status notification est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$statusNotif = new StatusNotif();
		$statusNotif->setLibStatusNotif($libStatusNotif);

		$entityManager->persist($statusNotif);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le status notification a ete ajoute avec succes.',
			'status_notif' => $this->formatStatusNotif($statusNotif),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier un statut de notification
	#[Route('/api/v1/edit_status_notif/{id}', name: 'api_edit_status_notif', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		StatusNotifRepository $statusNotifRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$statusNotif = $statusNotifRepository->find($id);
		if (!$statusNotif) {
			return $this->json(['message' => 'Status notification introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$libStatusNotif = trim((string) ($data['lib_status_notif'] ?? $data['libStatusNotif'] ?? ''));
		if ($libStatusNotif === '') {
			return $this->json(['message' => 'Le libelle du status notification est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$statusNotif->setLibStatusNotif($libStatusNotif);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le status notification a ete modifie avec succes.',
			'status_notif' => $this->formatStatusNotif($statusNotif),
		]);
	}

	// methode pour supprimer un statut de notification
	#[Route('/api/v1/delete_status_notif/{id}', name: 'api_delete_status_notif', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(StatusNotif $statusNotif, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($statusNotif);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le status notification a ete supprime avec succes.',
		]);
	}

	private function formatStatusNotif(StatusNotif $statusNotif): array
	{
		return [
			'id_status_notif' => $statusNotif->getIdStatusNotif(),
			'lib_status_notif' => $statusNotif->getLibStatusNotif(),
		];
	}
}
