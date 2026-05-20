<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { dashboard, finance as financeRoutes } from '@/routes';
import * as finance from '@/routes/finance';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Wallet, Eye, EyeOff, Building, SendHorizontal, ArrowRight } from 'lucide-vue-next';

interface Ledger {
    id: number;
    amount: number;
    balance_after: number;
    created_at: string;
    subledger: {
        id: number;
        type: string;
    };
}

defineProps<{
    recentLedgers: Ledger[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const user = page.props.auth.user;
const isBalanceVisible = ref(true);

const toggleBalance = () => {
    isBalanceVisible.value = !isBalanceVisible.value;
};

const formatCurrency = (value: number) => {
    if (!isBalanceVisible.value) return 'R$ ••••••';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('pt-BR');
};

const getOperationBadge = (type: string) => {
    switch (type) {
        case 'deposit':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100';
        case 'transfer':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100';
        case 'reversal':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Balance and Actions Section -->
        <div class="grid gap-4 md:grid-cols-3">
            <Card class="md:col-span-2">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Available Balance</CardTitle>
                    <div class="flex items-center gap-2">
                        <Button variant="ghost" size="icon" @click="toggleBalance" class="h-8 w-8">
                            <Eye v-if="!isBalanceVisible" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </Button>
                        <Wallet class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{{ formatCurrency(user.balance) }}</div>
                    <p class="text-xs text-muted-foreground">Updated in real-time</p>
                    
                    <div class="mt-6 flex gap-3">
                        <Button as-child variant="default" class="flex-1">
                            <Link :href="finance.showDeposit()">
                                <Building class="mr-2 h-4 w-4" />
                                Deposit
                            </Link>
                        </Button>
                        <Button as-child variant="secondary" class="flex-1">
                            <Link :href="finance.showTransfer()">
                                <SendHorizontal class="mr-2 h-4 w-4" />
                                Transfer
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Quick Navigation</CardTitle>
                    <CardDescription>Access your financial tools.</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-2">
                    <Button as-child variant="outline" class="justify-start">
                        <Link :href="finance.history()">
                            <ArrowRight class="mr-2 h-4 w-4" />
                            Full History
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Transactions Section -->
        <Card class="flex-1">
            <CardHeader class="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>Recent Transactions</CardTitle>
                    <CardDescription>Your latest financial activities.</CardDescription>
                </div>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="finance.history()">View All</Link>
                </Button>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Balance After</TableHead>
                            <TableHead>Details</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="ledger in recentLedgers" :key="ledger.id">
                            <TableCell>{{ formatDate(ledger.created_at) }}</TableCell>
                            <TableCell>
                                <Badge :class="getOperationBadge(ledger.subledger.type)">
                                    {{ ledger.subledger.type.toUpperCase() }}
                                </Badge>
                            </TableCell>
                            <TableCell :class="ledger.amount > 0 ? 'text-green-600' : 'text-red-600'">
                                {{ ledger.amount > 0 ? '+' : '' }}{{ formatCurrency(ledger.amount) }}
                            </TableCell>
                            <TableCell>{{ formatCurrency(ledger.balance_after) }}</TableCell>
                            <TableCell class="text-xs text-muted-foreground">
                                <template v-if="ledger.subledger.type === 'transfer'">
                                    <span v-if="ledger.amount < 0">To: {{ ledger.subledger.metadata.to_user_email || ledger.subledger.metadata.to_user_id }}</span>
                                    <span v-else>From: {{ ledger.subledger.metadata.from_user_email || ledger.subledger.metadata.from_user_id }}</span>
                                </template>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="recentLedgers.length === 0">
                            <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                No recent transactions.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
