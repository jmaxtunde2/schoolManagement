import Modal from '@/Components/UI/Modal';
import Button from '@/Components/UI/Button';

export default function ConfirmDialog({
    show,
    onClose,
    onConfirm,
    title = 'Confirmer',
    description,
    confirmLabel = 'Confirmer',
    cancelLabel = 'Annuler',
    variant = 'primary',
    loading = false,
    children,
}) {
    return (
        <Modal
            show={show}
            onClose={onClose}
            title={title}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        {cancelLabel}
                    </Button>
                    <Button variant={variant} onClick={onConfirm} loading={loading}>
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            {description && <p className="text-sm text-slate-600">{description}</p>}
            {children}
        </Modal>
    );
}
