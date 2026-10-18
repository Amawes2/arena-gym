import { Component } from 'react';

export default class ErrorBoundary extends Component {
    state = { error: null };

    static getDerivedStateFromError(error) {
        return { error };
    }

    render() {
        if (this.state.error) {
            return (
                <div className="flex min-h-screen items-center justify-center bg-elite-bg p-8">
                    <div className="max-w-lg rounded-[12px] border-[0.5px] border-elite-danger bg-elite-danger-bg p-8">
                        <h1 className="text-lg text-elite-danger">Une erreur est survenue</h1>
                        <p className="mt-4 text-sm text-elite-text">{this.state.error.message}</p>
                        <button
                            type="button"
                            className="mt-6 rounded-[8px] bg-elite-gold px-4 py-2 text-sm text-black"
                            onClick={() => window.location.reload()}
                        >
                            Recharger la page
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
