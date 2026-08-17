import { useState } from 'react';
import MainLayout from '../components/layout/MainLayout';
import ModuleManager from '../components/admin/ModuleManager';
import ChapitreManager from '../components/admin/ChapitreManager';
import TheorieManager from '../components/admin/TheorieManager';
import ExerciceManager from '../components/admin/ExerciceManager';
import '../styles/Admin.css';

const AdminPage = () => {
  const [activeTab, setActiveTab] = useState('modules');

  return (
    <MainLayout>
      <div className="admin-page">
        <div className="admin-header">
          <h1>🛠️ Administration</h1>
          <p>Gestion du contenu pédagogique</p>
        </div>

        <div className="admin-tabs">
          <button
            className={`admin-tab ${activeTab === 'modules' ? 'admin-tab-active' : ''}`}
            onClick={() => setActiveTab('modules')}
          >
            📚 Modules
          </button>
          <button
            className={`admin-tab ${activeTab === 'chapitres' ? 'admin-tab-active' : ''}`}
            onClick={() => setActiveTab('chapitres')}
          >
            📖 Chapitres
          </button>
          <button
            className={`admin-tab ${activeTab === 'theories' ? 'admin-tab-active' : ''}`}
            onClick={() => setActiveTab('theories')}
          >
            📝 Théories
          </button>
          <button
            className={`admin-tab ${activeTab === 'exercices' ? 'admin-tab-active' : ''}`}
            onClick={() => setActiveTab('exercices')}
          >
            💻 Exercices
          </button>
        </div>

        {/*
          Les 4 managers restent montés en permanence (visibilité en CSS,
          jamais démontés) : sans ça, chaque clic d'onglet détruisait le
          composant actif et le suivant remontait de zéro, refaisait son
          fetch complet et réaffichait un spinner de chargement à chaque
          changement d'onglet — perçu comme un "glitch" de la fenêtre.
          Garder les composants montés élimine le refetch et le spinner
          après la première visite de chaque onglet, et conserve aussi la
          sélection en cours dans la sidebar quand on revient sur un onglet.

          Contrepartie découverte à l'usage : un composant jamais démonté ne
          recharge jamais ses données. Un module créé dans l'onglet Modules
          n'apparaissait donc pas dans la liste déroulante du formulaire de
          l'onglet Chapitres, qui affichait ce qu'elle avait chargé au premier
          montage de la page. Il fallait recharger la page entière.

          D'où `isActive` : chaque manager recharge ses données quand son
          onglet redevient visible, mais *silencieusement* — sans repasser en
          état de chargement, donc sans réintroduire le spinner que ce montage
          permanent servait précisément à supprimer.
        */}
        <div className="admin-content" style={{ display: activeTab === 'modules' ? 'block' : 'none' }}>
          <ModuleManager isActive={activeTab === 'modules'} />
        </div>
        <div className="admin-content" style={{ display: activeTab === 'chapitres' ? 'block' : 'none' }}>
          <ChapitreManager isActive={activeTab === 'chapitres'} />
        </div>
        <div className="admin-content" style={{ display: activeTab === 'theories' ? 'block' : 'none' }}>
          <TheorieManager isActive={activeTab === 'theories'} />
        </div>
        <div className="admin-content" style={{ display: activeTab === 'exercices' ? 'block' : 'none' }}>
          <ExerciceManager isActive={activeTab === 'exercices'} />
        </div>
      </div>
    </MainLayout>
  );
};

export default AdminPage;
