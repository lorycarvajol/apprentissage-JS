import { Link } from 'react-router-dom';
import '../styles/Legal.css';

const CGUPage = () => {
  return (
    <div className="legal-page">
      <div className="legal-container">
        <Link to="/dashboard" className="legal-back">← Retour</Link>
        <h1>Conditions Générales d'Utilisation</h1>
        <p className="legal-updated">Dernière mise à jour : 10 juillet 2026</p>

        <h2>1. Objet</h2>
        <p>
          Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et
          l'utilisation de la plateforme d'apprentissage de la Programmation Orientée Objet en
          JavaScript (ci-après « la Plateforme »). En créant un compte, vous acceptez sans réserve les
          présentes CGU.
        </p>

        <h2>2. Description du service</h2>
        <p>
          La Plateforme propose un parcours pédagogique structuré en modules et chapitres,
          comportant des contenus théoriques et des exercices pratiques de programmation,
          destinés à l'apprentissage de la POO en JavaScript.
        </p>

        <h2>3. Inscription et compte utilisateur</h2>
        <ul>
          <li>L'inscription nécessite un nom d'utilisateur, une adresse email valide et un mot de passe.</li>
          <li>L'adresse email doit être vérifiée avant la première connexion.</li>
          <li>Vous êtes responsable de la confidentialité de vos identifiants et de toute activité effectuée depuis votre compte.</li>
          <li>Un compte est strictement personnel et ne peut être partagé.</li>
        </ul>

        <h2>4. Comportement de l'utilisateur</h2>
        <p>Vous vous engagez à :</p>
        <ul>
          <li>fournir des informations exactes lors de votre inscription ;</li>
          <li>ne pas tenter de contourner les mesures de sécurité de la Plateforme (limitation de tentatives, authentification) ;</li>
          <li>ne pas utiliser la Plateforme à des fins illégales ou contraires à sa finalité pédagogique.</li>
        </ul>

        <h2>5. Disponibilité du service</h2>
        <p>
          La Plateforme est fournie « en l'état ». L'éditeur s'efforce d'assurer une
          disponibilité continue mais ne garantit pas une absence totale d'interruption,
          notamment en cas de maintenance ou d'incident technique indépendant de sa volonté.
        </p>

        <h2>6. Suppression du compte</h2>
        <p>
          Vous pouvez à tout moment supprimer votre compte et l'ensemble des données associées
          depuis la page « Mon compte ». Cette action est irréversible : votre progression, vos
          points et vos badges seront définitivement supprimés.
        </p>
        <p>
          L'éditeur se réserve le droit de suspendre ou supprimer un compte en cas de
          manquement grave aux présentes CGU.
        </p>

        <h2>7. Propriété intellectuelle</h2>
        <p>
          Les contenus pédagogiques (théories, exercices, illustrations) sont la propriété de
          l'éditeur de la Plateforme et sont mis à disposition pour un usage personnel
          d'apprentissage uniquement.
        </p>

        <h2>8. Données personnelles</h2>
        <p>
          Le traitement de vos données personnelles est décrit dans notre{' '}
          <Link to="/politique-confidentialite">Politique de confidentialité</Link>.
        </p>

        <h2>9. Modification des CGU</h2>
        <p>
          Les présentes CGU peuvent être modifiées à tout moment. Les utilisateurs seront
          informés de toute modification substantielle. La poursuite de l'utilisation de la
          Plateforme après modification vaut acceptation des nouvelles CGU.
        </p>

        <h2>10. Contact</h2>
        <p>
          Pour toute question relative aux présentes CGU :{' '}
          <a href="mailto:lorycarvajolwebdev@gmail.com">lorycarvajolwebdev@gmail.com</a>.
        </p>
      </div>
    </div>
  );
};

export default CGUPage;
