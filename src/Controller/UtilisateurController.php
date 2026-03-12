<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Garage;
use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
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
    public function login( Request $request, UtilisateurRepository $repo, UserPasswordHasherInterface $hasher, GoogleAuthenticatorInterface $googleAuth,JWTTokenManagerInterface $jwtManager ): JsonResponse
       
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['emailUtilisateur'] ?? '';
        $mdp = $data['mdpUtilisateur'] ?? '';
        $authCode = $data['authCode'] ?? null;

        if (!$email || !$mdp) {
            return $this->json(['erreur' => 'Email et mot de passe obligatoires'], 400);
        }

        $user = $repo->findOneBy(['emailUtilisateur' => $email]);
        if (!$user) {
            return $this->json(['erreur' => 'Utilisateur introuvable'], 404);
        }

        if (!$hasher->isPasswordValid($user, $mdp)) {
            return $this->json(['erreur' => 'Email ou Mot de passe incorrect'], 401);
        }

         // Vérification 2FA
        if ($user->getIs2fa()) {
            if (!$authCode) {
                return $this->json(['erreur' => 'Veuillez fournir le code 2FA'], 403);
            }

            $g = new GoogleAuthenticator();
            $timestep = 1; 

            if (!$g->checkCode($user->getAuth2fa(), $authCode, $timestep)) {
                return $this->json(['erreur' => 'Code 2FA invalide'], 403);
            }
        }

        // Générer le JWT
        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'userId' => $user->getIdUtilisateur(),
            'email' => $user->getEmailUtilisateur(),
            'roles' => $user->getRoles(),
        ]);
    }
   
    // la methode pour se connecter 
    #[Route('/api/v1/users/connecter', name: 'app_user_connecter', methods: ['GET'])]
    public function connecter(): JsonResponse
    {
        $user = $this->getUser(); // récupère l'utilisateur 

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
    //recuperer toutes les utilisateurs (client/Admin/superAdmin)
    #[Route('/api/v1/users/get_utilisateur', name: 'app_get_utilisateur', methods: ['POST'])]
    public function getAllUser(Request $request, UtilisateurRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $role = $data['role'] ?? "";
        $users = $repo->findAll();

        $filteredUsers = array_filter($users, function ($user) use ($role) {
            return $user->getRole() && $user->getRole()->getNomRole() === $role;
             
        });

        $result = [];

        foreach ($filteredUsers as $user) {
            $result[] = [
                'id_utilisateur' => $user->getIdUtilisateur(),
                'email' => $user->getEmailUtilisateur(),
                'role' => $user->getRole()?->getNomRole()
            ];
        }

        return $this->json($result);
    }
    #[Route('/api/v1/users/activer_2fa', name: 'activer_2fa', methods: ['POST'])]
    public function activer2FA(Request $request, EntityManagerInterface $manager, GoogleAuthenticatorInterface $googleAuthenticator): JsonResponse 
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['erreur' => 'Email requis'], 400);
        }

        $user = $manager->getRepository(Utilisateur::class)->findOneBy(['emailUtilisateur' => $email]);
        if (!$user) {
            return $this->json(['erreur' => 'Utilisateur introuvable'], 404);
        }

        $secret = $googleAuthenticator->generateSecret();
        $user->setAuth2fa($secret);
        $user->setIs2fa(true);

        $manager->persist($user);
        $manager->flush();

        return $this->json([
            'message' => '2FA activé',
            'secret' => $secret, 
        ]);
    }
    // methode pour descativer la double authentification 
    #[Route('/api/v1/users/desactiver_2fa', name: 'desactiver_2fa', methods: ['POST'])]
    public function desactiver2FA(Request $request, EntityManagerInterface $manager ): JsonResponse
        
    {

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['erreur' => 'Email requis'], 400);
        }

        $user = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);

        if (!$user) {
            return $this->json(['erreur' => 'Utilisateur introuvable'], 404);
        }

        $user->setIs2fa(false);
        $user->setAuth2fa(null);

        $manager->persist($user);
        $manager->flush();

        return $this->json([
            'message' => '2FA désactivé avec succès'
        ]);
    }
    #[Route('/api/v1/users/verify_2fa', name: 'verify_2fa', methods: ['POST'])]
    public function verify2FA( Request $request, EntityManagerInterface $manager ): JsonResponse  
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $code = $data['code'] ?? null;

        if (!$email || !$code) {
            return $this->json([
                'erreur' => 'Email et code requis'
            ], 400);
        }
        $user = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);

        if (!$user) {
            return $this->json([
                'erreur' => 'Utilisateur introuvable'
            ], 404);
        }

        $secret = $user->getAuth2fa();

        if (!$secret) {
            return $this->json([
                'erreur' => '2FA non configuré'
            ], 400);
        }
        $g = new GoogleAuthenticator();

        if (!$g->checkCode($secret, $code)) {
            return $this->json([
                'erreur' => 'Code 2FA invalide'
            ], 403);
        }
        // Activation définitive du 2FA
        $user->setIs2fa(true);

        $manager->persist($user);
        $manager->flush();

        return $this->json([
            'message' => '2FA activé avec succès'
        ]);
    }
   
}