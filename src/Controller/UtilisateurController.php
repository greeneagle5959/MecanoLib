<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Garage;
use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Repository\UtilisateurRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UtilisateurController extends AbstractController
{

// la methode pour inscrire un utilisateur (client)
    #[Route('/api/v1/users/inscrire_client', name: 'app_users_inscrire_client', methods: ['POST'])]
    public function registerClient( Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher , MailerInterface $mailer): JsonResponse 
         
    {

        $data = json_decode($request->getContent(), true);

        $nom = $data['nom_client'] ?? "";
        $prenom = $data['prenom_client'] ?? "";
        $email = $data['email'] ?? "";
        $mdp = $data['mdp'] ?? "";
        $tel = $data['telephone'] ?? ""; // ou 'telephone_client' si tu veux être cohérent
        $consentement = $data['consentement_client'] ?? false;

        if ($nom === "" || $prenom === "" || $email === "" || $mdp === "" || $tel === "") {
            return $this->json([
                "message" => "Merci de remplir toutes les informations"
            ], 400);
        }

        // vérifier email  si exist
        $emailExiste = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);

        if ($emailExiste) {
            return $this->json([
                "message" => "Cet email est déjà utilisé"
            ], 400);
        }
        // récupérer le rôle
        $role = $manager->getRepository(Role::class)
            ->findOneBy(['nomRole' => 'ROLE_USER']);

        if (!$role) {
            return $this->json([
                "message" => "Role client introuvable"
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
        // lenvois de mail 
        $emailMessage = (new Email())
        ->from('mecanolibcontact@gmail.com')
        ->to($email) 
        ->subject('Confirmation de votre inscription')
        ->html('<h1>Bienvenue </h1><p>Votre compte a été créé avec succès.</p>');
        $mailer->send($emailMessage);

        return $this->json([
            "message" => "Votre inscription a été prise en compte, un mail vous été envoyer"
        ], 201);
    }

    // verifecation numero siret 
    #[Route('/api/v1/check_siret_insee/{siret}', methods: ['GET'])]
    public function checkSiretInsee(string $siret, HttpClientInterface $client): JsonResponse
    {
        try {
            $response = $client->request(
                'GET',
                'https://recherche-entreprises.api.gouv.fr/search?q=' . $siret
            );
            $status = $response->getStatusCode();

            if ($status !== 200) {
                return $this->json([
                    'exists' => false,
                    
                ]);
            }
            $data = $response->toArray(false);

            if (!isset($data['results'][0])) {
                return $this->json(['exists' => false]);
            }

            $e = $data['results'][0];
            $siege = $e['siege'] ?? [];
            $isClosed = !empty($siege['date_fermeture']);

            return $this->json([
                'exists' => true,
                'is_closed' => $isClosed,
                'nom' => $e['nom_complet'] ?? '',
                'adresse' => $siege['adresse'] ?? '',
                'ville' => $siege['libelle_commune'] ?? '',
                'code_postal' => $siege['code_postal'] ?? '',
                'code_insee' => $siege['commune'] ?? '',
                'date_fermeture' => $siege['date_fermeture'] ?? null
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'exists' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // methode pour inscrire un garage 
    #[Route('/api/v1/users/inscrire-garage', name: 'app_users_inscrire-garage', methods: ['POST'])]
    public function registerGarage(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'JSON invalide ou vide'
            ], 400);
        }

        $nomGarage = $data['nom_garage'] ?? '';
        $email = $data['email'] ?? '';
        $telephone = $data['telephone'] ?? '';
        $adresse = $data['adresse'] ?? '';
        $siret = $data['siret'] ?? '';
        $tva = $data['tva'] ?? '';
        $mdp = $data['mdp'] ?? '';
        $villeNom = $data['ville'] ?? '';
        $villeCode = $data['code_insee'] ?? '';
        $cp = $data['cp'] ?? '';

        if (!$nomGarage || !$email || !$telephone || !$adresse || !$siret || !$tva || !$mdp || !$villeNom || !$villeCode || !$cp) {
            return $this->json(['message' => 'Merci de remplir toutes les informations'], 400);
        }

        if ($manager->getRepository(Utilisateur::class)->findOneBy(['emailUtilisateur' => $email])) {
            return $this->json(['message' => 'Cet email est déjà utilisé'], 400);
        }

        if ($manager->getRepository(Garage::class)->findOneBy(['telephoneGarage' => $telephone])) {
            return $this->json(['message' => 'Ce numéro de téléphone est déjà utilisé'], 400);
        }

        if ($manager->getRepository(Garage::class)->findOneBy(['siret' => $siret])) {
            return $this->json(['message' => 'Ce SIRET est déjà utilisé'], 400);
        }


        if ($manager->getRepository(Garage::class)->findOneBy(['tva' => $tva])) {
            return $this->json(['message' => 'Ce TVA est déjà utilisé'], 400);
        }


        $roleGarage = $manager->getRepository(Role::class)->findOneBy(['nomRole' => 'ROLE_ADMIN']);
        if (!$roleGarage) {
            return $this->json(['message' => 'Rôle garage introuvable'], 400);
        }

        // Créer l'utilisateur du garage
        $utilisateur = new Utilisateur();
        $utilisateur->setEmailUtilisateur($email);
        $utilisateur->setRole($roleGarage);
        $utilisateur->setIs2fa(false);
        $utilisateur->setAuth2fa(null);
        $hashedPassword = $passwordHasher->hashPassword($utilisateur, $mdp);
        $utilisateur->setMdpUtilisateur($hashedPassword);
        $manager->persist($utilisateur);

        // Récupérer ou créer une ville 
        $ville = $manager->getRepository(Ville::class)->findOneBy(['codeInssee' => $villeCode]);
        if (!$ville) {
            $ville = new Ville();
            $ville->setNomVille($villeNom);
            $ville->setCodeInssee($villeCode);
            $ville->setCodePostal($cp);
            $manager->persist($ville);
        } elseif ($ville->getCodePostal() !== $cp) {
            $ville->setCodePostal($cp);
        }

        // Créer le garage
        $googleautharage = new Garage();
        $googleautharage->setNomGarage($nomGarage);
        $googleautharage->setEmailGarage($email);
        $googleautharage->setTelephoneGarage($telephone);
        $googleautharage->setAdresseGarage($adresse);
        $googleautharage->setSiret($siret);
        $googleautharage->setTva($tva);
        $googleautharage->setDateCreation(new \DateTime());
        $googleautharage->setUtilisateur($utilisateur);
        $googleautharage->setVille($ville);
        $googleautharage->setIsValide(false);
        $googleautharage->setImgGarage($data['img_garage'] ?? null);
        $googleautharage->setImgLogo($data['img_logo'] ?? null);

        $manager->persist($googleautharage);
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
                "message" => "Email et mot de passe obligatoires"
            ], 400);
        }
        // vérifier si email existe
        $emailExiste = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);

        if ($emailExiste) {
            return $this->json([
                "message" => "Cet email est déjà utilisé"
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
    public function login( Request $request, UtilisateurRepository $repo, UserPasswordHasherInterface $hasher, GoogleAuthenticatorInterface $googleauthoogleAuth,JWTTokenManagerInterface $jwtManager ): JsonResponse
       
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['emailUtilisateur'] ?? '';
        $mdp = $data['mdpUtilisateur'] ?? '';
        $authCode = $data['authCode'] ?? null;

        if (!$email || !$mdp) {
            return $this->json(['message' => 'Email et mot de passe obligatoires'], 400);
        }

        $user = $repo->findOneBy(['emailUtilisateur' => $email]);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        if (!$hasher->isPasswordValid($user, $mdp)) {
            return $this->json(['message' => 'Email ou Mot de passe incorrect'], 401);
        }

         // Vérification 2FA
        if ($user->getIs2fa()) {
            if (!$authCode) {
                return $this->json(['message' => 'Veuillez fournir le code 2FA'], 403);
            }

            $googleauth = new GoogleAuthenticator();
            $timestep = 1; 

            if (!$googleauth->checkCode($user->getAuth2fa(), $authCode, $timestep)) {
                return $this->json(['message' => 'Code 2FA invalide'], 403);
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
        $user = $this->getUser(); 

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non connecté'], 401);
        }

        $client = $user->getClients()->first() ?: null;
        $googleautharage = $user->getGarages()->first() ?: null;

        return $this->json([
            'userId'   => $user->getIdUtilisateur(),
            'email'    => $user->getEmailUtilisateur(),
            'roles'    => $user->getRoles(),
            'nom'      => $client?->getNomClient() ?? $googleautharage?->getNomGarage() ?? null,
            'prenom'   => $client?->getPrenomClient() ?? null,
            'clientId' => $client?->getIdClient(),
            'is2fa' => $user->getIs2fa(),
            
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
    // la methode pour activer l'authentification 2FA
 
    #[Route('/api/v1/users/activer_2fa', name: 'activer_2fa', methods: ['POST'])]
    public function activer2FA(
        Request $request,
        EntityManagerInterface $manager,
        GoogleAuthenticatorInterface $googleAuthenticator,
        MailerService $mailerService
    ): JsonResponse 
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['message' => 'Email requis'], 400);
        }

        $user = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        // Génération le code secret
        $secret = $googleAuthenticator->generateSecret();

        $user->setAuth2fa($secret);

        
        $user->setIs2fa(false);

        $manager->persist($user);
        $manager->flush();

       // genere qrcode
        $qrUrl = GoogleQrUrl::generate(
            $user->getEmailUtilisateur(),
            $secret,
            'MecanoLib'
        );

        $html = "
            <h2>Activation du 2FA</h2>
            <p>Scannez ce QR Code avec Google Authenticator :</p>
            <img src='$qrUrl' />

            <p>Ou entrez ce code manuellement :</p>
            <h3>$secret</h3>

            <p>Ensuite, entrez le code à 6 chiffres dans l'application.</p>";
        
        $mailerService->sendEmail(
            $email,
            'Activation 2FA - MecanoLib',
            $html
        );

        return $this->json([
            'message' => 'Email envoyé avec QR Code'
        ]);
    }
  
    // methode pour descativer la double authentification 
    #[Route('/api/v1/users/desactiver_2fa', name: 'desactiver_2fa', methods: ['POST'])]
    public function desactiver2FA(Request $request, EntityManagerInterface $manager ): JsonResponse
        
    {

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['message' => 'Email requis'], 400);
        }

        $user = $manager->getRepository(Utilisateur::class)
            ->findOneBy(['emailUtilisateur' => $email]);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        $user->setIs2fa(false);
        $user->setAuth2fa(null);

        $manager->persist($user);
        $manager->flush();

        return $this->json([
            'message' => '2FA désactivé avec succès'
        ]);
    }
    // la methode pour verifier le code secret et activer la 2fa a mettre dans le dashboard (client/garage/superAdmin)
   

    #[Route('/api/v1/users/verify_2fa', name: 'verify_2fa', methods: ['POST'])]
    public function verify2FA(
        Request $request,
        EntityManagerInterface $manager,
        GoogleAuthenticatorInterface $googleAuthenticator
    ): JsonResponse {

        try {
            $data = json_decode($request->getContent(), true);

            $email = $data['email'] ?? null;
            $code = $data['code'] ?? null;

            if (!$email || !$code) {
                return $this->json(['message' => 'Email et code requis'], 400);
            }

            $user = $manager->getRepository(Utilisateur::class)
                ->findOneBy(['emailUtilisateur' => $email]);

            if (!$user) {
                return $this->json(['message' => 'Utilisateur introuvable'], 404);
            }

            $secret = $user->getAuth2fa();

            if (!$secret) {
                return $this->json(['message' => '2FA non configuré'], 400);
            }

            //  Vérification code
            $isValid = $googleAuthenticator->checkCode($user, $code);

            if (!$isValid) {
                return $this->json(['message' => 'Code 2FA invalide'], 403);
            }

            $user->setIs2fa(true);
            $manager->flush();

            return $this->json([
                'message' => '2FA validé avec succès'
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'message' => 'Erreur serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Reset password depuis le lien email
    #[Route('/api/v1/users/reset_password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request,UtilisateurRepository $repo,EntityManagerInterface $manager,UserPasswordHasherInterface $passwordHasher  ): JsonResponse
          
    {
        $data = json_decode($request->getContent(), true);

        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$token || !$newPassword) {
            return new JsonResponse(['message' => 'Token et mot de passe requis'], 400);
        }

        // Chercher l'utilisateur via le token
        $user = $repo->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return new JsonResponse(['message' => 'Token invalide'], 400);
        }

        // Vérifier expiration
        if ($user->getResetTokenExpiresAt() < new \DateTime()) {
            return new JsonResponse(['message' => 'Token expiré'], 400);
        }

        // Hash du nouveau mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setMdpUtilisateur($hashedPassword);

        // Supprimer token après utilisation
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $manager->flush();

        return new JsonResponse([
            'message' => 'Mot de passe mis à jour avec succès'
        ]);
    }
    // Rounouveler mot de passe 
    #[Route('/api/v1/users/forget_password', name: 'forget_password', methods: ['POST'])]
    public function forgetPassword( Request $request,UtilisateurRepository $repo,EntityManagerInterface $manager,MailerInterface $mailer ): JsonResponse
       
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['message' => 'Email requis'], 400);
        }

        $user = $repo->findOneBy(['emailUtilisateur' => $email]);

        
        if (!$user) {
            return new JsonResponse(['message' => 'Si cet email existe, un lien a été envoyé']);
        }

        // Générer un token sécurisé
        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));

        $manager->flush();

        
        // URL du frontend React
    $frontendUrl = "http://localhost:5173/";

    // lien avec token
    $resetLink = $frontendUrl . "reset-password?token=" . $token;

    $emailMessage = (new Email())
        ->from('mecanolibcontact@gmail.com')
        ->to($email)
        ->subject('Réinitialisation de votre mot de passe')
        ->html("
            <div style='font-family:Arial;padding:20px'>
                <h2>Réinitialisation du mot de passe </h2>

                <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                <p>Cliquez sur le bouton ci-dessous :</p>

                <a href='$resetLink'
                style='display:inline-block;padding:14px 25px;
                background:#6366f1;color:white;text-decoration:none;
                border-radius:8px;font-weight:bold;margin-top:15px'>
                Réinitialiser mon mot de passe
                </a>

                <p style='margin-top:20px'>
                     Ce lien expire dans <b>1 heure</b>.
                </p>

                <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
            </div>
        ");

    $mailer->send($emailMessage);

        return new JsonResponse([
            'message' => 'Si cet email existe, un lien a été envoyé'
        ]);
    }
   
}