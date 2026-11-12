import { Link } from '@inertiajs/react';

import InfoPageLayout, {
    InfoSection,
} from '@/Components/Public/InfoPageLayout';

export default function Privacy({ support = {} }) {
    const email = support.email || 'support@coriyase.com';

    return (
        <InfoPageLayout
            metaTitle="Politique de confidentialité | CoriSchool"
            metaDescription="Politique de confidentialité de CoriSchool : données collectées, finalités, base légale, conservation, sécurité, droits des personnes et cookies."
            title="Politique de confidentialité"
            subtitle="Comment CoriSchool collecte, utilise et protège les données personnelles des établissements, des élèves, des familles et des personnels."
            updatedAt="novembre 2026"
        >
            <div className="space-y-8">
                <InfoSection title="1. Responsable du traitement">
                    <p>
                        La plateforme CoriSchool est éditée par Coriyase
                        Technologies. Pour les données liées à la gestion
                        commerciale et au fonctionnement de la plateforme,
                        Coriyase Technologies agit en qualité de responsable de
                        traitement.
                    </p>
                    <p>
                        Pour les données scolaires saisies dans son espace,
                        l’établissement utilisateur détermine les finalités et
                        les moyens du traitement. Il lui appartient d’informer
                        les personnes concernées et de garantir la licéité des
                        traitements réalisés.
                    </p>
                </InfoSection>

                <InfoSection title="2. Données collectées">
                    <p>
                        Selon l’usage de la plateforme, les données suivantes
                        peuvent être traitées :
                    </p>
                    <ul className="list-disc space-y-2 pl-5">
                        <li>
                            données d’identification et de contact des
                            utilisateurs (nom, rôle, adresse e-mail, numéro de
                            téléphone) ;
                        </li>
                        <li>
                            données relatives aux élèves, aux familles et aux
                            personnels, telles que saisies par l’établissement
                            (identité, classe, coordonnées, présence, notes,
                            documents scolaires) ;
                        </li>
                        <li>
                            données de gestion scolaire et financière
                            nécessaires au fonctionnement de l’établissement ;
                        </li>
                        <li>
                            données techniques de connexion et d’usage
                            (journaux, adresse IP, type de navigateur, actions
                            réalisées) ;
                        </li>
                        <li>
                            informations transmises via le formulaire de
                            démonstration ou le service clientèle.
                        </li>
                    </ul>
                </InfoSection>

                <InfoSection title="3. Finalités du traitement">
                    <p>Les données sont utilisées pour :</p>
                    <ul className="list-disc space-y-2 pl-5">
                        <li>
                            fournir et sécuriser les services de gestion
                            scolaire ;
                        </li>
                        <li>
                            permettre la communication entre l’établissement,
                            les familles et les personnels ;
                        </li>
                        <li>
                            assurer le suivi commercial, la démonstration et le
                            support clientèle ;
                        </li>
                        <li>
                            améliorer la qualité, la stabilité et la sécurité
                            de la plateforme ;
                        </li>
                        <li>
                            respecter les obligations légales et comptables
                            applicables.
                        </li>
                    </ul>
                </InfoSection>

                <InfoSection title="4. Base légale">
                    <p>
                        Les traitements reposent, selon les cas, sur
                        l’exécution du contrat ou des mesures précontractuelles,
                        sur le consentement de la personne concernée, sur
                        l’intérêt légitime de l’éditeur ou de l’établissement à
                        assurer le bon fonctionnement du service, ou sur le
                        respect d’une obligation légale.
                    </p>
                </InfoSection>

                <InfoSection title="5. Destinataires et sous-traitants">
                    <p>
                        Les données sont accessibles aux utilisateurs autorisés
                        de l’établissement, aux équipes habilitées de Coriyase
                        Technologies et, le cas échéant, à des prestataires
                        techniques intervenant pour l’hébergement, l’envoi de
                        messages ou la maintenance. Ces prestataires sont tenus
                        à des obligations de confidentialité et de sécurité.
                    </p>
                    <p>
                        Les données ne sont ni vendues ni cédées à des tiers à
                        des fins commerciales.
                    </p>
                </InfoSection>

                <InfoSection title="6. Durée de conservation">
                    <p>
                        Les données sont conservées pendant la durée nécessaire
                        aux finalités poursuivies, puis archivées ou supprimées
                        conformément aux obligations légales. Les données de
                        gestion scolaire sont conservées selon les règles
                        propres à l’établissement et aux réglementations qui lui
                        sont applicables.
                    </p>
                </InfoSection>

                <InfoSection title="7. Sécurité">
                    <p>
                        Nous mettons en œuvre des mesures techniques et
                        organisationnelles adaptées pour protéger les données
                        contre l’accès non autorisé, la perte, l’altération ou
                        la divulgation. Chaque utilisateur contribue à cette
                        sécurité en protégeant ses identifiants et en signalant
                        toute anomalie.
                    </p>
                </InfoSection>

                <InfoSection title="8. Vos droits">
                    <p>
                        Selon la réglementation applicable, les personnes
                        concernées peuvent disposer de droits d’accès, de
                        rectification, d’effacement, de limitation,
                        d’opposition et de portabilité de leurs données. Pour
                        les données scolaires, la demande est à adresser en
                        priorité à l’établissement concerné.
                    </p>
                    <p>
                        Pour exercer vos droits ou poser une question sur le
                        traitement de vos données, contactez-nous à l’adresse{' '}
                        <a
                            href={`mailto:${email}`}
                            className="font-bold text-emerald-700 underline-offset-2 hover:underline"
                        >
                            {email}
                        </a>
                        .
                    </p>
                </InfoSection>

                <InfoSection title="9. Cookies et stockage local">
                    <p>
                        La plateforme peut utiliser des cookies ou un stockage
                        local strictement nécessaires à l’authentification, à
                        la sécurité et au bon fonctionnement de l’interface.
                        Aucun traceur publicitaire n’est utilisé pour le
                        fonctionnement du service.
                    </p>
                </InfoSection>

                <InfoSection title="10. Contact">
                    <p>
                        Pour toute question relative à cette politique ou pour
                        signaler un incident de sécurité, utilisez notre{' '}
                        <Link
                            href={route('public.contact')}
                            className="font-bold text-emerald-700 underline-offset-2 hover:underline"
                        >
                            page de contact
                        </Link>{' '}
                        ou écrivez à{' '}
                        <a
                            href={`mailto:${email}`}
                            className="font-bold text-emerald-700 underline-offset-2 hover:underline"
                        >
                            {email}
                        </a>
                        .
                    </p>
                </InfoSection>
            </div>
        </InfoPageLayout>
    );
}
