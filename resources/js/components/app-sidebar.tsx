import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Briefcase,
    Building2,
    Calculator,
    Coins,
    FileStack,
    FileText,
    Flag,
    FolderGit2,
    Fingerprint,
    LayoutGrid,
    SlidersHorizontal,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as companies } from '@/routes/companies';
import { index as contracts } from '@/routes/contracts';
import { index as liquidations } from '@/routes/liquidations';
import { index as parametricIndex } from '@/routes/parametrics';
import { index as workers } from '@/routes/workers';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Panel',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Liquidaciones',
        href: liquidations().url,
        icon: Calculator,
    },
    {
        title: 'Contratos',
        href: contracts().url,
        icon: FileText,
    },
    {
        title: 'Trabajadores',
        href: workers().url,
        icon: Users,
    },
    {
        title: 'Empresas',
        href: companies().url,
        icon: Building2,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repositorio',
        href: 'https://github.com/johnkennedyls/blindemPruebaT-cnica',
        icon: FolderGit2,
    },
    {
        title: 'Documentación',
        href: 'https://github.com/johnkennedyls/blindemPruebaT-cnica/blob/main/README.md',
        icon: BookOpen,
    },
];

const parametricIcons: Record<string, typeof SlidersHorizontal> = {
    'tipos-de-documento': Fingerprint,
    cargos: Briefcase,
    'tipos-de-contrato': FileStack,
    'motivos-de-retiro': Flag,
    'salarios-minimos': Coins,
};

export function AppSidebar() {
    const { auth, parametrics } = usePage().props;

    const parametricNavItems: NavItem[] = auth.is_admin
        ? parametrics.map((catalog) => ({
              title: catalog.title,
              href: parametricIndex({ resource: catalog.slug }).url,
              icon: parametricIcons[catalog.slug] ?? SlidersHorizontal,
          }))
        : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} label="Gestión" />
                <NavMain
                    items={parametricNavItems}
                    label="Parámetros (administrador)"
                />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
