import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type Props = {
    action: string;
    label: string;
    question: string;
    description: string;
};

export function ConfirmDeleteDialog({
    action,
    label,
    question,
    description,
}: Props) {
    const [open, setOpen] = useState(false);
    const [running, setRunning] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive" size="sm">
                    {label}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{question}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="ghost">Cancelar</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        disabled={running}
                        onClick={() => {
                            setRunning(true);

                            router.delete(action, {
                                preserveScroll: true,
                                onFinish: () => {
                                    setRunning(false);
                                    setOpen(false);
                                },
                            });
                        }}
                    >
                        {running ? 'Eliminando…' : 'Eliminar definitivamente'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
