import type { Auth } from '@/types/auth';
import type { ParametricNavItem } from '@/types/navigation';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            parametrics: ParametricNavItem[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
