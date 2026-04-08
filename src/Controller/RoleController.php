<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class RoleController extends AbstractController
{
	// methode pour recuperer tous les roles
	#[Route('/api/v1/get_roles', name: 'api_get_roles', methods: ['GET'])]
	public function getAll(RoleRepository $roleRepository): JsonResponse
	{
		$roles = $roleRepository->findAll();

		$result = array_map(
			fn (Role $role): array => $this->formatRole($role),
			$roles
		);

		return $this->json($result);
	}

	// methode pour recuperer les utilisateurs d'un role
	#[Route('/api/v1/get_utilisateurs_by_role/{idRole}', name: 'api_get_utilisateurs_by_role', methods: ['GET'], requirements: ['idRole' => '\\d+'])]
	public function getUsersByRole(int $idRole, RoleRepository $roleRepository): JsonResponse
	{
		$role = $roleRepository->find($idRole);
		if (!$role) {
			return $this->json(['message' => 'Role introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$utilisateurs = array_map(
			static fn (Utilisateur $utilisateur): array => [
				'id_utilisateur' => $utilisateur->getIdUtilisateur(),
				'email_utilisateur' => $utilisateur->getEmailUtilisateur(),
			],
			$role->getUtilisateurs()->toArray()
		);

		return $this->json([
			'id_role' => $role->getIdRole(),
			'nom_role' => $role->getNomRole(),
			'utilisateurs' => $utilisateurs,
		]);
	}

	// methode pour recuperer un role
	#[Route('/api/v1/get_role/{id}', name: 'api_get_role', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, RoleRepository $roleRepository): JsonResponse
	{
		$role = $roleRepository->find($id);
		if (!$role) {
			return $this->json(['message' => 'Role introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatRole($role));
	}

	// methode pour ajouter un role
	#[Route('/api/v1/new_role', name: 'api_new_role', methods: ['POST'])]
	public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
	{
		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomRole = trim((string) ($data['nom_role'] ?? $data['nomRole'] ?? ''));
		if ($nomRole === '') {
			return $this->json(['message' => 'Le nom du role est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$role = new Role();
		$role->setNomRole($nomRole);

		$entityManager->persist($role);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le role a ete ajoute avec succes.',
			'role' => $this->formatRole($role),
		], JsonResponse::HTTP_CREATED);
	}

	// methode pour modifier un role
	#[Route('/api/v1/edit_role/{id}', name: 'api_edit_role', methods: ['POST'], requirements: ['id' => '\\d+'])]
	public function edit(int $id, Request $request, RoleRepository $roleRepository, EntityManagerInterface $entityManager): JsonResponse
	{
		$role = $roleRepository->find($id);
		if (!$role) {
			return $this->json(['message' => 'Role introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomRole = trim((string) ($data['nom_role'] ?? $data['nomRole'] ?? ''));
		if ($nomRole === '') {
			return $this->json(['message' => 'Le nom du role est requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$role->setNomRole($nomRole);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le role a ete modifie avec succes.',
			'role' => $this->formatRole($role),
		]);
	}

	// methode pour supprimer un role
	#[Route('/api/v1/delete_role/{id}', name: 'api_delete_role', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Role $role, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($role);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le role a ete supprime avec succes.',
		]);
	}

	private function formatRole(Role $role): array
	{
		return [
			'id_role' => $role->getIdRole(),
			'nom_role' => $role->getNomRole(),
		];
	}
}
