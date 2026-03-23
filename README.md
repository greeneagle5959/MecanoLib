Authentification JWT + 2FA Google Authenticator

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
    
  

  
   
 
    
