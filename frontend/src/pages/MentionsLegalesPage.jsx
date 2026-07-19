import { Link } from 'react-router-dom';
import '../styles/Legal.css';

const MentionsLegalesPage = () => {
  return (
    <div className="legal-page">
      <div className="legal-container">
        <Link to="/dashboard" className="legal-back">← Retour</Link>
        <h1>Mentions légales</h1>
        <p className="legal-updated">Dernière mise à jour : 10 juillet 2026</p>

        <h2>Éditeur du site</h2>
        <p>
          Ce site est édité par un particulier, à titre non professionnel. Conformément à
          l'article 6-III de la loi n° 2004-575 du 21 juin 2004 pour la confiance dans
          l'économie numérique (LCEN), l'identité complète de l'éditeur n'est pas rendue
          publique mais est tenue à la disposition des autorités compétentes qui en feraient
          la demande, via l'hébergeur du site.
        </p>
        <p>
          Pour toute question relative au site, vous pouvez contacter l'éditeur à l'adresse
          suivante : <a href="mailto:lorycarvajolwebdev@gmail.com">lorycarvajolwebdev@gmail.com</a>.
        </p>

        <h2>Hébergement</h2>
        <p>
          [NOM DE L'HÉBERGEUR À COMPLÉTER]<br />
          [ADRESSE DE L'HÉBERGEUR À COMPLÉTER]<br />
          [CONTACT DE L'HÉBERGEUR À COMPLÉTER]
        </p>

        <h2>Directeur de la publication</h2>
        <p>L'éditeur du site, tel que défini ci-dessus.</p>

        <h2>Propriété intellectuelle</h2>
        <p>
          L'ensemble des contenus présents sur ce site (textes, illustrations, code source,
          exercices) est protégé par le droit d'auteur. Toute reproduction, même partielle,
          sans autorisation préalable est interdite, sauf usage strictement personnel dans le
          cadre de l'apprentissage proposé par la plateforme.
        </p>

        <h2>Contact</h2>
        <p>
          Pour toute question, réclamation ou exercice de vos droits, vous pouvez écrire à :{' '}
          <a href="mailto:lorycarvajolwebdev@gmail.com">lorycarvajolwebdev@gmail.com</a>.
        </p>
      </div>
    </div>
  );
};

export default MentionsLegalesPage;
