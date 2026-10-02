import { useEffect, useRef, useState } from 'react';
import { cn } from '@/Utils/cn';

export default function Dropdown({ trigger, children, align = 'right' }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        function onClick(e) {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        }
        document.addEventListener('mousedown', onClick);
        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    return (
        <div className="relative" ref={ref}>
            <div onClick={() => setOpen((o) => !o)}>{trigger}</div>
            {open && (
                <div
                    className={cn(
                        'absolute z-40 mt-2 min-w-[180px] rounded-lg border border-slate-200 bg-white py-1 shadow-lg',
                        align === 'right' ? 'right-0' : 'left-0',
                    )}
                    onClick={() => setOpen(false)}
                >
                    {children}
                </div>
            )}
        </div>
    );
}

Dropdown.Item = function DropdownItem({ children, className, ...props }) {
    return (
        <button
            type="button"
            className={cn('flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50', className)}
            {...props}
        >
            {children}
        </button>
    );
};
