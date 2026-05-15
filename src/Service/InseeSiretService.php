<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class InseeSiretService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function verify(string $rawSiret): array
    {
        $normalizedSiret = preg_replace('/\D+/', '', (string) $rawSiret);

        if (!is_string($normalizedSiret) || strlen($normalizedSiret) !== 14) {
            return [
                'status' => 400,
                'payload' => [
                    'exists' => false,
                    'message' => 'SIRET invalide',
                ],
            ];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                'https://recherche-entreprises.api.gouv.fr/search?q=' . urlencode($normalizedSiret),
                [
                    'timeout' => 8,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                return [
                    'status' => 502,
                    'payload' => [
                        'exists' => false,
                        'message' => 'Service de verification indisponible',
                    ],
                ];
            }

            $dataInsee = $response->toArray(false);
            if (!isset($dataInsee['results'][0])) {
                return [
                    'status' => 404,
                    'payload' => [
                        'exists' => false,
                        'message' => 'SIRET introuvable',
                    ],
                ];
            }

            $entreprise = $dataInsee['results'][0];
            $siege = $entreprise['siege'] ?? [];
            $dateFermeture = $siege['date_fermeture'] ?? null;

            if ($dateFermeture) {
                $date = (new \DateTime($dateFermeture))->format('d/m/Y');

                return [
                    'status' => 400,
                    'payload' => [
                        'exists' => false,
                        'is_closed' => true,
                        'message' => "Ce garage est ferme depuis le $date",
                    ],
                ];
            }

            return [
                'status' => 200,
                'payload' => [
                    'exists' => true,
                    'is_closed' => false,
                    'nom' => $entreprise['nom_complet'] ?? '',
                    'adresse' => $siege['adresse'] ?? '',
                    'ville' => $siege['libelle_commune'] ?? '',
                    'code_postal' => $siege['code_postal'] ?? '',
                    'code_insee' => $siege['commune'] ?? '',
                    'date_fermeture' => $siege['date_fermeture'] ?? null,
                ],
            ];
        } catch (\Throwable) {
            return [
                'status' => 500,
                'payload' => [
                    'exists' => false,
                    'message' => 'Erreur serveur lors de la verification du SIRET',
                ],
            ];
        }
    }
}
