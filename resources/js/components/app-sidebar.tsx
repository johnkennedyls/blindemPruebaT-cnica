import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    Calculator,
    FileText,
    FolderGit2,
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
import * as parametrics from '@/routes/parametrics';
import { index as workers } from '@/routes/workers';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const baseNavItems: NavItem[] = [
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

export function AppSidebar() {
    const isAdmin = usePage().props.auth.is_admin;

    const mainNavItems: NavItem[] = [
        ...baseNavItems,
        ...(isAdmin
            ? [
                  {
                      title: 'Parámetros',
                      href: parametrics.index({
                          resource: 'tipos-de-documento',
                      }).url,
                      icon: SlidersHorizontal,
                  } satisfies NavItem,
              ]
            : []),
    ];

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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
