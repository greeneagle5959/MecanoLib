# Authentification JWT + 2FA (Google Authenticator)

## Description

Ce projet implémente un système d’authentification sécurisé basé sur :

-  JWT (JSON Web Token)
-  2FA (Google Authenticator)
-  Gestion des rôles (User, Admin, Super Admin)



 ## Technologies utilisées

- Symfony
- LexikJWTAuthenticationBundle
- SchebTwoFactorBundle
- Sonata Google Authenticator


## Documentation officielle

- JWT : https://symfony.com/bundles/LexikJWTAuthenticationBundle/current/index.html  
- 2FA : https://symfony.com/doc/current/SchebTwoFactorBundle/providers/google.html  


##  Installation

### Etape 1:

  Installation JWT (LexikJWTAuthenticationBundle)
  
    - composer require lexik/jwt-authentication-bundle
    
  Génération des clés JWT
  
    - php bin/console lexik:jwt:generate-keypair
    
  fichier nolmio
    -

### Etape 2:

  Installation Bundle Google Authenticator:
  
      - composer require scheb/two-factor-bundle
      
  Librairie Google Authenticator:
  
      - composer require sonata-project/google-authenticator
      
### Etape 3:

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
            pattern: pattern: ^/api/v1/users/login
            stateless: true 
            provider: users_in_database
           
        api:
            pattern: ^/api/v1/users
            stateless: true
            provider: users_in_database
            jwt: ~
### Etape 4 
  Configuration 2FA:
  
      Dans le fichier scheb_2fa.yaml:
      
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
                  

  ## Fonctionnement global:
  
   ### Étape 1
        Endpoint dans le controller :
            - POST /api/v1/users/login

    Processus  :
         1- Vérification email + mot de passe
         2- Si 2FA activé :
             * demande du code Google Authenticator
        Si OK :
        
          Génération du token :
            $token = $jwtManager->create($user);

          Vérification 2FA : 

            $g = new GoogleAuthenticator();
            if (!$g->checkCode($user->getAuth2fa(), $authCode)) {
                return $this->json(['erreur' => 'Code 2FA invalide'], 403);
            }

## Exemple de login pour generer le jwt + 2FA
    Endpoint : POST /api/v1/users/login
    Cette route permet de :
        - Vérifier l’email et le mot de passe
        - Vérifier le code 2FA si activé
        - Générer un token JWT
    
    Génération du token JWT : 
        $token = $jwtManager->create($user);
    
    Vérification 2FA + durée de vie de token :  
        $g = new GoogleAuthenticator();
            $timestep = 1; 
            if (!$g->checkCode($user->getAuth2fa(), $authCode, $timestep)) {
                return $this->json(['erreur' => 'Code 2FA invalide'], 403);
            }
  
## Gestion du 2FA : 
 
     Activation 2FA :
         Endpoint: POST /api/v1/users/activer_2fa
         Génère un secret et active le 2FA.
         
     Vérification 2FA: 
        Endpoint: POST /api/v1/users/verify_2fa
        Vérifie le code Google Authenticator.
             
     Désactivation du 2FA:
          Endpoint: POST /api/v1/users/desactiver_2fa
          Supprime le secret et désactive la double authentification
        

## Sécurité :

    - Authentification stateless via JWT
    - Double authentification avec TOTP (Google Authenticator)
    - Protection des routes via firewall Symfony
  

  
   
 
    
