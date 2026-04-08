<?php

namespace App\Controller;
use App\Entity\Garage;
use App\Entity\Prestation;
use App\Entity\Proposer;
use App\Repository\CategorieRepository;
use App\Repository\PrestationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;



class PrestationController extends AbstractController
{
    #[Route('/api/v1/get_prestations', name: 'api_get_prestations', methods: ['GET'])]
    public function getAllPrestations(PrestationRepository $prestationRepository): JsonResponse
    {
        $prestations = $prestationRepository->findAll();

        $nomsPrestations = array_map(
            static fn (Prestation $prestation): array => [
                'id_prestation' => $prestation->getIdPrestation(),
                'nom_prestation' => $prestation->getNomPrestation(),
                'categorie' => $prestation->getCategorie() ? [
                    'id_categorie' => $prestation->getCategorie()->getIdCategorie(),
                    'nom_categorie' => $prestation->getCategorie()->getNomCategorie(),
                ] : null,
            ],
            $prestations
        );

        return $this->json($nomsPrestations);
    }

    #[Route('/api/v1/get_prestations_by_categorie/{idCategorie}', name: 'api_get_prestations_by_categorie', methods: ['GET'])]
    public function prestationByCategorie(int $idCategorie, PrestationRepository $prestationRepository): JsonResponse
    {
        $prestations = $prestationRepository->findBy(['categorie' => $idCategorie]);

        $nomsPrestations = array_map(
            static fn (Prestation $prestation): array => [
                'id_prestation' => $prestation->getIdPrestation(),
                'nom_prestation' => $prestation->getNomPrestation(),
            ],
            $prestations
        );

        return $this->json($nomsPrestations);
    }
    // méthode pour récupérer les prestations d'un garage
#[Route('/api/v1/get_prestations_by_garage/{idGarage}', name: 'api_get_prestations_by_garage', methods: ['GET'])]
public function prestationsByGarage(int $idGarage, EntityManagerInterface $em): JsonResponse
{
    $garage = $em->getRepository(Garage::class)->find($idGarage);
    if (!$garage) {
        return $this->json(['message' => 'Garage introuvable.'], JsonResponse::HTTP_NOT_FOUND);
    }

    // Récupérer via la table Proposer (qui contient le prix)
    $propositions = $em->getRepository(Proposer::class)->findBy(['garage' => $garage]);

    $prestations = [];
    foreach ($propositions as $proposition) {
        $prestation = $proposition->getPrestation();
        $categorie = $prestation->getCategorie();

        $prestations[] = [
            'id_prestation' => $prestation->getIdPrestation(),
            'nom_prestation' => $prestation->getNomPrestation(),
            'prix' => $proposition->getPrix(),
            'idCategorie' => $categorie ? $categorie->getIdCategorie() : null,
            'categorie' => $categorie ? [
                'id_categorie' => $categorie->getIdCategorie(),
                'nom_categorie' => $categorie->getNomCategorie(),
            ] : null,
        ];
    }

    return $this->json($prestations);
}
// méthode pour récupérer les détails d'une prestation
    #[Route('/api/v1/get_prestation/{id}', name: 'app_prestation_show', methods: ['GET'])]
    public function show(int $id, PrestationRepository $prestationRepository): JsonResponse
    {
        $prestation = $prestationRepository->find($id);
        if (!$prestation) {
            return $this->json(['message' => 'Prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }
    $categorie = $prestation->getCategorie();
     $nomprestation = [
            'id_prestation' => $prestation->getIdPrestation(),
            'nom_prestation' => $prestation->getNomPrestation(),
            'description_prestation' => $prestation->getDescriptionPrestation(),
            'duree_prestation' => $prestation->getDureePrestation(),
            'categorie' => $categorie ? [
                'id_categorie' => $categorie->getIdCategorie(),
                'nom_categorie' => $categorie->getNomCategorie(),
            ] : null,
        ];

        return $this->json($nomprestation);
    }
// méthode pour ajouter une prestation
    #[Route('/api/v1/new_prestation', name: 'app_prestation_new', methods: ['POST'])]
    public function newPrestation(
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieRepository $categorieRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $nomPrestation = trim((string) ($data['nomprestation'] ?? ''));
        $descriptionPrestation = trim((string) ($data['descriptionprestation'] ?? ''));
        $dureePrestation = trim((string) ($data['duree_prestation'] ?? ''));
        $categorieId = isset($data['categorie']) ? (int) $data['categorie'] : 0;

        if ($nomPrestation === '' || $descriptionPrestation === '' || $dureePrestation === '' || $categorieId <= 0) {
            return $this->json(['message' => 'Tous les champs sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $categorie = $categorieRepository->find($categorieId);
        if (null === $categorie) {
            return $this->json(['message' => 'Categorie introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $prestation = new Prestation();
        $prestation->setNomPrestation($nomPrestation);
        $prestation->setDescriptionPrestation($descriptionPrestation);
        $prestation->setDureePrestation($dureePrestation);
        $prestation->setCategorie($categorie);

        $entityManager->persist($prestation);
        $entityManager->flush();

        return $this->json([
            'message' => 'La prestation a ete ajoutee avec succes.',

        ], JsonResponse::HTTP_CREATED);
    }
// méthode pour modifier une prestation
    #[Route('/api/v1/edit_prestation/{id}', name: 'app_prestation_edit', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[Route('/api/v1/prestations/{id}', name: 'app_prestation_edit_rest', methods: ['PUT', 'PATCH'], requirements: ['id' => '\\d+'])]
    public function edit(
        int $id,
        Request $request,
        PrestationRepository $prestationRepository,
        EntityManagerInterface $entityManager,
        CategorieRepository $categorieRepository
    ): JsonResponse
    {
        $prestation = $prestationRepository->find($id);
        if (!$prestation) {
            return $this->json(['message' => 'Prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (isset($data['nomprestation'])) {
            $nomPrestation = trim((string) $data['nomprestation']);
            if ($nomPrestation === '') {
                return $this->json(['message' => 'nomprestation ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $prestation->setNomPrestation($nomPrestation);
        }

        if (isset($data['descriptionprestation'])) {
            $descriptionPrestation = trim((string) $data['descriptionprestation']);
            if ($descriptionPrestation === '') {
                return $this->json(['message' => 'descriptionprestation ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $prestation->setDescriptionPrestation($descriptionPrestation);
        }

        if (isset($data['duree_prestation'])) {
            $dureePrestation = trim((string) $data['duree_prestation']);
            if ($dureePrestation === '') {
                return $this->json(['message' => 'duree_prestation ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $prestation->setDureePrestation($dureePrestation);
        }

        if (isset($data['categorie'])) {
            $categorieId = (int) $data['categorie'];
            if ($categorieId <= 0) {
                return $this->json(['message' => 'categorie doit etre un identifiant valide.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $categorie = $categorieRepository->find($categorieId);
            if (null === $categorie) {
                return $this->json(['message' => 'Categorie introuvable.'], JsonResponse::HTTP_NOT_FOUND);
            }

            $prestation->setCategorie($categorie);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'La prestation a ete modifiee avec succes.',
            'id_prestation' => $prestation->getIdPrestation(),
            'nom_prestation' => $prestation->getNomPrestation(),
        ]);
    }
// méthode pour supprimer une prestation et ses liaisons
    #[Route('/api/v1/delete_prestation/{id}', name: 'app_prestation_delete', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    #[Route('/api/v1/prestations/{id}', name: 'app_prestation_delete_rest', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    public function delete(int $id, PrestationRepository $prestationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $prestation = $prestationRepository->find($id);
        if (!$prestation) {
            return $this->json(['message' => 'Prestation introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        try {
            // Evite de charger les entites liees (mapping potentiellement desynchronise avec la DB).
            $entityManager->createQuery('DELETE FROM App\\Entity\\Proposer p WHERE p.prestation = :prestation')
                ->setParameter('prestation', $prestation)
                ->execute();

            // Certains environnements n'ont pas encore la table `lier`.
            if ($connection->createSchemaManager()->tablesExist(['lier'])) {
                $entityManager->createQuery('DELETE FROM App\\Entity\\Lier l WHERE l.prestation = :prestation')
                    ->setParameter('prestation', $prestation)
                    ->execute();
            }

            $entityManager->remove($prestation);
            $entityManager->flush();
            $connection->commit();

            return $this->json(['message' => 'La prestation a ete supprimee avec succes.']);
        } catch (\Throwable $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            return $this->json([
                'message' => 'Erreur serveur lors de la suppression de la prestation.',
                'detail' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    // methode pour que le garage rajoute ca voiture
    #[Route('/api/v1/garage/{idGarage}/add_prestations', name: 'api_garage_add_prestations', methods: ['POST'])]
    public function addPrestationsGarage( int $idGarage, Request $request,  EntityManagerInterface $manager   ): JsonResponse

    {
        $garage = $manager->getRepository(Garage::class)->find($idGarage);

        if (!$garage) {
            return $this->json([
                'message' => 'Garage introuvable'
            ]);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['prestations']) || !is_array($data['prestations'])) {
            return $this->json([
                'message' => 'Liste des prestations requise'
            ]);
        }
        $proposerRepo = $manager->getRepository(Proposer::class);
        foreach ($data['prestations'] as $item) {

            if (!isset($item['id']) || !isset($item['prix'])) {
                continue;
            }

            $prestation = $manager->getRepository(Prestation::class)->find($item['id']);

            if (!$prestation) {
                continue;
            }

            // vérifier si la prestation existe déjà
            $existing = $proposerRepo->findOneBy([
                'garage' => $garage,
                'prestation' => $prestation
            ]);

            if ($existing) {
                // mise à jour du prix
                $existing->setPrix((float)$item['prix']);
            } else {
                // ajout nouvelle prestation
                $proposer = new Proposer();
                $proposer->setGarage($garage);
                $proposer->setPrestation($prestation);
                $proposer->setPrix((float)$item['prix']);

                $manager->persist($proposer);
            }
        }

        $manager->flush();

        return $this->json([
            'message' => 'Prestations mises à jour avec succès'
        ]);
    }
    // methode pour supprimer une prestation dans un garage
    #[Route('/api/v1/garage/{idGarage}/delete_prestation/{idPrestation}', name: 'api_garage_delete_prestation', methods: ['DELETE'])]
    public function deletePrestationGarage(  int $idGarage,int $idPrestation,EntityManagerInterface $manager ): JsonResponse

    {

        $garage = $manager->getRepository(Garage::class)->find($idGarage);
        $prestation = $manager->getRepository(Prestation::class)->find($idPrestation);

        if (!$garage || !$prestation) {
            return $this->json([
                'message' => 'Garage ou prestation introuvable'
            ]);
        }

        $proposer = $manager->getRepository(Proposer::class)->findOneBy([
            'garage' => $garage,
            'prestation' => $prestation
        ]);

        if (!$proposer) {
            return $this->json([
                'message' => 'Cette prestation n\'existe pas pour ce garage'
            ]);
        }

        $manager->remove($proposer);
        $manager->flush();

        return $this->json([
            'message' => 'Prestation supprimée du garage'
        ]);
    }

}
