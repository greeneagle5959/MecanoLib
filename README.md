Description

Ce projet implémente un système d’authentification sécurisé basé sur :

    - JWT (JSON Web Token)
    - 2FA (Google Authenticator)
    - Gestion des rôles (User, Admin, Super Admin)
    
Technologies utilisées

    - Symfony
    - LexikJWTAuthenticationBundle
    - SchebTwoFactorBundle
    - Sonata Google Authenticator
    
Installation: 

    Authentification JWT + 2FA Google Authenticator
    
    https://symfony.com/doc/current/SchebTwoFactorBundle/providers/google.html

Etape 1
  Installation JWT (LexikJWTAuthenticationBundle)
    - composer require lexik/jwt-authentication-bundle
  Génération des clés JWT
    - php bin/console lexik:jwt:generate-keypair
  fichier nolmio
    -

   
Etape 2 
  Installation Bundle Google Authenticator
      - composer require scheb/two-factor-bundle
  Librairie Google Authenticator
      - composer require sonata-project/google-authenticator
Etape 3 
  Configuration dans Symfony le fichier security.yaml
  
    providers:
        users_in_memory: { memory: null }
        users_in_database:
            entity:
                class: App\Entity\Utilisateur
                property: emailUtilisateur
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        login:
            pattern: la route dans ton controller ex ( ^/api/v1/users/login)
            stateless: true 
            provider: users_in_database
           
        api:
            pattern: ^/api/v1/users
            stateless: true
            provider: users_in_database
            jwt: ~
Etape 4 
  Configuration 2FA
      Dans le fichier scheb_2fa.yaml
          # See the configuration reference at https://symfony.com/bundles/SchebTwoFactorBundle/6.x/configuration.html
          scheb_two_factor:
              security_tokens:
                  - Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken
                  - Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken
              # See the configuration reference at https://symfony.com/bundles/SchebTwoFactorBundle/6.x/configuration.html
              google:
                  enabled: true
                  server_name: le nom de ton dossier ex(MecanoLib)
                  issuer: le nom de ton dossier ex(MecanoLib)

  Fonctionnement global
    Étape 1
        Endpoint dans le controller :
            - POST /api/v1/users/login

    Processus  :
         1- Vérification email + mot de passe
         2- Si 2FA activé :
             * demande du code Google Authenticator
        Si OK :
          génération du JWT :
            $token = $jwtManager->create($user);

    Exemple de Endpoint 
    // creation dune route pour generer le token
    #[Route('/api/v1/users/login', name: 'app_user_login', methods: ['POST'])]
    public function login( Request $request, UtilisateurRepository $repo, UserPasswordHasherInterface $hasher,                GoogleAuthenticatorInterface $googleAuth,JWTTokenManagerInterface $jwtManager ): JsonResponse
       
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
  

  
   
 
    
