export default function FieldError({ id, message }) {
    if (!message) {
        return null;
    }

    return <span id={id} role="alert" className="mt-1 block text-xs text-elite-danger">{message}</span>;
}
