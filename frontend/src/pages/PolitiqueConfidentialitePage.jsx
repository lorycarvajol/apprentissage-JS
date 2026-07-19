import { Link } from 'react-router-dom';
import '../styles/Legal.css';

const PolitiqueConfidentialitePage = () => {
  return (
    <div className="legal-page">
      <div className="legal-container">
        <Link to="/dashboard" className="legal-back">← Retour</Link>
        <h1>Politique de confidentialité</h1>
        <p className="legal-updated">Dernière mise à jour : 10 juillet 2026</p>

        <p>
          Cette politique explique quelles données personnelles sont collectées sur cette
          Plateforme, pourquoi, combien de temps elles sont conservées, et comment exercer vos
          droits, conformément au Règlement Général sur la Protection des Données (RGPD).
        </p>

        <h2>1. Responsable du traitement</h2>
        <p>
          Le responsable du traitement est l'éditeur de la Plateforme (voir les{' '}
          <Link to="/mentions-legales">mentions légales</Link>), contactable à l'adresse :{' '}
          <a href="mailto:lorycarvajolwebdev@gmail.com">lorycarvajolwebdev@gmail.com</a>.
        </p>

        <h2>2. Données collectées</h2>
        <table className="legal-table">
          <thead>
            <tr>
              <th>Donnée</th>
              <th>Finalité</th>
              <th>Base légale</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Nom d'utilisateur, email, mot de passe (haché)</td>
              <td>Création et gestion du compte</td>
              <td>Exécution du contrat</td>
            </tr>
            <tr>
              <td>Prénom, nom (optionnels)</td>
              <td>Personnalisation de l'affichage</td>
              <td>Consentement</td>
            </tr>
            <tr>
              <td>Adresse IP, journal des connexions</td>
              <td>Sécurité, détection d'usage frauduleux, limitation des tentatives</td>
              <td>Intérêt légitime</td>
            </tr>
            <tr>
              <td>Progression, points, badges, exercices soumis</td>
              <td>Fonctionnement du parcours pédagogique et de la gamification</td>
              <td>Exécution du contrat</td>
            </tr>
          </tbody>
        </table>

        <h2>3. Mot de passe et sécurité</h2>
        <p>
          Votre mot de passe n'est jamais stocké en clair : il est haché avec un algorithme
          robuste (bcrypt/Argon2 selon la configuration du serveur) qui rend sa récupération
          impossible, y compris par l'éditeur lui-même. Les jetons de session (rafraîchissement
          de connexion, réinitialisation de mot de passe, vérification d'email) sont également
          stockés sous forme hachée, jamais en clair.
        </p>

        <h2>4. Cookies</h2>
        <p>
          La Plateforme utilise un unique cookie, strictement nécessaire au fonctionnement du
          service : un cookie technique <code>httpOnly</code> contenant un jeton de
          reconnexion, qui permet de rester connecté sans ressaisir vos identifiants. Ce cookie
          n'est pas utilisé à des fins publicitaires ou de mesure d'audience, et ne nécessite
          donc pas de recueil de consentement préalable au titre de la réglementation ePrivacy
          (cookies strictement nécessaires au service demandé par l'utilisateur).
        </p>

        <h2>5. Destinataires des données</h2>
        <p>
          Vos données ne sont ni vendues ni partagées avec des tiers à des fins commerciales.
          Elles peuvent être transmises à des prestataires techniques strictement nécessaires
          au fonctionnement du service (par exemple, un prestataire d'envoi d'emails pour la
          vérification de compte et la réinitialisation de mot de passe), soumis à des
          obligations de confidentialité équivalentes.
        </p>

        <h2>6. Durée de conservation</h2>
        <ul>
          <li>Données de compte : conservées tant que le compte est actif, supprimées définitivement en cas de suppression du compte.</li>
          <li>Journal des connexions : conservé 12 mois glissants à des fins de sécurité.</li>
          <li>Jetons de session : supprimés automatiquement à expiration ou lors de la déconnexion.</li>
        </ul>

        <h2>7. Vos droits</h2>
        <p>Conformément au RGPD, vous disposez des droits suivants sur vos données :</p>
        <ul>
          <li><strong>Droit d'accès</strong> : obtenir une copie de vos données.</li>
          <li><strong>Droit de rectification</strong> : corriger des données inexactes.</li>
          <li><strong>Droit à l'effacement</strong> (droit à l'oubli) : supprimer votre compte et toutes les données associées.</li>
          <li><strong>Droit à la portabilité</strong> : récupérer vos données dans un format structuré et réutilisable.</li>
          <li><strong>Droit d'opposition et de limitation</strong> du traitement, dans les cas prévus par la loi.</li>
        </ul>
        <p>
          Les droits d'accès, de suppression et de portabilité peuvent être exercés directement
          et immédiatement depuis la page <Link to="/compte">Mon compte</Link>, sans avoir à en
          faire la demande. Pour toute autre demande, écrivez à{' '}
          <a href="mailto:lorycarvajolwebdev@gmail.com">lorycarvajolwebdev@gmail.com</a>.
        </p>
        <p>
          Vous disposez également du droit d'introduire une réclamation auprès de la
          Commission Nationale de l'Informatique et des Libertés (CNIL) — <em>cnil.fr</em> —
          si vous estimez que le traitement de vos données n'est pas conforme à la
          réglementation.
        </p>

        <h2>8. Sécurité</h2>
        <p>
          Des mesures techniques sont mises en œuvre pour protéger vos données : mots de passe
          hachés, jetons de session hachés et à rotation, limitation du nombre de tentatives de
          connexion, connexions chiffrées recommandées (HTTPS).
        </p>

        <h2>9. Contact</h2>
        <p>
          Pour toute question relative à cette politique ou à vos données personnelles :{' '}
          <a href="mailto:lorycarvajolwebdev@gmail.com">lorycarvajolwebdev@gmail.com</a>.
        </p>
      </div>
    </div>
  );
};

export default PolitiqueConfidentialitePage;
