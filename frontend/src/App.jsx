import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import ProtectedRoute from './components/common/ProtectedRoute';
import ScrollToTop from './components/common/ScrollToTop';
import AdminRoute from './components/auth/AdminRoute';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import VerifyEmailPage from './pages/VerifyEmailPage';
import DashboardPage from './pages/DashboardPage';
import ModulesPage from './pages/ModulesPage';
import ModuleDetailPage from './pages/ModuleDetailPage';
import ChapitrePage from './pages/ChapitrePage';
import AdminPage from './pages/AdminPage';
import PreviewPage from './pages/PreviewPage';
import AccountPage from './pages/AccountPage';
import TrophyRoomPage from './pages/TrophyRoomPage';
import MentionsLegalesPage from './pages/MentionsLegalesPage';
import CGUPage from './pages/CGUPage';
import PolitiqueConfidentialitePage from './pages/PolitiqueConfidentialitePage';
import './App.css';

function App() {
  return (
    <Router>
      <ThemeProvider>
        <AuthProvider>
          <ScrollToTop />
          <Routes>
            {/* Routes publiques */}
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/reset-password" element={<ResetPasswordPage />} />
            <Route path="/verify-email" element={<VerifyEmailPage />} />

            {/* Pages légales (publiques) */}
            <Route path="/mentions-legales" element={<MentionsLegalesPage />} />
            <Route path="/cgu" element={<CGUPage />} />
            <Route path="/politique-confidentialite" element={<PolitiqueConfidentialitePage />} />

            {/* Routes protégées */}
            <Route
              path="/dashboard"
              element={
                <ProtectedRoute>
                  <DashboardPage />
                </ProtectedRoute>
              }
            />

            <Route
              path="/compte"
              element={
                <ProtectedRoute>
                  <AccountPage />
                </ProtectedRoute>
              }
            />

            <Route
              path="/trophies"
              element={
                <ProtectedRoute>
                  <TrophyRoomPage />
                </ProtectedRoute>
              }
            />

            {/* Routes du contenu pédagogique */}
            <Route
              path="/modules"
              element={
                <ProtectedRoute>
                  <ModulesPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/modules/:id"
              element={
                <ProtectedRoute>
                  <ModuleDetailPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/chapitres/:id"
              element={
                <ProtectedRoute>
                  <ChapitrePage />
                </ProtectedRoute>
              }
            />

            {/* Route admin */}
            <Route
              path="/admin"
              element={
                <AdminRoute>
                  <AdminPage />
                </AdminRoute>
              }
            />

            {/* Route de prévisualisation (publique pour faciliter l'accès depuis la fenêtre admin) */}
            <Route path="/preview" element={<PreviewPage />} />

            {/* Redirection par défaut */}
            <Route path="/" element={<Navigate to="/dashboard" replace />} />

            {/* Route 404 */}
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </AuthProvider>
      </ThemeProvider>
    </Router>
  );
}

export default App;
