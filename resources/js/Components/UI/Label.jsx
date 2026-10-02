export default function Label({ children, required, ...props }) {
    return (
        <label className="mb-1.5 block text-sm font-medium text-slate-700" {...props}>
            {children}
            {required && <span className="ml-0.5 text-red-500">*</span>}
        </label>
    );
}
