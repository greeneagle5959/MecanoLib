<?php

namespace App\Controller;

use App\Entity\Associer;
use App\Entity\Client;
use App\Entity\Garage;
use App\Entity\Horaire;
use App\Entity\Jour;
use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UtilisateurController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // la methode pour inscrire un utilisateur (client)
    #[Route('/api/v1/users/inscrire_client', name: 'app_users_inscrire_client', methods: ['POST'])]
    public function registerClient(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): JsonResponse
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
    public function registerGarage(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $nomGarage = $data['nom_garage'] ?? '';
        $email = $data['email'] ?? '';
        $telephone = $data['telephone'] ?? '';
        $adresse = $data['adresse'] ?? '';
        $siret = $data['siret'] ?? '';
        $mdp = $data['mdp'] ?? '';
        $villeNom = $data['ville'] ?? '';
        $villeCode = $data['code_insee'] ?? '';
        $cp = $data['cp'] ?? '';

        $tva = $this->calculerTvaIntracommunautaire($siret);

        if (!$nomGarage || !$email || !$telephone || !$adresse || !$siret || !$mdp || !$villeNom || !$villeCode || !$cp) {
            return $this->json(['erreur' => 'Merci de remplir toutes les informations'], 400);
        }

        if ($tva === null) {
            return $this->json(['erreur' => 'SIREN/SIRET invalide pour le calcul de TVA'], 400);
        }

        if ($manager->getRepository(Utilisateur::class)->findOneBy(['emailUtilisateur' => $email])) {
            return $this->json(['erreur' => 'Cet email est déjà utilisé'], 400);
        }

        if ($manager->getRepository(Garage::class)->findOneBy(['telephoneGarage' => $telephone])) {
            return $this->json(['erreur' => 'Ce numéro de téléphone est déjà utilisé'], 400);
        }

        if ($manager->getRepository(Garage::class)->findOneBy(['siret' => $siret])) {
            return $this->json(['erreur' => 'Ce SIRET est déjà utilisé'], 400);
        }

        if ($manager->getRepository(Garage::class)->findOneBy(['tva' => $tva])) {
            return $this->json(['erreur' => 'Ce TVA est déjà utilisé'], 400);
        }

        $roleGarage = $manager->getRepository(Role::class)->findOneBy(['nomRole' => 'ROLE_ADMIN']);
        if (!$roleGarage) {
            return $this->json(['erreur' => 'Rôle garage introuvable'], 400);
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
        $garage = new Garage();
        $garage->setNomGarage($nomGarage);
        $garage->setEmailGarage($email);
        $garage->setTelephoneGarage($telephone);
        $garage->setAdresseGarage($adresse);
        $garage->setSiret($siret);
        $garage->setTva($tva);
        $garage->setDateCreation(new \DateTime());
        $garage->setUtilisateur($utilisateur);
        $garage->setVille($ville);
        $garage->setIsValide(false);
        $garage->setImgGarage($data['img_garage'] ?? null);
        $garage->setImgLogo($data['img_logo'] ?? null);

        $manager->persist($garage);
        $this->initialiserPlanningGarageParDefaut($garage, $manager);
        $manager->flush();

        return $this->json([
            'message' => 'Garage ajouté avec succès. En attente de validation par la direction.'
        ], 201);
    }

    // ajouter un superadmin
    #[Route('/api/v1/users/ajouter_superadmin', name: 'ajouter_super_admin', methods: ['POST'])]
    public function ajouterSuperAdmin(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): JsonResponse
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

        if (!$role) {
            return $this->json([
                "erreur" => "Rôle super admin introuvable"
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
        $manager->flush();

        return $this->json([
            "message" => "Super admin ajouté avec succès"
        ], 201);
    }

    // creation dune route pour generer le token
    #[Route('/api/v1/users/login', name: 'app_user_login', methods: ['POST'])]
    public function login(Request $request, UtilisateurRepository $repo, UserPasswordHasherInterface $hasher, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['emailUtilisateur'] ?? $data['email'] ?? '';
        $mdp = $data['mdpUtilisateur'] ?? $data['mdp'] ?? '';
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

            if (!class_exists(GoogleAuthenticator::class)) {
                return $this->json(['erreur' => 'Module 2FA non installé'], 500);
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

    // la methode pour recuperer l'utilisateur connecte
    #[Route('/api/v1/users/connecter', name: 'app_user_connecter', methods: ['GET'])]
    public function connecter(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->json([
                'message' => 'Utilisateur non connecte.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $client = $user->getClients()->first() ?: null;
        $garage = $user->getGarages()->first() ?: null;
        $villeGarage = $garage?->getVille();

        return $this->json([
            'id' => $user->getIdUtilisateur(),
            'userId' => $user->getIdUtilisateur(),
            'email' => $user->getEmailUtilisateur(),
            'roles' => $user->getRoles(),
            'nom' => $client?->getNomClient() ?? $garage?->getNomGarage() ?? null,
            'prenom' => $client?->getPrenomClient() ?? null,
            'client' => $client ? [
                'id_client' => $client->getIdClient(),
                'nom_client' => $client->getNomClient(),
                'prenom_client' => $client->getPrenomClient(),
                'telephone_client' => $client->getTelephoneClient(),
                'adresse_client' => null,
                'nom_ville' => null,
                'code_postal' => null,
                'code_insee' => null,
            ] : null,
            'garage' => $garage ? [
                'id_garage' => $garage->getIdGarage(),
                'nom_garage' => $garage->getNomGarage(),
                'email_garage' => $garage->getEmailGarage(),
                'telephone_garage' => $garage->getTelephoneGarage(),
                'adresse_garage' => $garage->getAdresseGarage(),
                'nom_ville' => $villeGarage?->getNomVille(),
                'code_postal' => $villeGarage?->getCodePostal(),
                'code_insee' => $villeGarage?->getCodeInssee(),
            ] : null,
        ]);
    }

    // la methode pour changer le mot de passe de l'utilisateur connecte
    #[Route('/api/v1/users/change-password', name: 'app_users_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->json([
                'message' => 'Utilisateur non connecte.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $ancienMdp = $data['ancienMdp'] ?? $data['currentPassword'] ?? $data['oldPassword'] ?? '';
        $nouveauMdp = $data['mdp'] ?? $data['newPassword'] ?? $data['password'] ?? '';
        $confirmation = $data['confirmPassword'] ?? $data['confirmMdp'] ?? $data['confirmationMdp'] ?? '';

        if ($ancienMdp === '' || $nouveauMdp === '' || $confirmation === '') {
            return $this->json([
                'message' => 'Tous les champs sont obligatoires.'
            ], 400);
        }

        if (!$passwordHasher->isPasswordValid($user, $ancienMdp)) {
            return $this->json([
                'message' => 'Mot de passe actuel incorrect.'
            ], 401);
        }

        if (strlen($nouveauMdp) < 6) {
            return $this->json([
                'message' => 'Le nouveau mot de passe doit contenir au moins 6 caracteres.'
            ], 400);
        }

        if ($nouveauMdp !== $confirmation) {
            return $this->json([
                'message' => 'La confirmation du mot de passe ne correspond pas.'
            ], 400);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $nouveauMdp);
        $user->setMdpUtilisateur($hashedPassword);

        $manager->persist($user);
        $manager->flush();

        return $this->json([
            'message' => 'Mot de passe modifie avec succes.'
        ], 200);
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
    public function activer2FA(Request $request, EntityManagerInterface $manager): JsonResponse
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

        if (!class_exists(GoogleAuthenticator::class)) {
            return $this->json(['erreur' => 'Module 2FA non installé'], 500);
        }

        $googleAuthenticator = new GoogleAuthenticator();
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

    // methode pour desactiver la double authentification
    #[Route('/api/v1/users/desactiver_2fa', name: 'desactiver_2fa', methods: ['POST'])]
    public function desactiver2FA(Request $request, EntityManagerInterface $manager): JsonResponse
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

    // la methode pour verifier le code secret et activer la 2fa a mettre dans le dashboard (client/garage/superAdmin)
    #[Route('/api/v1/users/verify_2fa', name: 'verify_2fa', methods: ['POST'])]
    public function verify2FA(Request $request, EntityManagerInterface $manager): JsonResponse
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

        return $this->json([
            'message' => 'Code 2FA vérifié avec succès'
        ]);
    }

    private function initialiserPlanningGarageParDefaut(Garage $garage, EntityManagerInterface $manager): void
    {
        $templates = [
            'Lundi' => ['08:10', '12:00', '13:30', '16:00'],
            'Mardi' => ['08:20', '12:00', '14:00', '17:00'],
            'Mercredi' => ['08:30', '12:00', '14:00', '18:00'],
            'Jeudi' => ['08:30', '12:00', '14:00', '17:30'],
            'Vendredi' => ['08:30', '12:00', '14:00', '17:30'],
            'Samedi' => ['08:30', '12:00', '14:00', '17:30'],
            'Dimanche' => ['08:30', '12:00', '14:00', '17:30'],
        ];

        $jourRepository = $manager->getRepository(Jour::class);

        foreach ($templates as $libJour => [$ouvreMatin, $fermeMatin, $ouvreSoir, $fermeSoir]) {
            $jour = $jourRepository->findOneBy(['libJour' => $libJour]);

            if ($jour === null) {
                $jour = new Jour();
                $jour->setLibJour($libJour);
                $manager->persist($jour);
            }

            $horaire = new Horaire();
            $horaire->setGarage($garage);
            $horaire->setHreOuvreMatin(\DateTime::createFromFormat('H:i', $ouvreMatin) ?: new \DateTime('08:30'));
            $horaire->setHreFermeMatin(\DateTime::createFromFormat('H:i', $fermeMatin) ?: new \DateTime('12:00'));
            $horaire->setHreOuvreSoir(\DateTime::createFromFormat('H:i', $ouvreSoir) ?: new \DateTime('14:00'));
            $horaire->setHreFermeSoir(\DateTime::createFromFormat('H:i', $fermeSoir) ?: new \DateTime('17:30'));
            $manager->persist($horaire);

            $association = new Associer();
            $association->setJour($jour);
            $association->setHoraire($horaire);
            $manager->persist($association);
        }
    }

    // calculer le numero de TVA intracommunautaire a partir d'un siren/siret
    #[Route('/api/v1/users/calcul_tva', name: 'app_users_calcul_tva', methods: ['POST'])]
    public function calculTva(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['erreur' => 'JSON invalide'], 400);
        }

        $input = (string) ($data['siret'] ?? $data['siren'] ?? '');

        if ($input === '') {
            return $this->json(['erreur' => 'Veuillez fournir un siren ou un siret'], 400);
        }

        $tva = $this->calculerTvaIntracommunautaire($input);

        if ($tva === null) {
            return $this->json(['erreur' => 'SIREN/SIRET invalide pour le calcul de TVA'], 400);
        }

        return $this->json([
            'input' => $input,
            'tva_intracommunautaire' => $tva,
        ]);
    }

    // methode interne pour calculer la TVA FR avec la formule officielle
    private function calculerTvaIntracommunautaire(string $siretOrSiren): ?string
    {
        $digits = preg_replace('/\D+/', '', $siretOrSiren) ?? '';

        if ($digits === '' || strlen($digits) < 9) {
            return null;
        }

        $siren = substr($digits, 0, 9);

        if (!ctype_digit($siren)) {
            return null;
        }

        $sirenModulo = ((int) $siren) % 97;
        $cle = (12 + 3 * $sirenModulo) % 97;
        $cleFormatee = str_pad((string) $cle, 2, '0', STR_PAD_LEFT);

        return 'FR' . $cleFormatee . $siren;
    }
}
