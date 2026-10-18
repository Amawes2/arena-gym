export default function Button({ children, variant = 'primary', className = '', ...props }) {
    const base =
        'inline-flex items-center justify-center rounded-[8px] px-6 py-3 text-sm font-medium transition focus:outline-none focus:ring-1 focus:ring-elite-gold/50 disabled:opacity-50';

    const variants = {
        primary: 'bg-elite-gold text-black hover:brightness-110',
        outline: 'border-[0.5px] border-elite-gold text-elite-gold hover:bg-elite-gold/10',
        ghost: 'text-elite-muted hover:text-elite-text',
        danger: 'bg-elite-danger text-white',
    };

    return (
        <button type="button" className={`${base} ${variants[variant]} ${className}`} {...props}>
            {children}
        </button>
    );
}
