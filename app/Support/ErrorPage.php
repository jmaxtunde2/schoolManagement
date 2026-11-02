<?php

namespace App\Support;

use App\Services\Tenancy\TenantResolver;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Rend les pages d'erreur avec l'identité visuelle de CoriSchool.
 *
 * Une page Inertia est privilégiée pour rester cohérente avec le reste de
 * l'application. Lorsqu'Inertia ne peut pas être rendu (base de données
 * indisponible, assets absents...), une vue Blade autonome prend le relais.
 */
class ErrorPage
{
    public static function render(Request $request, Throwable $exception): ?SymfonyResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // Ces exceptions ont un traitement dédié (redirection vers la
        // connexion, retour au formulaire avec les erreurs de validation).
        if ($exception instanceof AuthenticationException || $exception instanceof ValidationException) {
            return null;
        }

        $status = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        // En local, conserver la page de débogage détaillée pour les erreurs
        // inattendues. Les erreurs HTTP volontaires (404, 403...) restent
        // toujours habillées par CoriSchool.
        if (! $exception instanceof HttpExceptionInterface && config('app.debug')) {
            return null;
        }

        if ($status < 400 || $status > 599) {
            $status = 500;
        }

        $meta = self::meta($status);
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        try {
            $response = Inertia::render('Errors/Error', [
                'status' => $status,
                ...$meta,
                'support' => [
                    'whatsapp' => config('cori.support.whatsapp'),
                    'whatsapp_message' => config('cori.support.whatsapp_message'),
                ],
            ])->toResponse($request)->setStatusCode($status);

            if ($headers !== []) {
                $response->headers->add($headers);
            }

            return $response;
        } catch (Throwable) {
            // Le rendu Inertia a échoué : utiliser la vue Blade autonome,
            // qui ne dépend ni de Vite ni de la base de données.
            return response()->view('errors.minimal', [
                'status' => $status,
                ...$meta,
                'branding' => self::safeBranding($request),
                'supportWhatsapp' => config('cori.support.whatsapp'),
                'supportMessage' => config('cori.support.whatsapp_message'),
            ], $status, $headers);
        }
    }

    /**
     * Textes français associés aux codes d'erreur HTTP les plus courants.
     *
     * @return array{title: string, message: string, hint: string}
     */
    public static function meta(int $status): array
    {
        return match ($status) {
            400 => [
                'title' => 'Requête invalide',
                'message' => 'La requête envoyée est incomplète ou mal formée.',
                'hint' => 'Vérifiez les informations saisies puis réessayez.',
            ],
            401 => [
                'title' => 'Connexion requise',
                'message' => 'Vous devez être connecté pour accéder à cette page.',
                'hint' => 'Connectez-vous avec vos identifiants pour continuer.',
            ],
            402 => [
                'title' => 'Paiement requis',
                'message' => 'Cette fonctionnalité nécessite une licence ou un paiement actif.',
                'hint' => 'Contactez votre administrateur pour régulariser votre abonnement.',
            ],
            403 => [
                'title' => 'Accès refusé',
                'message' => "Vous n'avez pas les autorisations nécessaires pour consulter cette page.",
                'hint' => "Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur de votre établissement.",
            ],
            404 => [
                'title' => 'Page introuvable',
                'message' => "La page demandée n'existe pas ou a été déplacée.",
                'hint' => "Vérifiez l'adresse ou revenez à l'accueil pour retrouver votre chemin.",
            ],
            405 => [
                'title' => 'Action non autorisée',
                'message' => "Cette méthode n'est pas autorisée pour cette adresse.",
                'hint' => "Revenez à la page précédente et utilisez l'interface prévue à cet effet.",
            ],
            408 => [
                'title' => 'Délai dépassé',
                'message' => 'Le serveur a mis trop de temps à répondre à votre demande.',
                'hint' => 'Vérifiez votre connexion puis réessayez.',
            ],
            419 => [
                'title' => 'Session expirée',
                'message' => 'Votre session a expiré pour des raisons de sécurité.',
                'hint' => 'Actualisez la page ou reconnectez-vous pour continuer.',
            ],
            429 => [
                'title' => 'Trop de tentatives',
                'message' => 'Vous avez effectué trop de requêtes en peu de temps.',
                'hint' => 'Patientez un instant puis réessayez.',
            ],
            500 => [
                'title' => 'Erreur interne',
                'message' => 'Une erreur inattendue est survenue de notre côté.',
                'hint' => "Réessayez dans quelques instants. Si le problème persiste, contactez l'administrateur de votre établissement.",
            ],
            501 => [
                'title' => 'Fonctionnalité indisponible',
                'message' => "Cette fonctionnalité n'est pas encore disponible.",
                'hint' => 'Revenez plus tard ou contactez votre administrateur.',
            ],
            502 => [
                'title' => 'Service momentanément indisponible',
                'message' => "Le service a reçu une réponse invalide d'un serveur voisin.",
                'hint' => 'Réessayez dans quelques instants.',
            ],
            503 => [
                'title' => 'Service en maintenance',
                'message' => 'CoriSchool est momentanément indisponible pour maintenance.',
                'hint' => 'Nous faisons notre possible pour revenir très vite. Réessayez dans quelques minutes.',
            ],
            504 => [
                'title' => 'Délai de réponse dépassé',
                'message' => "Un service en amont n'a pas répondu à temps.",
                'hint' => 'Réessayez dans quelques instants.',
            ],
            505 => [
                'title' => 'Version HTTP non supportée',
                'message' => "La version du protocole HTTP utilisée n'est pas prise en charge.",
                'hint' => 'Essayez avec un navigateur récent ou contactez le support.',
            ],
            default => [
                'title' => 'Une erreur est survenue',
                'message' => "La page n'a pas pu être affichée correctement.",
                'hint' => "Réessayez dans quelques instants. Si le problème persiste, contactez l'administrateur de votre établissement.",
            ],
        };
    }

    /**
     * Récupère l'identité de l'établissement sans jamais faire échouer la
     * page d'erreur (source de vérité : paramètres de l'école).
     */
    private static function safeBranding(Request $request): array
    {
        try {
            $school = $request->user()?->school;

            if (! $school) {
                $school = app(TenantResolver::class)->resolve($request);
            }

            return SchoolBranding::for($school);
        } catch (Throwable) {
            return SchoolBranding::guest();
        }
    }
}
