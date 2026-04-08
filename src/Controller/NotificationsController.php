<?php

namespace App\Controller;

use App\Entity\Notifications;
use App\Repository\NotificationsRepository;
use App\Repository\RendezVousRepository;
use App\Repository\StatusNotifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class NotificationsController extends AbstractController
{
	// methode pour recuperer toutes les notifications
	#[Route('/api/v1/get_notifications', name: 'api_get_notifications', methods: ['GET'])]
	public function getAll(NotificationsRepository $notificationsRepository): JsonResponse
	{
		$notifications = $notificationsRepository->findAll();

		$result = array_map(
			fn (Notifications $notification): array => $this->formatNotification($notification),
			$notifications
		);

		return $this->json($result);
	}

	// methode pour recuperer les notifications d'un status
	#[Route('/api/v1/get_notifications_by_status/{idStatusNotif}', name: 'api_get_notifications_by_status', methods: ['GET'], requirements: ['idStatusNotif' => '\\d+'])]
	public function getByStatus(int $idStatusNotif, StatusNotifRepository $statusNotifRepository, NotificationsRepository $notificationsRepository): JsonResponse
	{
		$statusNotif = $statusNotifRepository->find($idStatusNotif);
		if (!$statusNotif) {
			return $this->json(['message' => 'Status notification introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$notifications = $notificationsRepository->findBy(['statusNotif' => $statusNotif]);
		$result = array_map(
			fn (Notifications $notification): array => $this->formatNotification($notification),
			$notifications
		);

		return $this->json($result);
	}

	// methode pour recuperer les notifications d'un rendez-vous
	#[Route('/api/v1/get_notifications_by_rdv/{idRdv}', name: 'api_get_notifications_by_rdv', methods: ['GET'], requirements: ['idRdv' => '\\d+'])]
	public function getByRdv(int $idRdv, RendezVousRepository $rendezVousRepository, NotificationsRepository $notificationsRepository): JsonResponse
	{
		$rdv = $rendezVousRepository->find($idRdv);
		if (!$rdv) {
			return $this->json(['message' => 'Rendez-vous introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$notifications = $notificationsRepository->findBy(['rdv' => $rdv]);
		$result = array_map(
			fn (Notifications $notification): array => $this->formatNotification($notification),
			$notifications
		);

		return $this->json($result);
	}

	// methode pour recuperer une notification
	#[Route('/api/v1/get_notification/{id}', name: 'api_get_notification', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, NotificationsRepository $notificationsRepository): JsonResponse
	{
		$notification = $notificationsRepository->find($id);
		if (!$notification) {
			return $this->json(['message' => 'Notification introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatNotification($notification));
	}

	// methode pour ajouter une notification
	#[Route('/api/v1/new_notification', name: 'api_new_notification', methods: ['POST'])]
	public function create(
		Request $request,
		StatusNotifRepository $statusNotifRepository,
		RendezVousRepository $rendezVousRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$contenue = trim((string) ($data['contenue'] ?? $data['contenu'] ?? ''));
		$idStatusNotif = (int) ($data['id_status_notif'] ?? $data['idStatusNotif'] ?? 0);
		$idRdv = (int) ($data['id_rdv'] ?? $data['idRdv'] ?? 0);

		if ($contenue === '' || $idStatusNotif <= 0 || $idRdv <= 0) {
			return $this->json(['message' => 'contenue, id_status_notif et id_rdv sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$statusNotif = $statusNotifRepository->find($idStatusNotif);
		$rdv = $rendezVousRepository->find($idRdv);

		if (!$statusNotif || !$rdv) {
			return $this->json(['message' => 'Status notification ou rendez-vous introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$notification = new Notifications();
		$notification->setContenue($contenue);
		$notification->setDateEnvoi(new \DateTime());
		$notification->setStatusNotif($statusNotif);
		$notification->setRdv($rdv);

		$entityManager->persist($notification);
		$entityManager->flush();

		return $this->json([
			'message' => 'La notification a ete ajoutee avec succes.',
			'notification' => $this->formatNotification($notification),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier une notification
	#[Route('/api/v1/edit_notification/{id}', name: 'api_edit_notification', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		NotificationsRepository $notificationsRepository,
		StatusNotifRepository $statusNotifRepository,
		RendezVousRepository $rendezVousRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$notification = $notificationsRepository->find($id);
		if (!$notification) {
			return $this->json(['message' => 'Notification introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (isset($data['contenue']) || isset($data['contenu'])) {
			$contenue = trim((string) ($data['contenue'] ?? $data['contenu']));
			if ($contenue === '') {
				return $this->json(['message' => 'contenue ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$notification->setContenue($contenue);
		}

		if (isset($data['id_status_notif']) || isset($data['idStatusNotif'])) {
			$idStatusNotif = (int) ($data['id_status_notif'] ?? $data['idStatusNotif']);
			if ($idStatusNotif <= 0) {
				return $this->json(['message' => 'id_status_notif invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$statusNotif = $statusNotifRepository->find($idStatusNotif);
			if (!$statusNotif) {
				return $this->json(['message' => 'Status notification introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}
			$notification->setStatusNotif($statusNotif);
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
			$notification->setRdv($rdv);
		}

		if (isset($data['date_envoi']) || isset($data['dateEnvoi'])) {
			$rawDate = (string) ($data['date_envoi'] ?? $data['dateEnvoi']);
			try {
				$date = new \DateTime($rawDate);
				$notification->setDateEnvoi($date);
			} catch (\Throwable) {
				return $this->json(['message' => 'date_envoi invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'La notification a ete modifiee avec succes.',
			'notification' => $this->formatNotification($notification),
		]);
	}

	// methode pour supprimer une notification
	#[Route('/api/v1/delete_notification/{id}', name: 'api_delete_notification', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Notifications $notification, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($notification);
		$entityManager->flush();

		return $this->json([
			'message' => 'La notification a ete supprimee avec succes.',
		]);
	}

	private function formatNotification(Notifications $notification): array
	{
		return [
			'id_notifications' => $notification->getIdNotifications(),
			'contenue' => $notification->getContenue(),
			'date_envoi' => $notification->getDateEnvoi()->format('Y-m-d H:i:s'),
			'status_notif' => [
				'id_status_notif' => $notification->getStatusNotif()?->getIdStatusNotif(),
				'lib_status_notif' => $notification->getStatusNotif()?->getLibStatusNotif(),
			],
			'rdv' => [
				'id_rdv' => $notification->getRdv()?->getIdRdv(),
				'date_debut' => $notification->getRdv()?->getDateDebut()->format('Y-m-d H:i:s'),
				'date_fin' => $notification->getRdv()?->getDateFin()->format('Y-m-d H:i:s'),
			],
		];
	}
}
