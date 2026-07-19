const SECTIONS = [
  {
    title: 'Bases',
    items: [
      { label: 'Variable', code: 'const nom = "Alice";' },
      { label: 'Afficher', code: 'console.log(nom);' },
      { label: 'Concaténer du texte', code: 'console.log(`Bonjour ${nom}`);' },
      { label: 'Condition', code: 'if (age >= 18) {\n  // ...\n}' },
      { label: 'Boucle', code: 'for (const item of items) {\n  // ...\n}' },
    ],
  },
  {
    title: 'Classe',
    items: [
      { label: 'Déclarer une classe', code: 'class Livre {\n\n}' },
      { label: 'Propriété (publique)', code: 'titre;' },
      { label: 'Propriété privée', code: '#titre;' },
      { label: 'Constructeur', code: 'constructor(titre) {\n  this.titre = titre;\n}' },
      { label: 'Méthode', code: 'afficher() {\n  return this.titre;\n}' },
      { label: 'Créer un objet', code: 'const livre = new Livre("1984");' },
      { label: 'Appeler une méthode', code: 'livre.afficher();' },
      { label: 'Propriété/méthode statique', code: 'static compteur;\nstatic maMethode() {}' },
    ],
  },
  {
    title: 'Héritage',
    items: [
      { label: 'Hériter d\'une classe', code: 'class Roman extends Livre {\n\n}' },
      { label: 'Appeler le parent', code: 'super(titre);' },
      { label: 'Redéfinir une méthode', code: 'afficher() {\n  return `${super.afficher()} (roman)`;\n}' },
    ],
  },
  {
    title: 'Fonctions & modules',
    items: [
      { label: 'Fonction fléchée', code: 'const carre = (x) => x * x;' },
      { label: 'Paramètre par défaut', code: 'function saluer(nom = "vous") {}' },
      { label: 'Exporter', code: 'export class Livre {}' },
      { label: 'Importer', code: 'import { Livre } from "./livre.js";' },
      { label: 'Destructuration', code: 'const { titre, auteur } = livre;' },
    ],
  },
];

const JsCheatSheet = ({ onClose }) => {
  return (
    <div className="cheat-sheet-overlay" onClick={onClose}>
      <div className="cheat-sheet-panel" onClick={(e) => e.stopPropagation()}>
        <div className="cheat-sheet-header">
          <h2>📎 Aide-mémoire JavaScript</h2>
          <button type="button" className="cheat-sheet-close" onClick={onClose} aria-label="Fermer">
            ✕
          </button>
        </div>

        <div className="cheat-sheet-content">
          {SECTIONS.map((section) => (
            <div key={section.title} className="cheat-sheet-section">
              <h3>{section.title}</h3>
              {section.items.map((item) => (
                <div key={item.label} className="cheat-sheet-item">
                  <span className="cheat-sheet-label">{item.label}</span>
                  <pre className="cheat-sheet-code">{item.code}</pre>
                </div>
              ))}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default JsCheatSheet;
