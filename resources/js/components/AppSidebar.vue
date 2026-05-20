<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Activity, Building, History, LayoutGrid, SendHorizontal } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import * as finance from '@/routes/finance';
import type { NavItem } from '@/types';

const page = usePage();
const permissions = page.props.auth.user?.permissions ?? [];

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const financeNavItems: NavItem[] = [
    {
        title: 'History',
        href: finance.history(),
        icon: History,
    },
    {
        title: 'Deposit',
        href: finance.showDeposit(),
        icon: Building,
    },
    {
        title: 'Transfer',
        href: finance.showTransfer(),
        icon: SendHorizontal,
    },
];


const footerNavItems: NavItem[] = [
    ...(permissions.includes('view monitoring') ? [
        {
            title: 'Monitoring',
            href: '/telescope',
            icon: Activity,
        },
    ] : []),
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain :items="financeNavItems" title="Finance" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
