<?php

namespace App\Controller;

use App\Entity\Files;
use App\Repository\FilesRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class FilesController extends AbstractController
{
	// methode pour recuperer tous les fichiers
	#[Route('/api/v1/get_files', name: 'api_get_files', methods: ['GET'])]
	public function getAll(FilesRepository $filesRepository): JsonResponse
	{
		$filesList = $filesRepository->findAll();

		$result = array_map(
			fn (Files $files): array => $this->formatFile($files),
			$filesList
		);

		return $this->json($result);
	}

	// methode pour recuperer les fichiers d'un rendez-vous
	#[Route('/api/v1/get_files_by_rdv/{idRdv}', name: 'api_get_files_by_rdv', methods: ['GET'], requirements: ['idRdv' => '\\d+'])]
	public function getByRdv(int $idRdv, RendezVousRepository $rendezVousRepository, FilesRepository $filesRepository): JsonResponse
	{
		$rdv = $rendezVousRepository->find($idRdv);
		if (!$rdv) {
			return $this->json(['message' => 'Rendez-vous introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$filesList = $filesRepository->findBy(['rdv' => $rdv]);
		$result = array_map(
			fn (Files $files): array => $this->formatFile($files),
			$filesList
		);

		return $this->json($result);
	}

	// methode pour recuperer un fichier
	#[Route('/api/v1/get_file/{id}', name: 'api_get_file', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, FilesRepository $filesRepository): JsonResponse
	{
		$files = $filesRepository->find($id);
		if (!$files) {
			return $this->json(['message' => 'Fichier introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatFile($files));
	}

	// methode pour ajouter un fichier
	#[Route('/api/v1/new_file', name: 'api_new_file', methods: ['POST'])]
	public function create(
		Request $request,
		RendezVousRepository $rendezVousRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$img = trim((string) ($data['img'] ?? ''));
		$commentaire = trim((string) ($data['commentaire'] ?? ''));
		$idRdv = (int) ($data['id_rdv'] ?? $data['idRdv'] ?? 0);

		if ($img === '' || $commentaire === '' || $idRdv <= 0) {
			return $this->json(['message' => 'img, commentaire et id_rdv sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$rdv = $rendezVousRepository->find($idRdv);
		if (!$rdv) {
			return $this->json(['message' => 'Rendez-vous introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$files = new Files();
		$files->setImg($img);
		$files->setCommentaire($commentaire);
		$files->setRdv($rdv);

		$entityManager->persist($files);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le fichier a ete ajoute avec succes.',
			'file' => $this->formatFile($files),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier un fichier
	#[Route('/api/v1/edit_file/{id}', name: 'api_edit_file', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		FilesRepository $filesRepository,
		RendezVousRepository $rendezVousRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$files = $filesRepository->find($id);
		if (!$files) {
			return $this->json(['message' => 'Fichier introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (isset($data['img'])) {
			$img = trim((string) $data['img']);
			if ($img === '') {
				return $this->json(['message' => 'img ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$files->setImg($img);
		}

		if (isset($data['commentaire'])) {
			$commentaire = trim((string) $data['commentaire']);
			if ($commentaire === '') {
				return $this->json(['message' => 'commentaire ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$files->setCommentaire($commentaire);
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

			$files->setRdv($rdv);
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'Le fichier a ete modifie avec succes.',
			'file' => $this->formatFile($files),
		]);
	}

	// methode pour supprimer un fichier
	#[Route('/api/v1/delete_file/{id}', name: 'api_delete_file', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Files $files, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($files);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le fichier a ete supprime avec succes.',
		]);
	}

	private function formatFile(Files $files): array
	{
		return [
			'id_files' => $files->getIdFiles(),
			'img' => $files->getImg(),
			'commentaire' => $files->getCommentaire(),
			'rdv' => [
				'id_rdv' => $files->getRdv()?->getIdRdv(),
				'date_debut' => $files->getRdv()?->getDateDebut()->format('Y-m-d H:i:s'),
				'date_fin' => $files->getRdv()?->getDateFin()->format('Y-m-d H:i:s'),
			],
		];
	}
}
