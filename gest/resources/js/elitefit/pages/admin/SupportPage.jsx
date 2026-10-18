import { useEffect, useRef, useState } from 'react';
import { IconMessageCircle, IconRefresh, IconSend } from '@tabler/icons-react';
import api from '../../api/client';

const questions = [
    { question: 'Comment ajouter un membre ?', answer: 'Ouvrez Membres, choisissez Ajouter un membre, puis saisissez ses coordonnées.' },
    { question: 'Comment créer ou renouveler un abonnement ?', answer: 'Dans Abonnements, choisissez le membre et son forfait. Les abonnements de membres restent distincts de l’abonnement Arena.' },
    { question: 'Comment enregistrer une présence ?', answer: 'Ouvrez Présences et recherchez le membre. Le système vérifie son abonnement et enregistre le passage.' },
    { question: 'Comment renouveler mon accès Arena ?', answer: 'Ouvrez Mon abonnement et envoyez une demande. Le paiement est manuel en FCFA; le Super Admin vous transmet les instructions.' },
    { question: 'Comment obtenir de l’aide ?', answer: 'Écrivez au Super Admin dans la conversation ci-dessous. Seuls les responsables de votre salle et le Super Admin voient ce fil.' },
];

export default function SupportPage() {
    const [messages, setMessages] = useState([]);
    const [body, setBody] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);
    const bottomRef = useRef(null);

    const loadMessages = () => api.get('/support/messages').then(({ data }) => setMessages(data.data ?? []));

    useEffect(() => {
        let ignore = false;
        const load = () => api.get('/support/messages').then(({ data }) => {
            if (!ignore) {
                setMessages(data.data ?? []);
                setError('');
            }
        }).catch((requestError) => {
            if (!ignore) {
                setError(requestError.response?.data?.message || 'Impossible de charger la conversation.');
            }
        }).finally(() => {
            if (!ignore) {
                setLoading(false);
            }
        });
        load();
        const interval = window.setInterval(load, 30000);
        return () => {
            ignore = true;
            window.clearInterval(interval);
        };
    }, []);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, [messages]);

    const send = async (event) => {
        event.preventDefault();
        if (!body.trim() || sending) {
            return;
        }
        setSending(true);
        setError('');
        try {
            await api.post('/support/messages', { body: body.trim() });
            setBody('');
            await loadMessages();
        } catch (requestError) {
            setError(requestError.response?.data?.message || 'Le message n’a pas pu être envoyé.');
        } finally {
            setSending(false);
        }
    };

    return (
        <section className="space-y-6 p-4 sm:p-6 lg:p-8">
            <header>
                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-elite-gold">Assistance ARENA</p>
                <h1 className="mt-2 text-2xl font-semibold text-elite-text">FAQ et échanges</h1>
                <p className="mt-2 text-sm text-elite-muted">Consultez les réponses rapides ou écrivez au Super Admin depuis le fil privé de votre salle.</p>
            </header>

            <div className="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                <div className="space-y-3">
                    <h2 className="text-lg font-semibold text-elite-text">Questions fréquentes</h2>
                    {questions.map(({ question, answer }) => (
                        <details key={question} className="rounded-xl border border-elite-border-light bg-elite-card px-4 py-3">
                            <summary className="cursor-pointer text-sm font-medium text-elite-text">{question}</summary>
                            <p className="mt-3 text-sm leading-6 text-elite-muted">{answer}</p>
                        </details>
                    ))}
                </div>

                <section className="flex min-h-[520px] flex-col rounded-2xl border border-elite-border-light bg-elite-card" aria-label="Conversation avec le Super Admin">
                    <header className="flex items-center justify-between border-b border-elite-border-light px-5 py-4">
                        <div className="flex items-center gap-3">
                            <span className="grid h-10 w-10 place-items-center rounded-xl bg-elite-gold/15 text-elite-gold"><IconMessageCircle size={21} /></span>
                            <div><h2 className="font-semibold text-elite-text">Super Admin</h2><p className="text-xs text-elite-muted">Conversation privée avec votre salle</p></div>
                        </div>
                        <button type="button" onClick={() => loadMessages().catch(() => {})} aria-label="Actualiser" className="rounded-lg p-2 text-elite-muted hover:bg-white/10"><IconRefresh size={18} /></button>
                    </header>
                    <div className="flex-1 space-y-3 overflow-y-auto p-4 sm:p-5">
                        {loading ? <p className="text-sm text-elite-muted">Chargement des messages…</p> : messages.length === 0 ? <p className="rounded-xl bg-white/5 p-4 text-sm text-elite-muted">Aucun message pour le moment. Posez votre question ci-dessous.</p> : messages.map((message) => {
                            const isAdminReply = message.sender_role === 'super_admin';
                            return (
                                <article key={message.id} className={`max-w-[90%] rounded-xl px-4 py-3 ${isAdminReply ? 'bg-white/8 text-elite-text' : 'ml-auto bg-elite-gold/15 text-elite-text'}`}>
                                    <p className="mb-1 text-[11px] font-semibold text-elite-gold">{isAdminReply ? 'Super Admin' : (message.sender_name || 'Votre salle')}</p>
                                    <p className="whitespace-pre-wrap text-sm leading-6">{message.body}</p>
                                    <time className="mt-2 block text-right text-[10px] text-elite-muted">{message.created_at ? new Date(message.created_at).toLocaleString('fr-FR') : ''}</time>
                                </article>
                            );
                        })}
                        <div ref={bottomRef} />
                    </div>
                    {error && <p role="alert" className="mx-4 rounded-lg bg-red-500/10 px-3 py-2 text-sm text-red-100">{error}</p>}
                    <form onSubmit={send} className="flex items-end gap-2 border-t border-elite-border-light p-4">
                        <label className="sr-only" htmlFor="support-message">Votre message</label>
                        <textarea id="support-message" value={body} onChange={(event) => setBody(event.target.value)} maxLength={4000} rows={2} placeholder="Écrivez votre question…" className="min-h-12 flex-1 resize-y rounded-xl border border-elite-border-light bg-elite-bg px-3 py-3 text-sm text-elite-text outline-none focus:border-elite-gold" />
                        <button type="submit" disabled={sending || !body.trim()} aria-label="Envoyer le message" className="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-elite-gold text-slate-950 disabled:opacity-50"><IconSend size={19} /></button>
                    </form>
                </section>
            </div>
        </section>
    );
}
