import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import PublicLayout from './layouts/PublicLayout';
import RouteTransition from './components/RouteTransition';
import PageLoadingFallback from './components/PageLoadingFallback';
import adminModuleRoutes from './pages/admin/adminModuleRoutes';
import HomePage from './pages/public/HomePage';

const AdminLayout = lazy(() => import('./layouts/AdminLayout'));
const AdminLoginPage = lazy(() => import('./pages/admin/AdminLoginPage'));
const AdminDashboardPage = lazy(() => import('./pages/admin/AdminDashboardPage'));
const AdminMembresPage = lazy(() => import('./pages/admin/AdminResourcePages').then((module) => ({ default: module.AdminMembresPage })));
const AdminModulePage = lazy(() => import('./pages/admin/AdminResourcePages').then((module) => ({ default: module.AdminModulePage })));
const AdminPresencesPage = lazy(() => import('./pages/admin/AdminResourcePages').then((module) => ({ default: module.AdminPresencesPage })));
const AdminProduitsPage = lazy(() => import('./pages/admin/AdminResourcePages').then((module) => ({ default: module.AdminProduitsPage })));
const AdminCommandesPage = lazy(() => import('./pages/admin/AdminResourcePages').then((module) => ({ default: module.AdminCommandesPage })));
const AdminStockPage = lazy(() => import('./pages/admin/AdminResourcePages').then((module) => ({ default: module.AdminStockPage })));
const SupportPage = lazy(() => import('./pages/admin/SupportPage'));
const BillingPage = lazy(() => import('./pages/admin/BillingPage'));

const PrivacyPage = lazy(() => import('./pages/public/PrivacyPage'));
const CookiesPage = lazy(() => import('./pages/public/CookiesPage'));
const InscriptionAbonnementPage = lazy(() => import('./pages/public/InscriptionAbonnementPage'));
const GymRegistrationPage = lazy(() => import('./pages/public/GymRegistrationPage'));
const EmailVerificationPage = lazy(() => import('./pages/public/EmailVerificationPage'));

function withPageLoading(page) {
    return <Suspense fallback={<PageLoadingFallback />}>{page}</Suspense>;
}

export default function App() {
    return (
        <Routes>
            <Route element={<PublicLayout />}>
                <Route index element={<HomePage />} />
                <Route path="politique-confidentialite" element={withPageLoading(<PrivacyPage />)} />
                <Route path="cookies" element={withPageLoading(<CookiesPage />)} />
                <Route path="inscription" element={withPageLoading(<InscriptionAbonnementPage />)} />
                <Route path="inscription/abonnement" element={withPageLoading(<InscriptionAbonnementPage />)} />
                <Route path="inscription-salle" element={withPageLoading(<GymRegistrationPage />)} />
                <Route path="verification-email" element={withPageLoading(<EmailVerificationPage />)} />
            </Route>
            <Route path="connexion" element={withPageLoading(<RouteTransition><AdminLoginPage /></RouteTransition>)} />
            <Route path="admin/login" element={withPageLoading(<RouteTransition><AdminLoginPage /></RouteTransition>)} />
            <Route path="admin" element={withPageLoading(<AdminLayout />)}>
                <Route index element={withPageLoading(<AdminDashboardPage />)} />
                <Route path="membres" element={withPageLoading(<AdminMembresPage />)} />
                <Route path="abonnements" element={withPageLoading(<AdminModulePage {...adminModuleRoutes.abonnements} />)} />
                <Route path="presences" element={withPageLoading(<AdminPresencesPage />)} />
                <Route path="produits" element={withPageLoading(<AdminProduitsPage />)} />
                <Route path="commandes" element={withPageLoading(<AdminCommandesPage />)} />
                <Route path="stock" element={withPageLoading(<AdminStockPage />)} />
                <Route path="rapports" element={withPageLoading(<AdminModulePage {...adminModuleRoutes.rapports} />)} />
                <Route path="aide" element={withPageLoading(<SupportPage />)} />
                <Route path="abonnement-saas" element={withPageLoading(<BillingPage />)} />
            </Route>
        </Routes>
    );
}
