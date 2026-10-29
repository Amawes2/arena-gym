import { useEffect, useState } from 'react';
import { Link, NavLink, Navigate, Outlet } from 'react-router-dom';
import { IconBell, IconClipboardCheck, IconLayoutDashboard, IconMenu2, IconPackage, IconUsers } from '@tabler/icons-react';
import AdminSidebar from '../components/admin/AdminSidebar';
import FirstLoginTour from '../components/admin/FirstLoginTour';
import RouteTransition from '../components/RouteTransition';
import api from '../api/client';

export default function AdminLayout() {
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [hasAdminAccess, setHasAdminAccess] = useState(false);
    const [user, setUser] = useState(null);
    const [gyms, setGyms] = useState([]);
    const [selectedGymId, setSelectedGymId] = useState('');
    const [billingStatus, setBillingStatus] = useState(null);
    const [verificationMessage, setVerificationMessage] = useState('');
    const [isResendingVerification, setIsResendingVerification] = useState(false);

    useEffect(() => {
        let ignore = false;

        api.get('/auth/user')
            .then(({ data }) => {
                if (ignore) {
                    return;
                }

                const accessibleGyms = Array.isArray(data.gyms) ? data.gyms : [];
                const storedGymId = localStorage.getItem('elitefit_gym_id');
                const selectedGym = accessibleGyms.find((gym) => String(gym.id) === storedGymId) ?? accessibleGyms[0];

                if (selectedGym) {
                    localStorage.setItem('elitefit_gym_id', String(selectedGym.id));
                    setSelectedGymId(String(selectedGym.id));
                } else {
                    localStorage.removeItem('elitefit_gym_id');
                }

                setUser(data.user);
                setGyms(accessibleGyms);
                setHasAdminAccess(['admin', 'receptionist', 'super_admin'].includes(data.user?.role));
            })
            .catch(() => {
                if (!ignore) {
                    setHasAdminAccess(false);
                    localStorage.removeItem('elitefit_gym_id');
                }
            })
            .finally(() => {
                if (!ignore) {
                    setIsLoading(false);
                }
            });

        return () => {
            ignore = true;
        };
    }, []);

    useEffect(() => {
        if (!hasAdminAccess || !selectedGymId) {
            setBillingStatus(null);
            return undefined;
        }

        let ignore = false;
        setBillingStatus(null);
        const refreshStatus = () => api.get('/billing/status', { params: { gym_id: selectedGymId } })
            .then(({ data }) => {
                if (!ignore) {
                    setBillingStatus(data);
                }
            })
            .catch(() => {
                if (!ignore) {
                    setBillingStatus(null);
                }
            });

        refreshStatus();
        const interval = window.setInterval(refreshStatus, 60000);

        return () => {
            ignore = true;
            window.clearInterval(interval);
        };
    }, [hasAdminAccess, selectedGymId]);

    if (isLoading) {
        return <div className="arena-center-grid min-h-screen place-items-center bg-elite-bg text-sm text-elite-muted">Vérification de votre session…</div>;
    }

    if (!hasAdminAccess) {
        return <Navigate to="/admin/login" replace />;
    }

    const selectedGym = gyms.find((gym) => String(gym.id) === selectedGymId);
    const initials = user?.name
        ?.trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase() || 'A';

    const selectGym = (event) => {
        const nextGymId = event.target.value;
        localStorage.setItem('elitefit_gym_id', nextGymId);
        setSelectedGymId(nextGymId);
    };

    const resendVerification = async () => {
        if (!user?.email || isResendingVerification) {
            return;
        }

        setIsResendingVerification(true);
        setVerificationMessage('');

        try {
            const { data } = await api.post('/auth/email-verification/resend', { email: user.email });
            setVerificationMessage(data.message);
        } catch (requestError) {
            setVerificationMessage(requestError.response?.data?.message || 'Le code n’a pas pu être envoyé. Réessayez dans quelques instants.');
        } finally {
            setIsResendingVerification(false);
        }
    };

    return (
        <div className="arena-admin flex min-h-screen bg-elite-bg">
            {isMenuOpen && (
                <button
                    type="button"
                    aria-label="Fermer le menu"
                    onClick={() => setIsMenuOpen(false)}
                    className="fixed inset-0 z-30 bg-black/50 lg:hidden"
                />
            )}
            <AdminSidebar isOpen={isMenuOpen} onClose={() => setIsMenuOpen(false)} />
            <div className="flex min-w-0 flex-1 flex-col">
                <header className="arena-admin-topbar sticky top-0 z-20 d-flex align-items-center justify-content-between gap-3 px-3 lg:px-4">
                    <div className="d-flex min-w-0 align-items-center gap-3">
                        <button
                            type="button"
                            aria-label="Ouvrir le menu"
                            aria-expanded={isMenuOpen}
                            onClick={() => setIsMenuOpen(true)}
                            className="arena-admin-icon-button btn btn-outline d-inline-flex flex-shrink-0 align-items-center justify-content-center p-0 lg:hidden"
                        >
                            <IconMenu2 size={19} />
                        </button>
                        <label className="d-flex min-w-0 align-items-center gap-2">
                            <span className="small fw-semibold text-nowrap text-dark d-none sm:d-inline">Salle active</span>
                            <select
                                aria-label="Sélectionner une salle"
                                className="arena-admin-gym-select form-select form-select-sm"
                                value={selectedGymId}
                                onChange={selectGym}
                                disabled={gyms.length < 2}
                            >
                                {gyms.map((gym) => (
                                    <option key={gym.id} value={gym.id}>{gym.name}</option>
                                ))}
                                {gyms.length === 0 && <option value="">Aucune salle disponible</option>}
                            </select>
                        </label>
                    </div>
                    <div className="d-flex align-items-center gap-2 gap-sm-3">
                        <a href="/admin/rapports" aria-label="Voir les rapports et alertes" className="arena-admin-icon-button btn btn-outline d-inline-flex align-items-center justify-content-center p-0">
                            <IconBell size={18} />
                        </a>
                        <span className="border-start d-none sm:d-block" style={{ height: 30 }} />
                        <span className="arena-admin-avatar d-inline-flex align-items-center justify-content-center rounded-circle">{initials}</span>
                        <span className="d-none sm:d-block">
                            <span className="d-block small fw-semibold text-dark">{user?.name}</span>
                            <span className="d-block" style={{ color: '#687280', fontSize: '0.7rem' }}>Espace administrateur</span>
                        </span>
                    </div>
                </header>
                <main className="arena-admin-main d-flex min-w-0 flex-grow-1 flex-column">
                    {user?.email_verification_required && (
                        <div className="mx-3 mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100 lg:mx-6">
                            <span><strong>Adresse e-mail à vérifier.</strong> Votre espace est en consultation seule jusqu’à la confirmation de votre adresse.</span>
                            <div className="flex flex-wrap items-center gap-3">
                                {verificationMessage && <span role="status">{verificationMessage}</span>}
                                <button type="button" onClick={resendVerification} disabled={isResendingVerification} className="font-semibold text-elite-gold underline disabled:opacity-60">
                                    {isResendingVerification ? 'Envoi…' : 'Renvoyer le code'}
                                </button>
                                <Link to="/verification-email" state={{ email: user.email }} className="font-semibold text-elite-gold underline">Saisir le code</Link>
                            </div>
                        </div>
                    )}
                    {billingStatus?.gym && !billingStatus.gym.is_approved && (
                        <div className="mx-3 mt-3 rounded-xl border border-amber-300/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100 lg:mx-6">
                            <strong>Salle en attente de validation.</strong> Après vérification de votre e-mail, vous pourrez créer jusqu’à {Math.max(0, (billingStatus.gym.subscriptions_limit ?? 3) - (billingStatus.gym.subscriptions_created ?? 0))} abonnement(s) de membre avant validation.
                            <a href="/admin/aide" className="ml-2 font-semibold text-elite-gold underline">Contacter le Super Admin</a>
                        </div>
                    )}
                    {billingStatus?.gym?.is_read_only && (
                        <div className="mx-3 mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-100 lg:mx-6">
                            <span><strong>Espace en lecture seule.</strong> Vos anciennes données restent consultables; les modifications sont suspendues jusqu’au renouvellement.</span>
                            <a href="/admin/abonnement-saas" className="rounded-lg bg-elite-gold px-3 py-2 font-semibold text-slate-950">Renouveler l’abonnement</a>
                        </div>
                    )}
                    <div key={selectedGym?.id ?? 'no-gym'} className="d-flex min-w-0 flex-grow-1 flex-column">
                        <RouteTransition className="d-flex min-w-0 flex-grow-1 flex-column">
                            <Outlet />
                        </RouteTransition>
                    </div>
                </main>
            </div>
            <nav className="arena-mobile-nav" aria-label="Navigation principale">
                {[
                    { to: '/admin', label: 'Accueil', icon: IconLayoutDashboard, end: true },
                    { to: '/admin/membres', label: 'Membres', icon: IconUsers },
                    { to: '/admin/presences', label: 'Présences', icon: IconClipboardCheck },
                    { to: '/admin/stock', label: 'Stock', icon: IconPackage },
                ].map(({ to, label, icon: Icon, end }) => (
                    <NavLink key={to} to={to} end={end} aria-label={label}>
                        <Icon size={19} stroke={1.8} />
                        <span>{label}</span>
                    </NavLink>
                ))}
            </nav>
            {user?.role !== 'super_admin' && !user?.onboarding_completed_at && (
                <FirstLoginTour onComplete={() => setUser((current) => ({ ...current, onboarding_completed_at: new Date().toISOString() }))} />
            )}
        </div>
    );
}
