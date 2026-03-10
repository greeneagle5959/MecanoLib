<?php

namespace App\Controller;
use App\Entity\Prestation;
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
            static fn (Prestation $prestation): array => ['nomprestation' => $prestation->getNomPrestation()],
            $prestations
        );

        return $this->json($nomsPrestations);
    }

    #[Route('/api/v1/get_prestations_by_categorie/{idCategorie}', name: 'api_get_prestations_by_categorie', methods: ['GET'], requirements: ['idCategorie' => '\\d+'])]
    public function prestationByCategorie(int $idCategorie, PrestationRepository $prestationRepository): JsonResponse
    {
        $prestations = $prestationRepository->findBy(['categorie' => $idCategorie]);

        $nomsPrestations = array_map(
            static fn (Prestation $prestation): array => ['nomprestation' => $prestation->getNomPrestation()],
            $prestations
        );

        return $this->json($nomsPrestations);
    }
#[Route('/api/v1/get_prestations_by_garage/{idGarage}', name: 'api_get_prestations_by_garage', methods: ['GET'], requirements: ['idGarage' => '\\d+'])]
    public function prestationByGarage(int $idGarage, PrestationRepository $prestationRepository): JsonResponse
    {
        $prestations = $prestationRepository->findBy(['categorie' => $idCategorie]);

        $nomsPrestations = array_map(
            static fn (Prestation $prestation): array => ['nomprestation' => $prestation->getNomPrestation()],
            $prestations
        );

        return $this->json($nomsPrestations);
    }





    #[Route('/api/v1/get_prestation/{id}', name: 'app_prestation_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
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

    #[Route('/api/v1/edit_prestation/{id}', name: 'app_prestation_edit', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function edit(
        Request $request,
        Prestation $prestation,
        EntityManagerInterface $entityManager,
        CategorieRepository $categorieRepository
    ): JsonResponse
    {
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
        ]);
    }

    #[Route('/api/v1/delete_prestation/{id}', name: 'app_prestation_delete', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    public function delete(Prestation $prestation, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($prestation);
        $entityManager->flush();

        return $this->json(['message' => 'La prestation a ete supprimee avec succes.']);
    }


}
