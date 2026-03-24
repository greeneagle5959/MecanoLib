<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class VilleController extends AbstractController
{
    // Récupérer la liste de toutes les villes
    #[Route('/api/v1/get_villes', name: 'api_get_villes', methods: ['GET'])]
    public function getAll(VilleRepository $villeRepository): JsonResponse
    {
        $villes = $villeRepository->findAll();

        $result = array_map(
            static fn (Ville $ville): array => [
                'id_ville' => $ville->getIdVille(),
                'nom_ville' => $ville->getNomVille(),
                'code_postal' => $ville->getCodePostal(),
                'code_inssee' => $ville->getCodeInssee(),
            ],
            $villes
        );

        return $this->json($result);
    }

    // Récupérer une ville par son identifiant
    #[Route('/api/v1/get_ville/{id}', name: 'api_get_ville', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function getOne(int $id, VilleRepository $villeRepository): JsonResponse
    {
        $ville = $villeRepository->find($id);

        if (!$ville) {
            return $this->json(['message' => 'Ville introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id_ville' => $ville->getIdVille(),
            'nom_ville' => $ville->getNomVille(),
            'code_postal' => $ville->getCodePostal(),
            'code_inssee' => $ville->getCodeInssee(),
        ]);
    }

    // Récupérer les garages rattachés à une ville
    #[Route('/api/v1/get_garages_by_ville/{id}', name: 'api_get_garages_by_ville', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function getGaragesByVille(int $id, VilleRepository $villeRepository): JsonResponse
    {
        $ville = $villeRepository->find($id);

        if (!$ville) {
            return $this->json(['message' => 'Ville introuvable.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $garages = $ville->getGarages();

        $result = [];
        foreach ($garages as $garage) {
            $result[] = [
                'id_garage' => $garage->getIdGarage(),
                'nom_garage' => $garage->getNomGarage(),
                'email_garage' => $garage->getEmailGarage(),
                'telephone_garage' => $garage->getTelephoneGarage(),
                'adresse_garage' => $garage->getAdresseGarage(),
                'img_garage' => $garage->getImgGarage(),
                'img_logo' => $garage->getImgLogo(),
                'is_valide' => $garage->getIsValide(),
            ];
        }

        return $this->json([
            'id_ville' => $ville->getIdVille(),
            'nom_ville' => $ville->getNomVille(),
            'garages' => $result,
        ]);
    }

    // Créer une nouvelle ville
    #[Route('/api/v1/new_ville', name: 'api_new_ville', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $nomVille = trim((string) ($data['nom_ville'] ?? ''));
        $codePostal = trim((string) ($data['code_postal'] ?? ''));
        $codeInssee = trim((string) ($data['code_inssee'] ?? ''));

        if ($nomVille === '' || $codePostal === '' || $codeInssee === '') {
            return $this->json(['message' => 'Tous les champs sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $ville = new Ville();
        $ville->setNomVille($nomVille);
        $ville->setCodePostal($codePostal);
        $ville->setCodeInssee($codeInssee);

        $entityManager->persist($ville);
        $entityManager->flush();

        return $this->json([
            'message' => 'La ville a ete ajoutee avec succes.',
            'id_ville' => $ville->getIdVille(),
        ], JsonResponse::HTTP_CREATED);
    }

    // Modifier une ville existante
    #[Route('/api/v1/edit_ville/{id}', name: 'api_edit_ville', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, Ville $ville, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (isset($data['nom_ville'])) {
            $nomVille = trim((string) $data['nom_ville']);
            if ($nomVille === '') {
                return $this->json(['message' => 'nom_ville ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $ville->setNomVille($nomVille);
        }

        if (isset($data['code_postal'])) {
            $codePostal = trim((string) $data['code_postal']);
            if ($codePostal === '') {
                return $this->json(['message' => 'code_postal ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $ville->setCodePostal($codePostal);
        }

        if (isset($data['code_inssee'])) {
            $codeInssee = trim((string) $data['code_inssee']);
            if ($codeInssee === '') {
                return $this->json(['message' => 'code_inssee ne peut pas etre vide.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $ville->setCodeInssee($codeInssee);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'La ville a ete modifiee avec succes.',
        ]);
    }

    // Supprimer une ville par son identifiant
    #[Route('/api/v1/delete_ville/{id}', name: 'api_delete_ville', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    public function delete(Ville $ville, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($ville);
        $entityManager->flush();

        return $this->json(['message' => 'La ville a ete supprimee avec succes.']);
    }
}
