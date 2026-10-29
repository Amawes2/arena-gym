import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import api from '../../api/client';
import ArenaLogo from '../../components/brand/ArenaLogo';
import HoneypotField from '../../components/forms/HoneypotField';
import FieldError from '../../components/forms/FieldError';
import TurnstileWidget from '../../components/forms/TurnstileWidget';
import Button from '../../components/ui/Button';

export default function AdminLoginPage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [twoFactorCode, setTwoFactorCode] = useState('');
    const [website, setWebsite] = useState('');
    const [turnstileToken, setTurnstileToken] = useState('');
    const [widgetKey, setWidgetKey] = useState(0);
    const [magicLinkMode, setMagicLinkMode] = useState(false);
    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});
    const [magicMessage, setMagicMessage] = useState('');
    const [loading, setLoading] = useState(false);
    const googleErrorMessages = {
        not_configured: 'La connexion Google n’est pas encore configurée sur le serveur.',
        cancelled: 'La connexion Google a été annulée.',
        failed: 'La connexion Google a échoué. Veuillez réessayer.',
        unverified: 'Google n’a pas confirmé votre adresse e-mail.',
        no_account: 'Aucun compte administrateur Arena ne correspond à cette adresse Google.',
        two_factor: 'La double authentification est activée sur ce compte. Connectez-vous avec votre mot de passe et votre code 2FA.',
    };
    const googleError = googleErrorMessages[searchParams.get('google_error')];

    const submit = async (event) => {
        event.preventDefault();
        setError('');
        setFieldErrors({});
        setMagicMessage('');
        setLoading(true);

        try {
            if (magicLinkMode) {
                const { data } = await api.post('/auth/magic-link', {
                    email,
                    website,
                    turnstile_token: turnstileToken,
                });

                setMagicMessage(data.message);
                return;
            }

            const { data } = await api.post('/auth/login', {
                email,
                password,
                two_factor_code: twoFactorCode,
                website,
                turnstile_token: turnstileToken,
            });

            if (data.redirect_url) {
                window.location.assign(data.redirect_url);
            }
        } catch (requestError) {
            if (requestError.response?.data?.verification_required) {
                navigate('/verification-email', { state: { email: requestError.response.data.email } });
                return;
            }

            const validationErrors = requestError.response?.data?.errors;
            if (validationErrors) {
                setFieldErrors(Object.fromEntries(Object.entries(validationErrors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages])));
                const unassignedError = Object.entries(validationErrors).find(([field]) => !['email', 'password', 'two_factor_code'].includes(field))?.[1];
                if (unassignedError) {
                    setError(Array.isArray(unassignedError) ? unassignedError[0] : unassignedError);
                }
            } else {
                setError('Identifiants incorrects ou vérification anti-robot expirée. Réessayez.');
            }
        } finally {
            setLoading(false);
            setTurnstileToken('');
            setWidgetKey((current) => current + 1);
        }
    };

    const toggleMagicLinkMode = () => {
        setMagicLinkMode((current) => !current);
        setError('');
        setFieldErrors({});
        setMagicMessage('');
        setTurnstileToken('');
        setWidgetKey((current) => current + 1);
    };

    const inputClass = 'mt-2 w-full rounded-lg border border-elite-border-light bg-elite-bg px-4 py-3 text-sm text-elite-text placeholder:text-elite-disabled focus:border-elite-gold focus:outline-none focus:ring-2 focus:ring-elite-gold/20';

    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-elite-bg px-5 py-12 sm:px-6">
            <div className="arena-glow pointer-events-none absolute -right-32 -top-40 h-[26rem] w-[26rem] rounded-full" />
            <form onSubmit={submit} className="arena-login-enter relative w-full max-w-md rounded-2xl border border-elite-border-light bg-elite-card p-7 shadow-2xl shadow-black/30 sm:p-9">
                <HoneypotField value={website} onChange={(event) => setWebsite(event.target.value)} />
                <Link to="/" className="flex items-center justify-center gap-2.5 text-lg font-bold tracking-[0.16em] text-elite-text">
                    <ArenaLogo size={40} />
                    ARENA GYM
                </Link>
                <h1 className="mt-8 text-center text-2xl font-semibold text-elite-text">Connexion à votre espace</h1>
                <p className="mt-2 text-center text-sm leading-6 text-elite-muted">
                    {magicLinkMode ? 'Recevez un lien de connexion valable pendant dix minutes.' : 'Utilisez les identifiants de votre compte administrateur.'}
                </p>

                <div className="mt-8 space-y-5">
                    {googleError && <p role="alert" className="rounded-lg border border-elite-danger/20 bg-elite-danger-bg px-4 py-3 text-sm text-elite-danger">{googleError}</p>}
                    <a href="/auth/google" className="flex w-full items-center justify-center gap-3 rounded-lg border border-elite-border-light bg-elite-bg px-4 py-3 text-sm font-semibold text-elite-text transition hover:border-elite-gold/60">
                        <svg aria-hidden="true" viewBox="0 0 48 48" className="h-5 w-5">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z" transform="translate(0 5)" />
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.76 7.18l7.73 6C44.42 38.1 46.98 31.9 46.98 24.55Z" />
                            <path fill="#FBBC05" d="M10.53 28.59a14.4 14.4 0 0 1 0-9.18l-7.98-6.19a23.9 23.9 0 0 0 0 21.56l7.98-6.19Z" transform="translate(0 5)" />
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-6c-2.14 1.44-4.89 2.3-8.18 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48Z" transform="translate(0 -5)" />
                        </svg>
                        Continuer avec Google
                    </a>
                    <p className="text-center text-xs text-elite-muted">ou connectez-vous avec votre e-mail</p>
                    <label className="block text-sm font-medium text-elite-text">
                        Adresse e-mail
                        <input type="email" name="email" autoComplete="username" autoFocus placeholder="vous@exemple.com" className={inputClass} value={email} onChange={(event) => {
                            setEmail(event.target.value);
                            setFieldErrors((current) => ({ ...current, email: undefined }));
                        }} required aria-invalid={Boolean(fieldErrors.email)} aria-describedby={fieldErrors.email ? 'login-email-error' : undefined} />
                        <FieldError id="login-email-error" message={fieldErrors.email} />
                    </label>
                    {!magicLinkMode && (
                        <label className="block text-sm font-medium text-elite-text">
                            Mot de passe
                            <input type="password" name="password" autoComplete="current-password" placeholder="Votre mot de passe" className={inputClass} value={password} onChange={(event) => {
                                setPassword(event.target.value);
                                setFieldErrors((current) => ({ ...current, password: undefined }));
                            }} required aria-invalid={Boolean(fieldErrors.password)} aria-describedby={fieldErrors.password ? 'login-password-error' : undefined} />
                            <FieldError id="login-password-error" message={fieldErrors.password} />
                        </label>
                    )}
                    {!magicLinkMode && (
                        <label className="block text-sm font-medium text-elite-text">
                            Code 2FA ou code de secours (si activé)
                            <input type="text" name="two_factor_code" inputMode="numeric" autoComplete="one-time-code" placeholder="Code à 6 chiffres ou code de secours" className={inputClass} value={twoFactorCode} onChange={(event) => {
                                setTwoFactorCode(event.target.value);
                                setFieldErrors((current) => ({ ...current, two_factor_code: undefined }));
                            }} aria-invalid={Boolean(fieldErrors.two_factor_code)} aria-describedby={fieldErrors.two_factor_code ? 'login-two-factor-error' : undefined} />
                            <FieldError id="login-two-factor-error" message={fieldErrors.two_factor_code} />
                        </label>
                    )}
                    <TurnstileWidget key={widgetKey} action="login" onToken={setTurnstileToken} />
                    {error && <p role="alert" className="rounded-lg border border-elite-danger/20 bg-elite-danger-bg px-4 py-3 text-sm text-elite-danger">{error}</p>}
                    {magicMessage && <p role="status" className="rounded-lg bg-elite-success-bg px-4 py-3 text-sm text-elite-success">{magicMessage}</p>}
                    <Button type="submit" className="w-full !py-3.5" disabled={loading}>
                        {loading ? 'Envoi en cours…' : magicLinkMode ? 'Envoyer le lien magique' : 'Se connecter'}
                    </Button>
                    <button type="button" className="w-full text-sm text-elite-gold hover:underline" onClick={toggleMagicLinkMode}>
                        {magicLinkMode ? 'Utiliser mon mot de passe' : 'Se connecter avec un lien magique'}
                    </button>
                </div>

                <p className="mt-7 text-center text-sm text-elite-muted">
                    Votre salle n’est pas encore inscrite ? <Link className="font-medium text-elite-gold hover:underline" to="/inscription-salle">Créer un espace</Link>
                </p>
                <p className="mt-7 border-t border-elite-border pt-5 text-center text-xs leading-5 text-elite-muted">
                    Consultez notre <Link className="text-elite-text hover:text-elite-gold" to="/politique-confidentialite">politique de confidentialité</Link>.
                </p>
            </form>
        </div>
    );
}
