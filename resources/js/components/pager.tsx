import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

export function Pager({
    page,
    label,
}: {
    page: Paginated<unknown>;
    label: string;
}) {
    return (
        <nav className="flex flex-wrap items-center gap-2" aria-label={label}>
            {page.prev_page_url ? (
                <Button asChild variant="outline" size="sm">
                    <Link href={page.prev_page_url}>Anterior</Link>
                </Button>
            ) : null}

            {page.links
                .filter((link) => /^\d+$/.test(link.label))
                .map((link) => (
                    <Link
                        key={link.label}
                        href={link.url ?? '#'}
                        className={cn(
                            'neu-raised h-9 w-9 rounded-xl text-sm',
                            link.active
                                ? 'btn-taxi font-bold'
                                : 'text-muted-foreground',
                        )}
                    >
                        {link.label}
                    </Link>
                ))}

            {page.next_page_url ? (
                <Button asChild variant="outline" size="sm">
                    <Link href={page.next_page_url}>Siguiente</Link>
                </Button>
            ) : null}
        </nav>
    );
}
