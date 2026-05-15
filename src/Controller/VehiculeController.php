<?php

namespace App\Controller;


use App\Entity\Modele;
use App\Entity\Vehicule;
use App\Repository\ClientRepository;
use App\Repository\MarqueRepository;
use App\Repository\ModeleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class VehiculeController extends AbstractController
{
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
    public function getModeles(int $id, ModeleRepository $repo): JsonResponse
    {
        $modeles = $repo->findBy(['marque' => $id]);

        $data = [];

        foreach ($modeles as $modele) {
            $data[] = [
                "id_modele" => $modele->getIdModele(),
                "nom_modele" => $modele->getNomModele()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/v1/vehicules/rechercher/{plaque}', name: 'app_rechercher_vehicule_par_plaque', methods: ['GET'])]
    public function rechercherVehiculeParPlaque(string $plaque, EntityManagerInterface $manager): JsonResponse
    {
        $normalizedPlaque = strtoupper(trim($plaque));

        if ($normalizedPlaque === '') {
            return new JsonResponse([
                'message' => 'Plaque manquante'
            ], 400);
        }

        $vehicule = $manager->getRepository(Vehicule::class)->findOneBy([
            'imatriculationVehicule' => $normalizedPlaque,
        ]);

        if (!$vehicule) {
            return new JsonResponse([
                'found' => false,
                'message' => 'Véhicule introuvable'
            ], 404);
        }

        $client = $vehicule->getClient();
        $marque = $vehicule->getMarque();
        $modele = $vehicule->getModele();

        return new JsonResponse([
            'found' => true,
            'vehiculeId' => $vehicule->getIdVehicule(),
            'plaque' => $vehicule->getImatriculationVehicule(),
            'client' => $client ? [
                'idClient' => $client->getIdClient(),
                'nom' => $client->getNomClient(),
                'prenom' => $client->getPrenomClient(),
            ] : null,
            'marque' => $marque ? [
                'idMarque' => $marque->getIdMarque(),
                'nomMarque' => $marque->getNomMarque(),
            ] : null,
            'modele' => $modele ? [
                'idModele' => $modele->getIdModele(),
                'nomModele' => $modele->getNomModele(),
            ] : null,
        ]);
    }
// la methode pour que le client peut ajouter ces voiture 
    #[Route('/api/v1/client/add_vehicule', name: 'app_add_vehicule', methods: ['POST'])]
    public function addVehicule( Request $request,EntityManagerInterface $manager,ClientRepository $clientRepo,MarqueRepository $marqueRepo ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $immatriculation = $data['immatriculation'] ?? null;
        $annee = $data['annee'] ?? null;
        $clientId = $data['id_client'] ?? null;
        $marqueId = $data['id_marque'] ?? null;
        $modeleId = $data['id_modele'] ?? null;

        if (!$immatriculation || !$annee || !$clientId || !$marqueId || !$modeleId) {
            return new JsonResponse([
                "message" => "Données manquantes"
            ], 400);
        }

        // vérifier immatriculation
        $immatriculationExiste = $manager->getRepository(Vehicule::class)
            ->findOneBy(['imatriculationVehicule' => $immatriculation]);

        if ($immatriculationExiste) {
            return $this->json([
                "erreur" => "Immatriculation existe déjà"
            ], 400);
        }

        $client = $clientRepo->find($clientId);
        $marque = $marqueRepo->find($marqueId);
        $modele = $manager->getRepository(Modele::class)->find($modeleId);

        if (!$client || !$marque || !$modele) {
            return new JsonResponse([
                "message" => "Client, marque ou modèle introuvable"
            ], 404);
        }

        $vehicule = new Vehicule();
        $vehicule->setImatriculationVehicule($immatriculation);
        $vehicule->setAnneeVehicule($annee);
        $vehicule->setClient($client);
        $vehicule->setMarque($marque);
        $vehicule->setModele($modele);

        $manager->persist($vehicule);
        $manager->flush();

        return new JsonResponse([
            "message" => "Véhicule ajouté avec succès"
        ]);
    }
    //methode pour supprimer un vhecule 
    #[Route('/api/v1/client/delete_vehicule/{id}', name: 'app_delete_vehicule', methods: ['DELETE'])]
    public function deleteVehicule( int $id,  EntityManagerInterface $manager   ): JsonResponse 
    {

        $vehicule = $manager->getRepository(Vehicule::class)->find($id);

        if (!$vehicule) {
            return new JsonResponse([
                "message" => "Véhicule introuvable"
            ], 404);
        }
        $manager->remove($vehicule);
        $manager->flush();

        return new JsonResponse([
            "message" => "Véhicule supprimé avec succès"
        ]);
    }
    // methode pour afficher toutes les voiture en fonction de client connecté
    #[Route('/api/v1/client/vehicules/{id}', name: 'app_client_vehicules', methods: ['GET'])]
    public function getVehiculesClient( int $id, ClientRepository $clientRepo ): JsonResponse 
        
    {
        $client = $clientRepo->find($id);

        if (!$client) {
            return new JsonResponse([
                "message" => "Client introuvable"
            ], 404);
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

        return new JsonResponse($data);
    }
    //modifier le vehicule 
    #[Route('/api/v1/client/update_vehicule/{id}', name: 'app_update_vehicule', methods: ['PATCH'])]
    public function updateVehicule( int $id, Request $request, EntityManagerInterface $manager, MarqueRepository $marqueRepo ): JsonResponse  
    {
        $vehicule = $manager->getRepository(Vehicule::class)->find($id);

        if (!$vehicule) {
            return new JsonResponse([
                "message" => "Véhicule introuvable"
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['immatriculation'])) {
            $vehicule->setImatriculationVehicule($data['immatriculation']);
        }

        if (isset($data['annee'])) {
            $vehicule->setAnneeVehicule($data['annee']);
        }

        if (isset($data['id_marque'])) {
            $marque = $marqueRepo->find($data['id_marque']);

            if (!$marque) {
                return new JsonResponse([
                    "message" => "Marque introuvable"
                ], 404);
            }

            $vehicule->setMarque($marque);
        }

        if (isset($data['id_modele'])) {

            $modele = $manager->getRepository(Modele::class)
                ->find($data['id_modele']);

            if (!$modele) {
                return new JsonResponse([
                    "message" => "Modele introuvable"
                ], 404);
            }

            $vehicule->setModele($modele);
        }

        $manager->flush();

        return new JsonResponse([
            "message" => "Véhicule modifié avec succès"
        ]);
    }
    
    
}
