import { useState } from 'react';
import { IconArrowRight, IconCheck, IconX } from '@tabler/icons-react';
import api from '../../api/client';

const steps = [
    {
        title: 'Bienvenue dans ARENA GYM',
        body: 'Cet espace rassemble les outils de gestion de votre salle. Ce guide rapide vous accompagne dans vos premiers pas.',
    },
    {
        title: 'Tableau de bord',
        body: 'Retrouvez les indicateurs clés, les dernières activités et les alertes de votre salle dès l’accueil.',
    },
    {
        title: 'Membres et abonnements',
        body: 'Ajoutez vos membres, choisissez leurs forfaits et suivez les échéances depuis les rubriques Membres et Abonnements.',
    },
    {
        title: 'Présences, boutique et rapports',
        body: 'Enregistrez les présences, gérez les produits et les commandes, puis consultez les rapports de votre salle.',
    },
    {
        title: 'Aide et abonnement Arena',
        body: 'La rubrique Aide & échanges vous relie au Super Admin. Mon abonnement affiche le statut de la salle et le parcours de renouvellement.',
    },
];

export default function FirstLoginTour({ onComplete }) {
    const [stepIndex, setStepIndex] = useState(0);
    const [isSaving, setIsSaving] = useState(false);
    const currentStep = steps[stepIndex];

    const finish = async () => {
        if (isSaving) {
            return;
        }

        setIsSaving(true);
        try {
            await api.post('/onboarding/complete');
        } catch {} finally {
            onComplete();
            setIsSaving(false);
        }
    };

    return (
        <div className="fixed inset-0 z-[100] grid place-items-center bg-black/75 p-4" role="presentation">
            <section className="w-full max-w-xl rounded-2xl border border-elite-border-light bg-elite-card p-6 shadow-2xl sm:p-8" role="dialog" aria-modal="true" aria-labelledby="arena-tour-title">
                <div className="flex items-center justify-between gap-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.16em] text-elite-gold">Guide de démarrage · {stepIndex + 1}/{steps.length}</p>
                    <button type="button" onClick={finish} className="rounded-lg p-2 text-elite-muted hover:bg-white/10" aria-label="Fermer le guide">
                        <IconX size={18} />
                    </button>
                </div>
                <h2 id="arena-tour-title" className="mt-5 text-2xl font-semibold text-elite-text">{currentStep.title}</h2>
                <p className="mt-3 text-sm leading-7 text-elite-muted">{currentStep.body}</p>
                <div className="mt-8 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" onClick={finish} disabled={isSaving} className="text-sm text-elite-muted underline underline-offset-4 hover:text-white">
                        Passer le guide
                    </button>
                    <div className="flex items-center gap-2">
                        {stepIndex > 0 && (
                            <button type="button" onClick={() => setStepIndex((index) => index - 1)} className="rounded-lg border border-elite-border-light px-4 py-2 text-sm text-elite-text">
                                Retour
                            </button>
                        )}
                        {stepIndex < steps.length - 1 ? (
                            <button type="button" onClick={() => setStepIndex((index) => index + 1)} className="inline-flex items-center gap-2 rounded-lg bg-elite-gold px-4 py-2 text-sm font-semibold text-slate-950">
                                Suivant <IconArrowRight size={17} />
                            </button>
                        ) : (
                            <button type="button" onClick={finish} disabled={isSaving} className="inline-flex items-center gap-2 rounded-lg bg-elite-gold px-4 py-2 text-sm font-semibold text-slate-950">
                                <IconCheck size={17} /> Terminer
                            </button>
                        )}
                    </div>
                </div>
            </section>
        </div>
    );
}
