import { Link } from '@inertiajs/react';

import InfoPageLayout, {
    InfoSection,
} from '@/Components/Public/InfoPageLayout';

export default function Terms() {
    return (
        <InfoPageLayout
            metaTitle="Conditions d’utilisation | CoriSchool"
            metaDescription="Conditions d’utilisation de CoriSchool : accès à la plateforme, comptes, usage acceptable, propriété intellectuelle, disponibilité et responsabilités."
            title="Conditions d’utilisation"
            subtitle="Les règles qui encadrent l’accès et l’utilisation de la plateforme CoriSchool par les établissements, leurs équipes et leurs utilisateurs."
            updatedAt="novembre 2026"
        >
            <div className="space-y-8">
                <InfoSection title="1. Objet">
                    <p>
                        Les présentes conditions d’utilisation régissent
                        l’accès et l’utilisation de la plateforme CoriSchool,
                        solution de gestion scolaire éditée par Coriyase
                        Technologies. Elles s’appliquent à tout établissement
                        souscripteur ainsi qu’à toute personne disposant d’un
                        compte ou accédant aux services mis à disposition.
                    </p>
                </InfoSection>

                <InfoSection title="2. Acceptation des conditions">
                    <p>
                        L’utilisation de la plateforme implique l’acceptation
                        pleine et entière des présentes conditions. Si vous
                        utilisez CoriSchool pour le compte d’un établissement,
                        vous confirmez disposer de l’autorité nécessaire pour
                        engager celui-ci.
                    </p>
                </InfoSection>

                <InfoSection title="3. Accès à la plateforme">
                    <p>
                        L’accès est réservé aux établissements disposant d’un
                        abonnement ou d’une licence valide, ainsi qu’aux
                        utilisateurs autorisés par ces établissements. Les
                        identifiants sont personnels et confidentiels.
                    </p>
                    <p>
                        L’établissement s’engage à fournir les informations
                        nécessaires à la création et au maintien des comptes,
                        et à tenir à jour les coordonnées de ses utilisateurs.
                    </p>
                </InfoSection>

                <InfoSection title="4. Comptes et sécurité">
                    <p>
                        Chaque utilisateur est responsable de la confidentialité
                        de ses identifiants et des actions réalisées depuis son
                        compte. Toute utilisation non autorisée ou suspicion
                        d’accès frauduleux doit être signalée sans délai au
                        service clientèle.
                    </p>
                    <p>
                        L’établissement veille à attribuer des rôles et des
                        permissions adaptés aux fonctions de chacun de ses
                        utilisateurs.
                    </p>
                </InfoSection>

                <InfoSection title="5. Utilisation acceptable">
                    <p>Il est notamment interdit :</p>
                    <ul className="list-disc space-y-2 pl-5">
                        <li>
                            d’utiliser la plateforme à des fins illicites ou
                            contraires aux réglementations applicables ;
                        </li>
                        <li>
                            de porter atteinte à la sécurité, à l’intégrité ou
                            à la disponibilité du service ;
                        </li>
                        <li>
                            d’accéder à des données sans autorisation ou de les
                            divulguer à des tiers non habilités ;
                        </li>
                        <li>
                            de reproduire, revendre ou exploiter la plateforme
                            en dehors du cadre prévu par la licence ;
                        </li>
                        <li>
                            de transmettre des contenus illicites, injurieux
                            ou portant atteinte aux droits de tiers.
                        </li>
                    </ul>
                </InfoSection>

                <InfoSection title="6. Propriété intellectuelle">
                    <p>
                        La plateforme, sa structure, ses fonctionnalités, ses
                        composants logiciels et sa documentation demeurent la
                        propriété de Coriyase Technologies ou de ses
                        partenaires. Les droits concédés sont limités à
                        l’utilisation du service dans le cadre de la licence
                        souscrite.
                    </p>
                    <p>
                        Les données saisies par l’établissement restent sa
                        propriété. L’établissement garantit disposer des droits
                        nécessaires sur les contenus qu’il importe ou diffuse
                        sur la plateforme.
                    </p>
                </InfoSection>

                <InfoSection title="7. Données personnelles">
                    <p>
                        Le traitement des données personnelles est décrit dans
                        notre{' '}
                        <Link
                            href={route('public.privacy')}
                            className="font-bold text-emerald-700 underline-offset-2 hover:underline"
                        >
                            politique de confidentialité
                        </Link>
                        .
                    </p>
                </InfoSection>

                <InfoSection title="8. Disponibilité et maintenance">
                    <p>
                        Nous mettons en œuvre des moyens raisonnables pour
                        assurer la disponibilité et la sécurité de la
                        plateforme. Des interruptions temporaires peuvent
                        survenir pour maintenance, mise à jour ou en cas
                        d’événement indépendant de notre volonté.
                    </p>
                </InfoSection>

                <InfoSection title="9. Responsabilité">
                    <p>
                        L’établissement demeure responsable des données qu’il
                        saisit, des décisions administratives ou pédagogiques
                        prises à partir de la plateforme et du respect des
                        obligations qui lui incombent envers ses élèves,
                        familles et personnels.
                    </p>
                    <p>
                        Dans les limites permises par la loi, Coriyase
                        Technologies ne saurait être tenue responsable des
                        dommages indirects résultant d’une utilisation non
                        conforme du service.
                    </p>
                </InfoSection>

                <InfoSection title="10. Suspension et résiliation">
                    <p>
                        L’accès peut être suspendu en cas de manquement grave
                        aux présentes conditions, de défaut de paiement ou
                        d’utilisation compromettant la sécurité du service.
                        La résiliation de la licence met fin à l’accès à la
                        plateforme selon les modalités prévues au contrat.
                    </p>
                </InfoSection>

                <InfoSection title="11. Modifications">
                    <p>
                        Ces conditions peuvent être mises à jour afin de tenir
                        compte de l’évolution du service ou des obligations
                        légales. La version en vigueur est celle publiée sur
                        cette page.
                    </p>
                </InfoSection>

                <InfoSection title="12. Droit applicable et contact">
                    <p>
                        Les présentes conditions sont soumises au droit
                        applicable au siège de l’éditeur. Pour toute question,
                        consultez notre{' '}
                        <Link
                            href={route('public.contact')}
                            className="font-bold text-emerald-700 underline-offset-2 hover:underline"
                        >
                            page de contact
                        </Link>
                        .
                    </p>
                </InfoSection>
            </div>
        </InfoPageLayout>
    );
}
