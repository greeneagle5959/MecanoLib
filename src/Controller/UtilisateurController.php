<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Garage;
use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UtilisateurController extends AbstractController
{

// la methode pour inscrire un utilisateur (client)
    #[Route('/api/v1/users/inscrire_client', name: 'app_users_inscrire_client', methods: ['POST'])]
    public function registerClient( Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher ): JsonResponse 
         
    {

        $data = json_decode($request->getContent(), true);

        $nom = $data['nom'] ?? "";
        $prenom = $data['prenom'] ?? "";
        $email = $data['email'] ?? "";
        $mdp = $data['mdp'] ?? "";
        $tel = $data['tel'] ?? "";
        $consentement = $data['consentement'] ?? false;

        if ($nom === "" || $prenom === "" || $email === "" || $mdp === "" || $tel === "") {
            return $this->json([
                "erreur" => "Merci de remplir toutes les informations"
            ], 400);
        }

        // vérifier email  si exist
        $emailExiste = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);

        if ($emailExiste) {
            return $this->json([
                "erreur" => "Cet email est déjà utilisé"
            ], 400);
        }

        // récupérer le rôle
        $role = $manager->getRepository(Role::class)
            ->findOneBy(['nomRole' => 'ROLE_USER']);

        if (!$role) {
            return $this->json([
                "erreur" => "Role client introuvable"
            ], 400);
        }

        // créer utilisateur
        $utilisateur = new Utilisateur();
        $utilisateur->setEmailUtilisateur($email);
        $hashedPassword = $passwordHasher->hashPassword(
            $utilisateur,
            $mdp
        );

        $utilisateur->setMdpUtilisateur($hashedPassword);

        $utilisateur->setRole($role);
        $utilisateur->setIs2fa(false);
        $utilisateur->setAuth2fa(null);
        $manager->persist($utilisateur);
        // créer client
        $client = new Client();

        $client->setNomClient($nom);
        $client->setPrenomClient($prenom);
        $client->setTelephoneClient($tel);
        $client->setConsentementClient($consentement);
        $client->setDateInscription(new \DateTime());
        $client->setUtilisateur($utilisateur);

        $manager->persist($client);

        $manager->flush();
        return $this->json([
            "message" => "Client inscrit avec succès"
        ], 201);
    }

    // methode pour inscrire un garage 
   #[Route('/api/v1/users/inscrire-garage', name: 'app_users_inscrire-garage', methods: ['POST'])]
    public function registerGarage( Request $request,  EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): JsonResponse
       
    {
        
        $data = json_decode($request->getContent(), true);

        $nomGarage = $data['nom_garage'] ?? '';
        $email = $data['email'] ?? '';
        $telephone = $data['telephone'] ?? '';
        $adresse = $data['adresse'] ?? '';
        $siret = $data['siret'] ?? '';
        $tva = $data['tva'] ?? '';
        $villeId = $data['id_ville'] ?? null;
        $mdp = $data['mdp'] ?? '';

        if (!$nomGarage || !$email || !$telephone || !$adresse || !$siret || !$tva || !$villeId || !$mdp) {
            return $this->json(['erreur' => 'Merci de remplir toutes les informations'], 400);
        }

        // Vérifier si l'email existe déjà
        $emailExiste = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);
        if ($emailExiste) {
            return $this->json(['erreur' => 'Cet email est déjà utilisé'], 400);
        }

        // Récupérer le rôle garage
        $roleGarage = $manager->getRepository(Role::class)
            ->findOneBy(['nomRole' => 'ROLE_ADMIN']); 
       
        // Créer l'utilisateur du garage
        $utilisateur = new Utilisateur();
        $utilisateur->setEmailUtilisateur($email);
        $utilisateur->setRole($roleGarage);
        $utilisateur->setIs2fa(false);
        $utilisateur->setAuth2fa(null);
        $hashedPassword = $passwordHasher->hashPassword($utilisateur, $mdp);
        $utilisateur->setMdpUtilisateur($hashedPassword);
        $manager->persist($utilisateur);

        // Créer le garage
        $garage = new Garage();
        $garage->setNomGarage($nomGarage);
        $garage->setEmailGarage($email);
        $garage->setTelephoneGarage($telephone);
        $garage->setAdresseGarage($adresse);
        $garage->setSiret($siret);
        $garage->setTva($tva);
        $garage->setDateCreation(new \DateTime());
        $garage->setUtilisateur($utilisateur);
        $garage->setIsValide(false); 
        $garage->setImgGarage($data['img_garage'] ?? null);
        $garage->setImgLogo($data['img_logo'] ?? null);

        // Récupérer la ville
        $ville = $manager->getRepository(Ville::class)->find($villeId);
        if (!$ville) {
            return $this->json(['erreur' => 'Ville introuvable'], 400);
        }
        $garage->setVille($ville);

        $manager->persist($garage);
        $manager->flush();

        return $this->json([
            'message' => 'Garage ajouté avec succès. En attente de validation par la direction.'
        ], 201);
    }
    // ajouter un superadmin 
    #[Route('/api/v1/users/ajouter_superadmin', name: 'ajouter_super_admin', methods: ['POST'])]
    public function ajouterSuperAdmin( Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher ): JsonResponse
          
    {

        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? '';
        $mdp = $data['mdp'] ?? '';

        if ($email === "" || $mdp === "") {
            return $this->json([
                "erreur" => "Email et mot de passe obligatoires"
            ], 400);
        }
        // vérifier si email existe
        $emailExiste = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);

        if ($emailExiste) {
            return $this->json([
                "erreur" => "Cet email est déjà utilisé"
            ], 400);
        }

        // récupérer rôle super admin
        $role = $manager->getRepository(Role::class)
            ->findOneBy(['nomRole' => 'ROLE_SUPER_ADMIN']);

       
        // créer utilisateur
        $utilisateur = new Utilisateur();

        $utilisateur->setEmailUtilisateur($email);

        $hashedPassword = $passwordHasher->hashPassword(
            $utilisateur,
            $mdp
        );

        $utilisateur->setMdpUtilisateur($hashedPassword);

        $utilisateur->setRole($role);
        $utilisateur->setIs2fa(false);
        $utilisateur->setAuth2fa(null);

        $manager->persist($utilisateur);
        $manager->flush();

        return $this->json([
            "message" => "Super admin ajouté avec succès"
        ], 201);
    }
    // creation dune route pour generer le token
    #[Route('/api/v1/users/login', name: 'app_user_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json(['success' => ' tester token']);
    }
    // la methode pour se connecter 
    #[Route('/api/v1/users/connecter', name: 'app_user_connecter', methods: ['GET'])]
    public function connecter(): JsonResponse
    {
        $user = $this->getUser(); // récupère l'utilisateur via JWT

        if (!$user) {
            return $this->json(['erreur' => 'Utilisateur non connecté'], 401);
        }

        $client = $user->getClients()->first() ?: null;
        $garage = $user->getGarages()->first() ?: null;

        return $this->json([
            'userId'   => $user->getIdUtilisateur(),
            'email'    => $user->getEmailUtilisateur(),
            'roles'    => $user->getRoles(),
            'nom'      => $client?->getNomClient() ?? $garage?->getNomGarage() ?? null,
            'prenom'   => $client?->getPrenomClient() ?? null,
            
        ]);
    }

}