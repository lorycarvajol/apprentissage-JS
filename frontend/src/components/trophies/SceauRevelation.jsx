import { useState, useEffect, useRef } from 'react';
import { CATEGORY_GLYPH, rareteDe, teintureDe } from '../../utils/heraldique';
import '../../styles/Revelation.css';

/**
 * Carte de révélation d'un sceau.
 *
 * Deux usages, un seul composant :
 *   - « revelation » : à l'arrivée sur la salle des trophées quand des titres
 *     ont été décrochés sans avoir été regardés. La carte les déroule un à un.
 *   - « consultation » : au clic sur un sceau déjà frappé, pour le relire.
 *
 * La différence n'est pas cosmétique. En révélation, la carte annonce un titre
 * nouveau et se compte (« 2 / 3 ») ; en consultation, elle se contente
 * d'expliquer. L'animation de scellage joue dans les deux cas — c'est elle qui
 * rend la lecture agréable — mais l'attente qu'elle impose n'est acceptable que
 * parce qu'elle est courte et qu'on peut fermer à tout moment.
 */

const SceauRevelation = ({ sceaux, mode = 'revelation', onClose }) => {
  const [index, setIndex] = useState(0);
  const boutonRef = useRef(null);
  const sceau = sceaux[index];

  // Rejoue le scellage à chaque changement de sceau : la clé du nœud change,
  // React remonte le médaillon, et les animations CSS repartent de zéro. Sans
  // ça, passer au titre suivant afficherait un sceau déjà frappé.
  const cle = sceau ? `${sceau.id}-${index}` : 'vide';

  useEffect(() => {
    boutonRef.current?.focus();
  }, [index]);

  useEffect(() => {
    const surTouche = (e) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', surTouche);
    return () => document.removeEventListener('keydown', surTouche);
  }, [onClose]);

  if (!sceau) return null;

  const rarete = rareteDe(sceau.points_reward);
  const teinture = teintureDe(sceau.category);
  const dernier = index >= sceaux.length - 1;

  const suivant = () => {
    if (dernier) {
      onClose();
    } else {
      setIndex((i) => i + 1);
    }
  };

  return (
    <div className="revelation-fond" onClick={onClose} role="presentation">
      <div
        className={`revelation-carte seal-category-${sceau.category} seal-rarity-${rarete.id}`}
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="revelation-titre"
      >
        {mode === 'revelation' && (
          <p className="revelation-annonce">
            Un sceau vient d'être frappé
            {sceaux.length > 1 && (
              <span className="revelation-compte">
                {index + 1} / {sceaux.length}
              </span>
            )}
          </p>
        )}

        {/* Le scellage : la cire coulée, la matrice frappée, l'empreinte
            révélée. L'onde est purement décorative, d'où aria-hidden. */}
        <div className="revelation-scene" key={cle}>
          <span className="revelation-onde" aria-hidden="true"></span>
          <span className="revelation-medaillon seal-medallion" aria-hidden="true">
            <span className="revelation-glyphe">
              {CATEGORY_GLYPH[sceau.category] || '✦'}
            </span>
          </span>
        </div>

        <h2 className="revelation-titre" id="revelation-titre">
          {sceau.name}
        </h2>

        <p className="revelation-description">{sceau.description}</p>

        {/* Ce qui « explique clairement le sceau » : ses deux dimensions,
            nommées et illustrées par la vignette correspondante, dans les mêmes
            termes que les légendes de la salle. */}
        <dl className="revelation-lecture">
          <div className="revelation-ligne">
            <dt>
              <span className="revelation-vignette trophy-legend-swatch" aria-hidden="true"></span>
              Sa cire
            </dt>
            <dd>
              <strong>{teinture.label}</strong>
              <span>{teinture.sens}</span>
            </dd>
          </div>

          <div className="revelation-ligne">
            <dt>
              <span
                className={`revelation-vignette trophy-legend-bezel seal-rarity-${rarete.id}`}
                aria-hidden="true"
              ></span>
              Sa sertissure
            </dt>
            <dd>
              <strong>
                {rarete.label} — {rarete.matiere}
              </strong>
              <span>{sceau.points_reward} points</span>
            </dd>
          </div>
        </dl>

        <div className="revelation-actions">
          <button type="button" className="revelation-bouton" onClick={suivant} ref={boutonRef}>
            {mode === 'revelation' && !dernier ? 'Titre suivant' : 'Fermer'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default SceauRevelation;
