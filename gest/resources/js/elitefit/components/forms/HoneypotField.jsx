export default function HoneypotField({ value, onChange }) {
    return (
        <div
            aria-hidden="true"
            style={{ position: 'absolute', left: '-10000px', top: 'auto', width: 1, height: 1, overflow: 'hidden' }}
        >
            <label>
                Website
                <input
                    type="text"
                    name="website"
                    value={value}
                    onChange={onChange}
                    autoComplete="off"
                    tabIndex={-1}
                />
            </label>
        </div>
    );
}
