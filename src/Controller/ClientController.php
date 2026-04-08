<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Utilisateur;
use App\Repository\ClientRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ClientController extends AbstractController
{
	// méthode pour récupérer tous les clients
	#[Route('/api/v1/get_clients', name: 'api_get_clients', methods: ['GET'])]
	public function getAllClients(ClientRepository $clientRepository): JsonResponse
	{
		$clients = $clientRepository->findAll();

		$result = array_map(
			fn (Client $client): array => $this->formatClient($client),
			$clients
		);

		return $this->json($result);
	}

	// méthode pour récupérer les clients d'un utilisateur
	#[Route('/api/v1/get_clients_by_utilisateur/{idUtilisateur}', name: 'api_get_clients_by_utilisateur', methods: ['GET'], requirements: ['idUtilisateur' => '\\d+'])]
	public function getClientsByUtilisateur(
		int $idUtilisateur,
		UtilisateurRepository $utilisateurRepository,
		ClientRepository $clientRepository
	): JsonResponse {
		$utilisateur = $utilisateurRepository->find($idUtilisateur);
		if (!$utilisateur) {
			return $this->json(['message' => 'Utilisateur introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$clients = $clientRepository->findBy(['utilisateur' => $utilisateur]);
		$result = array_map(
			fn (Client $client): array => $this->formatClient($client),
			$clients
		);

		return $this->json($result);
	}

	// méthode pour récupérer le détail d'un client
	#[Route('/api/v1/get_client/{id}', name: 'api_get_client', methods: ['GET'], requirements: ['id' => '\\d+'])]
	public function show(int $id, ClientRepository $clientRepository): JsonResponse
	{
		$client = $clientRepository->find($id);
		if (!$client) {
			return $this->json(['message' => 'Client introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json($this->formatClient($client));
	}

	// méthode pour ajouter un client
	#[Route('/api/v1/new_client', name: 'api_new_client', methods: ['POST'])]
	public function newClient(
		Request $request,
		UtilisateurRepository $utilisateurRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$nomClient = trim((string) ($data['nom_client'] ?? $data['nomClient'] ?? ''));
		$prenomClient = trim((string) ($data['prenom_client'] ?? $data['prenomClient'] ?? ''));
		$telephoneClient = trim((string) ($data['telephone_client'] ?? $data['telephoneClient'] ?? ''));
		$consentementClient = (bool) ($data['consentement_client'] ?? $data['consentementClient'] ?? false);
		$idUtilisateur = (int) ($data['id_utilisateur'] ?? $data['idUtilisateur'] ?? 0);

		if ($nomClient === '' || $prenomClient === '' || $telephoneClient === '' || $idUtilisateur <= 0) {
			return $this->json(['message' => 'nom_client, prenom_client, telephone_client et id_utilisateur sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		$utilisateur = $utilisateurRepository->find($idUtilisateur);
		if (!$utilisateur) {
			return $this->json(['message' => 'Utilisateur introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$client = new Client();
		$client->setNomClient($nomClient);
		$client->setPrenomClient($prenomClient);
		$client->setTelephoneClient($telephoneClient);
		$client->setConsentementClient($consentementClient);
		$client->setDateInscription(new \DateTime());
		$client->setUtilisateur($utilisateur);

		$entityManager->persist($client);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le client a ete ajoute avec succes.',
			'client' => $this->formatClient($client),
		], JsonResponse::HTTP_CREATED);
	}

	// méthode pour modifier un client
	#[Route('/api/v1/edit_client/{id}', name: 'api_edit_client', methods: ['POST'], requirements: ['id' => '\\d+'])]
	#[Route('/api/v1/clients/{id}', name: 'api_update_client', methods: ['PUT', 'PATCH'], requirements: ['id' => '\\d+'])]
	public function edit(
		int $id,
		Request $request,
		ClientRepository $clientRepository,
		UtilisateurRepository $utilisateurRepository,
		EntityManagerInterface $entityManager
	): JsonResponse {
		$client = $clientRepository->find($id);
		if (!$client) {
			return $this->json(['message' => 'Client introuvable.'], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (isset($data['nom_client']) || isset($data['nomClient']) || isset($data['nom'])) {
			$nomClient = trim((string) ($data['nom_client'] ?? $data['nomClient'] ?? $data['nom']));
			if ($nomClient === '') {
				return $this->json(['message' => 'nom_client ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$client->setNomClient($nomClient);
		}

		if (isset($data['prenom_client']) || isset($data['prenomClient']) || isset($data['prenom'])) {
			$prenomClient = trim((string) ($data['prenom_client'] ?? $data['prenomClient'] ?? $data['prenom']));
			if ($prenomClient === '') {
				return $this->json(['message' => 'prenom_client ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$client->setPrenomClient($prenomClient);
		}

		if (isset($data['telephone_client']) || isset($data['telephoneClient']) || isset($data['telephone']) || isset($data['tel'])) {
			$telephoneClient = trim((string) ($data['telephone_client'] ?? $data['telephoneClient'] ?? $data['telephone'] ?? $data['tel']));
			if ($telephoneClient === '') {
				return $this->json(['message' => 'telephone_client ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}
			$client->setTelephoneClient($telephoneClient);
		}

		if (isset($data['consentement_client']) || isset($data['consentementClient']) || isset($data['consentement'])) {
			$client->setConsentementClient((bool) ($data['consentement_client'] ?? $data['consentementClient'] ?? $data['consentement']));
		}

		if (isset($data['email']) || isset($data['email_utilisateur']) || isset($data['emailUtilisateur'])) {
			$email = trim((string) ($data['email'] ?? $data['email_utilisateur'] ?? $data['emailUtilisateur']));
			if ($email === '') {
				return $this->json(['message' => 'email ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$emailExistant = $utilisateurRepository->findOneBy(['emailUtilisateur' => $email]);
			if ($emailExistant !== null && $emailExistant->getIdUtilisateur() !== $client->getUtilisateur()?->getIdUtilisateur()) {
				return $this->json(['message' => 'Cet email est deja utilise.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			if ($client->getUtilisateur() !== null) {
				$client->getUtilisateur()->setEmailUtilisateur($email);
			}
		}

		if (isset($data['id_utilisateur']) || isset($data['idUtilisateur'])) {
			$idUtilisateur = (int) ($data['id_utilisateur'] ?? $data['idUtilisateur']);
			if ($idUtilisateur <= 0) {
				return $this->json(['message' => 'id_utilisateur invalide.'], JsonResponse::HTTP_BAD_REQUEST);
			}

			$utilisateur = $utilisateurRepository->find($idUtilisateur);
			if (!$utilisateur) {
				return $this->json(['message' => 'Utilisateur introuvable.'], JsonResponse::HTTP_NOT_FOUND);
			}

			$client->setUtilisateur($utilisateur);
		}

		$entityManager->flush();

		return $this->json([
			'message' => 'Le client a ete modifie avec succes.',
			'client' => $this->formatClient($client),
		]);
	}
	// méthode pour afficher le profil du client connecté
	#[Route('/api/v1/client/profil', name: 'api_client_profile_show', methods: ['GET'])]
	public function getMyProfile(): JsonResponse
	{
		$user = $this->getUser();

		if (!$user instanceof Utilisateur) {
			return $this->json([
				'message' => 'Utilisateur non connecte.'
			], JsonResponse::HTTP_UNAUTHORIZED);
		}

		$client = $user->getClients()->first() ?: null;
		if (!$client instanceof Client) {
			return $this->json([
				'message' => 'Client introuvable pour cet utilisateur.'
			], JsonResponse::HTTP_NOT_FOUND);
		}

		return $this->json([
			'client' => $this->formatClientProfile($client),
		], JsonResponse::HTTP_OK);
	}

	// méthode pour que le client connecté puisse modifier son propre profil
	#[Route('/api/v1/client/profil', name: 'api_client_profile_update', methods: ['PUT', 'PATCH'])]
	public function updateMyProfile(
		Request $request,
		EntityManagerInterface $entityManager,
		UtilisateurRepository $utilisateurRepository
	): JsonResponse {
		$user = $this->getUser();

		if (!$user instanceof Utilisateur) {
			return $this->json([
				'message' => 'Utilisateur non connecte.'
			], JsonResponse::HTTP_UNAUTHORIZED);
		}

		$client = $user->getClients()->first() ?: null;
		if (!$client instanceof Client) {
			return $this->json([
				'message' => 'Client introuvable pour cet utilisateur.'
			], JsonResponse::HTTP_NOT_FOUND);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return $this->json([
				'message' => 'Donnees invalides.'
			], JsonResponse::HTTP_BAD_REQUEST);
		}

		if (!empty($data['nom']) || !empty($data['nom_client']) || !empty($data['nomClient'])) {
			$client->setNomClient((string) ($data['nom'] ?? $data['nom_client'] ?? $data['nomClient']));
		}

		if (!empty($data['prenom']) || !empty($data['prenom_client']) || !empty($data['prenomClient'])) {
			$client->setPrenomClient((string) ($data['prenom'] ?? $data['prenom_client'] ?? $data['prenomClient']));
		}

		if (!empty($data['telephone']) || !empty($data['telephone_client']) || !empty($data['telephoneClient']) || !empty($data['tel'])) {
			$client->setTelephoneClient((string) ($data['telephone'] ?? $data['telephone_client'] ?? $data['telephoneClient'] ?? $data['tel']));
		}

		if (isset($data['consentement']) || isset($data['consentement_client']) || isset($data['consentementClient'])) {
			$client->setConsentementClient((bool) ($data['consentement'] ?? $data['consentement_client'] ?? $data['consentementClient']));
		}

		if (!empty($data['email']) || !empty($data['email_client']) || !empty($data['emailUtilisateur'])) {
			$email = trim((string) ($data['email'] ?? $data['email_client'] ?? $data['emailUtilisateur']));
			if ($email === '') {
				return $this->json([
					'message' => 'email ne peut pas etre vide.'
				], JsonResponse::HTTP_BAD_REQUEST);
			}

			$emailExistant = $utilisateurRepository->findOneBy(['emailUtilisateur' => $email]);
			if ($emailExistant !== null && $emailExistant->getIdUtilisateur() !== $user->getIdUtilisateur()) {
				return $this->json([
					'message' => 'Cet email est deja utilise.'
				], JsonResponse::HTTP_BAD_REQUEST);
			}

			$user->setEmailUtilisateur($email);
		}

		$entityManager->persist($client);
		$entityManager->persist($user);
		$entityManager->flush();

		return $this->json([
			'message' => 'Profil client mis a jour avec succes.',
			'client' => $this->formatClientProfile($client),
		], JsonResponse::HTTP_OK);
	}

	// méthode pour supprimer un client
	#[Route('/api/v1/delete_client/{id}', name: 'api_delete_client', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
	public function delete(Client $client, EntityManagerInterface $entityManager): JsonResponse
	{
		$entityManager->remove($client);
		$entityManager->flush();

		return $this->json([
			'message' => 'Le client a ete supprime avec succes.',
		]);
	}

	private function formatClientProfile(Client $client): array
	{
		return [
			'id_client' => $client->getIdClient(),
			'nom_client' => $client->getNomClient(),
			'prenom_client' => $client->getPrenomClient(),
			'telephone_client' => $client->getTelephoneClient(),
			'adresse_client' => null,
			'email' => $client->getUtilisateur()?->getEmailUtilisateur(),
			'nom_ville' => null,
			'code_postal' => null,
			'code_insee' => null,
			'consentement_client' => $client->getConsentementClient(),
		];
	}

	private function formatClient(Client $client): array
	{
		return [
			'id_client' => $client->getIdClient(),
			'nom_client' => $client->getNomClient(),
			'prenom_client' => $client->getPrenomClient(),
			'telephone_client' => $client->getTelephoneClient(),
			'consentement_client' => $client->getConsentementClient(),
			'date_inscription' => $client->getDateInscription()->format('Y-m-d H:i:s'),
			'utilisateur' => [
				'id_utilisateur' => $client->getUtilisateur()?->getIdUtilisateur(),
				'email_utilisateur' => $client->getUtilisateur()?->getEmailUtilisateur(),
			],
		];
	}
}
