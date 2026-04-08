<?php

namespace App\Controller;


use App\Entity\Vehicule;
use App\Repository\ClientRepository;
use App\Repository\MarqueRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class VehiculeController extends AbstractController
{
    // méthode pour récupérer tous les véhicules
    #[Route('/api/v1/get_vehicules', name: 'api_get_vehicules', methods: ['GET'])]
    public function getAllVehicules(VehiculeRepository $vehiculeRepository): JsonResponse
    {
        $vehicules = $vehiculeRepository->findAll();

        $data = array_map(
            fn (Vehicule $vehicule): array => $this->formatVehicule($vehicule),
            $vehicules
        );

        return $this->json($data);
    }

    #[Route('/api/v1/get_vehicule/{id}', name: 'api_get_vehicule', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function getVehicule(int $id, VehiculeRepository $vehiculeRepository): JsonResponse
    {
        $vehicule = $vehiculeRepository->find($id);

        if (!$vehicule) {
            return $this->json(['message' => 'Vehicule introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($this->formatVehicule($vehicule));
    }

    // afficher toutes les voiture pour choisir la marque
    #[Route('/api/v1/choisir_marque', name: 'app_choisir_marque' ,methods: ['GET'])]
    public function getMarques(MarqueRepository $repo): JsonResponse
    {
        $marques = $repo->findAll();

        $data = [];

        foreach ($marques as $marque) {
            $data[] = [
                "id_marque" => $marque->getIdMarque(),
                "nom_marque" => $marque->getNomMarque()
            ];
        }

        return new JsonResponse($data);
    }

    //afficher les modele en fonction de la marque choisi
    #[Route('/api/v1/choisir_modele/{id}', name: 'app_choisir_modele',methods: ['GET'])]
    public function getModeles(int $id, MarqueRepository $marqueRepository): JsonResponse
    {
        $marque = $marqueRepository->find($id);

        if (!$marque) {
            return $this->json([
                'message' => 'Marque introuvable'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $modeles = array_map(
            static fn (\App\Entity\Modele $modele): array => [
                'id_modele' => $modele->getIdModele(),
                'nom_modele' => $modele->getNomModele(),
            ],
            $marque->getModeles()->toArray()
        );

        return $this->json(array_values($modeles));
    }
// la methode pour que le client peut ajouter ces voiture
    #[Route('/api/v1/client/add_vehicule', name: 'app_add_vehicule', methods: ['POST'])]
    public function addVehicule( Request $request,EntityManagerInterface $manager,ClientRepository $clientRepo,MarqueRepository $marqueRepo ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $immatriculation = $data['immatriculation'] ?? null;
        $annee = $data['annee'] ?? null;
        $clientId = $data['id_client'] ?? null;
        $marqueId = $data['id_marque'] ?? null;
        $modeleId = $data['id_modele'] ?? null;

        if (!$immatriculation || !$annee || !$clientId || !$marqueId) {
            return $this->json([
                "message" => "Données manquantes"
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // vérifier immatriculation
        $immatriculationExiste = $manager->getRepository(Vehicule::class)
            ->findOneBy(['imatriculationVehicule' => $immatriculation]);

        if ($immatriculationExiste) {
            return $this->json([
                "erreur" => "Immatriculation existe déjà"
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $client = $clientRepo->find($clientId);
        $marque = $marqueRepo->find($marqueId);

        if ($modeleId) {
            $modele = $manager->getRepository(\App\Entity\Modele::class)->find($modeleId);
            if (!$modele) {
                return $this->json([
                    "message" => "Modele introuvable"
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            if ((int) $modele->getMarque()?->getIdMarque() !== (int) $marque->getIdMarque()) {
                return $this->json([
                    'message' => 'La marque ne correspond pas au modele selectionne'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        if (!$client || !$marque) {
            return $this->json([
                "message" => "Client ou marque introuvable"
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $vehicule = new Vehicule();
        $vehicule->setImatriculationVehicule($immatriculation);
        $vehicule->setAnneeVehicule($annee);
        $vehicule->setClient($client);
        $vehicule->setMarque($marque);

        $manager->persist($vehicule);
        $manager->flush();

        return $this->json([
            "message" => "Véhicule ajouté avec succès"
        ], JsonResponse::HTTP_CREATED);
    }
    //methode pour supprimer un vhecule
    #[Route('/api/v1/client/delete_vehicule/{id}', name: 'app_delete_vehicule', methods: ['DELETE'])]
    public function deleteVehicule( int $id,  EntityManagerInterface $manager   ): JsonResponse
    {

        $vehicule = $manager->getRepository(Vehicule::class)->find($id);

        if (!$vehicule) {
            return $this->json([
                "message" => "Véhicule introuvable"
            ], JsonResponse::HTTP_NOT_FOUND);
        }
        $manager->remove($vehicule);
        $manager->flush();

        return $this->json([
            "message" => "Véhicule supprimé avec succès"
        ]);
    }
    // methode pour afficher toutes les voiture en fonction de client connecté
    #[Route('/api/v1/client/vehicules/{id}', name: 'app_client_vehicules', methods: ['GET'])]
    public function getVehiculesClient( int $id, ClientRepository $clientRepo ): JsonResponse

    {
        $client = $clientRepo->find($id);

        if (!$client) {
            return $this->json([
                "message" => "Client introuvable"
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $vehicules = $client->getVehicules();

        $data = [];

        foreach ($vehicules as $vehicule) {

            $data[] = [
                "id_vehicule" => $vehicule->getIdVehicule(),
                "immatriculation" => $vehicule->getImatriculationVehicule(),
                "annee" => $vehicule->getAnneeVehicule(),
                "marque" => $vehicule->getMarque()->getNomMarque()
            ];
        }

        return $this->json($data);
    }
    //modifier le vehicule
    #[Route('/api/v1/client/update_vehicule/{id}', name: 'app_update_vehicule', methods: ['PATCH'])]
    public function updateVehicule( int $id, Request $request, EntityManagerInterface $manager, MarqueRepository $marqueRepo, ClientRepository $clientRepo ): JsonResponse
    {
        $vehicule = $manager->getRepository(Vehicule::class)->find($id);

        if (!$vehicule) {
            return $this->json([
                "message" => "Véhicule introuvable"
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (isset($data['immatriculation'])) {
            $immatriculation = trim((string) $data['immatriculation']);
            if ($immatriculation === '') {
                return $this->json(['message' => 'immatriculation ne peut pas etre vide'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $duplicate = $manager->getRepository(Vehicule::class)->findOneBy(['imatriculationVehicule' => $immatriculation]);
            if ($duplicate && $duplicate->getIdVehicule() !== $vehicule->getIdVehicule()) {
                return $this->json(['message' => 'Immatriculation existe déjà'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $vehicule->setImatriculationVehicule($immatriculation);
        }

        if (isset($data['annee'])) {
            $annee = trim((string) $data['annee']);
            if ($annee === '') {
                return $this->json(['message' => 'annee ne peut pas etre vide'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $vehicule->setAnneeVehicule($annee);
        }

        if (isset($data['id_marque'])) {
            $marque = $marqueRepo->find($data['id_marque']);

            if (!$marque) {
                return $this->json([
                    "message" => "Marque introuvable"
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $vehicule->setMarque($marque);
        }

        if (isset($data['id_client'])) {
            $client = $clientRepo->find((int) $data['id_client']);
            if (!$client) {
                return $this->json(['message' => 'Client introuvable'], JsonResponse::HTTP_NOT_FOUND);
            }
            $vehicule->setClient($client);
        }

        $manager->flush();

        return $this->json([
            "message" => "Véhicule modifié avec succès"
        ]);
    }
// méthode pour modifier un vehicule
    #[Route('/api/v1/new_vehicule', name: 'api_new_vehicule', methods: ['POST'])]
    public function newVehicule(Request $request, EntityManagerInterface $manager, ClientRepository $clientRepo, MarqueRepository $marqueRepo): JsonResponse
    {
        return $this->addVehicule($request, $manager, $clientRepo, $marqueRepo);
    }

    #[Route('/api/v1/edit_vehicule/{id}', name: 'api_edit_vehicule', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function editVehicule(int $id, Request $request, EntityManagerInterface $manager, MarqueRepository $marqueRepo, ClientRepository $clientRepo): JsonResponse
    {
        return $this->updateVehicule($id, $request, $manager, $marqueRepo, $clientRepo);
    }

    #[Route('/api/v1/delete_vehicule/{id}', name: 'api_delete_vehicule', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    public function removeVehicule(int $id, EntityManagerInterface $manager): JsonResponse
    {
        return $this->deleteVehicule($id, $manager);
    }

    private function formatVehicule(Vehicule $vehicule): array
    {
        $modeles = array_map(
            static fn (\App\Entity\Modele $modele): array => [
                'id_modele' => $modele->getIdModele(),
                'nom_modele' => $modele->getNomModele(),
            ],
            $vehicule->getMarque()?->getModeles()->toArray() ?? []
        );

        return [
            'id_vehicule' => $vehicule->getIdVehicule(),
            'immatriculation' => $vehicule->getImatriculationVehicule(),
            'annee' => $vehicule->getAnneeVehicule(),
            'client' => [
                'id_client' => $vehicule->getClient()?->getIdClient(),
                'nom_client' => $vehicule->getClient()?->getNomClient(),
                'prenom_client' => $vehicule->getClient()?->getPrenomClient(),
            ],
            'marque' => [
                'id_marque' => $vehicule->getMarque()?->getIdMarque(),
                'nom_marque' => $vehicule->getMarque()?->getNomMarque(),
                'modele' => $modeles[0]['nom_modele'] ?? null,
                'modeles' => $modeles,
            ],
        ];
    }


}
