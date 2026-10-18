export default function ArenaLogo({ size = 40, className = '' }) {
    return (
        <img
            src="/images/arena-icon-512.png"
            alt=""
            aria-hidden="true"
            width={size}
            height={size}
            decoding="async"
            fetchPriority="high"
            className={`arena-brand-icon shrink-0 rounded-lg object-cover ${className}`}
        />
    );
}
