import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import api from '../../api/client';
import FieldError from '../../components/forms/FieldError';
import Button from '../../components/ui/Button';

export default function EmailVerificationPage() {
    const location = useLocation();
    const [email, setEmail] = useState(location.state?.email || '');
    const [code, setCode] = useState('');
    const [error, setError] = useState(location.state?.deliveryIssue || '');
    const [fieldErrors, setFieldErrors] = useState({});
    const [message, setMessage] = useState('');
    const [loading, setLoading] = useState(false);
    const [resending, setResending] = useState(false);

    const verify = async (event) => {
        event.preventDefault();
        setError('');
        setMessage('');
        setFieldErrors({});
        setLoading(true);

        try {
            const { data } = await api.post('/auth/email-verification/verify', { email, code });
            window.location.assign(data.redirect_url || '/');
        } catch (requestError) {
            const responseData = requestError.response?.data;
            const validationErrors = responseData?.errors;
            if (validationErrors) {
                setFieldErrors(Object.fromEntries(Object.entries(validationErrors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages])));
                if (responseData?.message) {
                    setError(responseData.message);
                }
            } else {
                setError(responseData?.message || 'La vérification a échoué. Réessayez.');
            }
        } finally {
            setLoading(false);
        }
    };

    const resend = async () => {
        setError('');
        setMessage('');
        setFieldErrors({});
        setResending(true);

        try {
            const { data } = await api.post('/auth/email-verification/resend', { email });
            setMessage(data.message || 'Si un compte non vérifié correspond à cette adresse, un nouveau code vient d’être envoyé.');
        } catch (requestError) {
            setError(requestError.response?.data?.message || 'Le code ne peut pas être renvoyé pour le moment.');
        } finally {
            setResending(false);
        }
    };

    const inputClass = 'mt-2 w-full rounded-xl border border-elite-border-light bg-elite-bg px-4 py-3 text-elite-text placeholder:text-elite-disabled focus:border-elite-gold focus:outline-none';

    return (
        <section className="relative isolate overflow-hidden px-6 py-12 sm:py-20">
            <div className="arena-glow pointer-events-none absolute -right-40 top-0 -z-10 h-96 w-96 rounded-full" />
            <div className="mx-auto max-w-xl rounded-2xl border border-elite-border-light bg-elite-card p-6 shadow-2xl shadow-black/20 sm:p-9">
                <p className="text-xs tracking-[0.18em] text-elite-gold">SÉCURISATION DU COMPTE</p>
                <h1 className="mt-4 text-3xl leading-tight text-elite-text">Vérifiez votre adresse e-mail</h1>
                <p className="mt-4 text-sm leading-7 text-elite-muted">Saisissez le code à 6 chiffres envoyé par e-mail. Il reste valable pendant 10 minutes.</p>

                <form onSubmit={verify} className="mt-7 space-y-5">
                    <label className="block text-xs text-elite-muted">
                        Adresse e-mail
                        <input className={inputClass} type="email" autoComplete="email" value={email} onChange={(event) => setEmail(event.target.value)} required aria-invalid={Boolean(fieldErrors.email)} aria-describedby={fieldErrors.email ? 'verification-email-error' : undefined} />
                        <FieldError id="verification-email-error" message={fieldErrors.email} />
                    </label>
                    <label className="block text-xs text-elite-muted">
                        Code de vérification
                        <input className={inputClass} type="text" inputMode="numeric" autoComplete="one-time-code" maxLength={6} pattern="[0-9]{6}" placeholder="000000" value={code} onChange={(event) => setCode(event.target.value.replace(/\D/g, '').slice(0, 6))} required aria-invalid={Boolean(fieldErrors.code)} aria-describedby={fieldErrors.code ? 'verification-code-error' : undefined} />
                        <FieldError id="verification-code-error" message={fieldErrors.code} />
                    </label>
                    {message && <p role="status" className="rounded-lg bg-elite-success-bg px-4 py-3 text-sm text-elite-success">{message}</p>}
                    {error && <p role="alert" className="rounded-lg bg-elite-danger-bg px-4 py-3 text-sm text-elite-danger">{error}</p>}
                    <Button type="submit" className="w-full !py-3.5" disabled={loading}>{loading ? 'Vérification…' : 'Vérifier mon adresse'}</Button>
                </form>

                <button type="button" className="mt-5 w-full text-sm text-elite-gold hover:underline disabled:cursor-wait disabled:opacity-60" onClick={resend} disabled={resending || !email}>
                    {resending ? 'Envoi en cours…' : 'Renvoyer le code'}
                </button>
                <p className="mt-6 text-center text-sm text-elite-muted"><Link to="/inscription" className="text-elite-gold hover:underline">Retour à l’inscription</Link></p>
            </div>
        </section>
    );
}
