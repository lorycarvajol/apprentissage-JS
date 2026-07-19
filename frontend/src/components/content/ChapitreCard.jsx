import { Link } from 'react-router-dom';
import '../../styles/Content.css';

const ChapitreCard = ({ chapitre, moduleId }) => {
  return (
    <Link
      to={`/chapitres/${chapitre.id}`}
      className="chapitre-card"
    >
      <div className="chapitre-header">
        <div className="chapitre-number">Chapitre {chapitre.order_index}</div>
        {!chapitre.is_published && (
          <span className="badge badge-draft">Brouillon</span>
        )}
      </div>

      <h4 className="chapitre-title">{chapitre.title}</h4>

      {chapitre.description && (
        <p className="chapitre-description">{chapitre.description}</p>
      )}

      <div className="chapitre-footer">
        <span className="chapitre-link-text">Commencer →</span>
      </div>
    </Link>
  );
};

export default ChapitreCard;
