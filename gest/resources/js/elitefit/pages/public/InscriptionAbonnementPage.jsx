import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../../api/client';
import HoneypotField from '../../components/forms/HoneypotField';
import TurnstileWidget from '../../components/forms/TurnstileWidget';
import FieldError from '../../components/forms/FieldError';
import Button from '../../components/ui/Button';

const initialForm = { prenom: '', nom: '', telephone: '', email: '', password: '', password_confirmation: '', website: '', turnstile_token: '' };

export default function InscriptionAbonnementPage() {
    const navigate = useNavigate();
    const [form, setForm] = useState(initialForm);
    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});
    const [loading, setLoading] = useState(false);
    const [widgetKey, setWidgetKey] = useState(0);

    const update = (field) => (event) => {
        setForm((current) => ({ ...current, [field]: event.target.value }));
        setFieldErrors((current) => ({ ...current, [field]: undefined }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setError('');
        setFieldErrors({});
        setLoading(true);

        try {
            const { data } = await api.post('/auth/register', form);
            navigate('/verification-email', { replace: true, state: { email: data.email } });
        } catch (requestError) {
            if (requestError.response?.data?.verification_pending) {
                navigate('/verification-email', {
                    replace: true,
                    state: {
                        email: requestError.response.data.email || form.email,
                        deliveryIssue: requestError.response.data.message,
                    },
                });
                return;
            }

            const validationErrors = requestError.response?.data?.errors;
            if (validationErrors) {
                setFieldErrors(Object.fromEntries(Object.entries(validationErrors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages])));
                const unassignedError = Object.entries(validationErrors).find(([field]) => !['prenom', 'nom', 'telephone', 'email', 'password', 'password_confirmation'].includes(field))?.[1];
                if (unassignedError) {
                    setError(Array.isArray(unassignedError) ? unassignedError[0] : unassignedError);
                }
            } else {
                setError(requestError.response?.data?.message || 'Impossible de créer le compte pour le moment. Réessayez.');
            }
        } finally {
            setLoading(false);
            setForm((current) => ({ ...current, turnstile_token: '' }));
            setWidgetKey((current) => current + 1);
        }
    };

    const inputClass = 'mt-2 w-full rounded-xl border border-elite-border-light bg-elite-bg px-4 py-3 text-elite-text placeholder:text-elite-disabled focus:border-elite-gold focus:outline-none';

    return (
        <section className="relative isolate overflow-hidden px-6 py-12 sm:py-20">
            <div className="arena-glow pointer-events-none absolute -right-40 top-0 -z-10 h-96 w-96 rounded-full" />
            <div className="arena-form-layout mx-auto max-w-5xl gap-12 lg:items-start">
                <div className="pt-2">
                    <p className="text-xs tracking-[0.18em] text-elite-gold">DEVENEZ MEMBRE</p>
                    <h1 className="mt-4 text-4xl leading-tight text-elite-text sm:text-5xl">Votre prochain chapitre commence ici.</h1>
                    <p className="mt-5 text-sm leading-7 text-elite-muted">Créez votre espace membre pour commencer votre parcours chez ARENA GYM. Vos informations restent protégées et servent à gérer votre compte.</p>
                    <p className="mt-8 text-sm text-elite-muted">Vous avez déjà un compte ? <Link to="/connexion" className="text-elite-gold hover:underline">Connectez-vous</Link></p>
                </div>
                <form onSubmit={submit} className="rounded-2xl border border-elite-border-light bg-elite-card p-6 shadow-2xl shadow-black/20 sm:p-9">
                    <HoneypotField value={form.website} onChange={update('website')} />
                    <h2 className="text-xl text-elite-text">Créer mon compte</h2>
                    <p className="mt-2 text-sm text-elite-muted">Tous les champs sont requis.</p>
                    <div className="arena-form-fields-grid mt-7 gap-5">
                        <label className="text-xs text-elite-muted">Prénom<input className={inputClass} autoFocus autoComplete="given-name" value={form.prenom} onChange={update('prenom')} maxLength={50} required aria-invalid={Boolean(fieldErrors.prenom)} aria-describedby={fieldErrors.prenom ? 'member-first-name-error' : undefined} /><FieldError id="member-first-name-error" message={fieldErrors.prenom} /></label>
                        <label className="text-xs text-elite-muted">Nom<input className={inputClass} autoComplete="family-name" value={form.nom} onChange={update('nom')} maxLength={50} required aria-invalid={Boolean(fieldErrors.nom)} aria-describedby={fieldErrors.nom ? 'member-last-name-error' : undefined} /><FieldError id="member-last-name-error" message={fieldErrors.nom} /></label>
                        <label className="text-xs text-elite-muted sm:col-span-2">Téléphone<input className={inputClass} type="tel" autoComplete="tel" placeholder="07 XX XX XX XX" value={form.telephone} onChange={update('telephone')} required aria-invalid={Boolean(fieldErrors.telephone)} aria-describedby={fieldErrors.telephone ? 'member-phone-error' : undefined} /><FieldError id="member-phone-error" message={fieldErrors.telephone} /></label>
                        <label className="text-xs text-elite-muted sm:col-span-2">Adresse e-mail<input className={inputClass} type="email" autoComplete="email" placeholder="vous@exemple.com" value={form.email} onChange={update('email')} required aria-invalid={Boolean(fieldErrors.email)} aria-describedby={fieldErrors.email ? 'member-email-error' : undefined} /><FieldError id="member-email-error" message={fieldErrors.email} /></label>
                        <label className="text-xs text-elite-muted">Mot de passe<input className={inputClass} type="password" autoComplete="new-password" minLength={12} value={form.password} onChange={update('password')} required aria-invalid={Boolean(fieldErrors.password)} aria-describedby={fieldErrors.password ? 'member-password-hint member-password-error' : 'member-password-hint'} /><span id="member-password-hint" className="mt-1 block">12 caractères minimum, avec une majuscule, une minuscule, un chiffre et un symbole.</span><FieldError id="member-password-error" message={fieldErrors.password} /></label>
                        <label className="text-xs text-elite-muted">Confirmer le mot de passe<input className={inputClass} type="password" autoComplete="new-password" minLength={12} value={form.password_confirmation} onChange={update('password_confirmation')} required aria-invalid={Boolean(fieldErrors.password_confirmation)} aria-describedby={fieldErrors.password_confirmation ? 'member-password-confirmation-error' : undefined} /><FieldError id="member-password-confirmation-error" message={fieldErrors.password_confirmation} /></label>
                    </div>
                    <label className="mt-6 flex items-start gap-3 text-xs leading-5 text-elite-muted"><input className="mt-1 accent-elite-gold" type="checkbox" required /><span>J’accepte que mes informations soient utilisées pour créer et gérer mon compte. Consultez la <Link to="/politique-confidentialite" className="text-elite-gold hover:underline">politique de confidentialité</Link>.</span></label>
                    <TurnstileWidget key={widgetKey} action="member_registration" onToken={(token) => setForm((current) => ({ ...current, turnstile_token: token }))} />
                    {error && <p role="alert" className="mt-5 rounded-lg bg-elite-danger-bg px-4 py-3 text-sm text-elite-danger">{error}</p>}
                    <Button type="submit" className="mt-7 w-full !py-3.5" disabled={loading}>{loading ? 'Création du compte…' : 'Créer mon compte'}</Button>
                    <p className="mt-4 text-center text-xs text-elite-muted">En continuant, vous acceptez également notre <Link to="/cookies" className="text-elite-text hover:text-elite-gold">politique relative aux cookies</Link>.</p>
                </form>
            </div>
        </section>
    );
}
