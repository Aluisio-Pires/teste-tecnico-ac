<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, RotateCcw } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import * as finance from '@/routes/finance';

interface Ledger {
    id: number;
    amount: number;
    balance_after: number;
    created_at: string;
    subledger: {
        id: number;
        type: string;
        metadata: any;
        was_reversed: boolean;
    };
}

defineProps<{
    ledgers: {
        data: Ledger[];
        links: any[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'History',
                href: finance.history(),
            },
        ],
    },
});

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('pt-BR');
};

const reverseTransaction = (subledgerId: number) => {
    if (confirm('Are you sure you want to reverse this transaction?')) {
        router.post(finance.reverse({ subledger: subledgerId }));
    }
};

const canReverse = (ledger: Ledger) => {
    if (ledger.subledger.type === 'reversal') {
return false;
}

    if (ledger.subledger.was_reversed) {
return false;
}
    
    const user = usePage().props.auth.user;

    if (ledger.subledger.type === 'deposit') {
        return Number(ledger.subledger.metadata.user_id) === user.id;
    }

    if (ledger.subledger.type === 'transfer') {
        return Number(ledger.subledger.metadata.from_user_id) === user.id;
    }

    return false;
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
    <Head title="Transaction History" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Card>
            <CardHeader>
                <CardTitle>Transaction History</CardTitle>
                <CardDescription>View all your financial movements and reversals.</CardDescription>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Operation</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Balance After</TableHead>
                            <TableHead>Details</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="ledger in ledgers.data" :key="ledger.id">
                            <TableCell class="whitespace-nowrap">{{ formatDate(ledger.created_at) }}</TableCell>
                            <TableCell>
                                <Badge :class="getOperationBadge(ledger.subledger.type)">
                                    {{ ledger.subledger.type.toUpperCase() }}
                                </Badge>
                            </TableCell>
                            <TableCell :class="ledger.amount > 0 ? 'text-green-600' : 'text-red-600'">
                                <span class="flex items-center gap-1">
                                    <ArrowDownLeft v-if="ledger.amount > 0" class="h-3 w-3" />
                                    <ArrowUpRight v-else class="h-3 w-3" />
                                    {{ formatCurrency(ledger.amount) }}
                                </span>
                            </TableCell>
                            <TableCell>{{ formatCurrency(ledger.balance_after) }}</TableCell>
                            <TableCell class="text-xs text-muted-foreground">
                                <template v-if="ledger.subledger.type === 'transfer'">
                                    <span v-if="ledger.amount < 0">To: {{ ledger.subledger.metadata.to_user_email || ledger.subledger.metadata.to_user_id }}</span>
                                    <span v-else>From: {{ ledger.subledger.metadata.from_user_email || ledger.subledger.metadata.from_user_id }}</span>
                                </template>
                                <template v-else-if="ledger.subledger.type === 'reversal'">
                                    Ref: #{{ ledger.subledger.metadata.original_subledger_id }}
                                </template>
                                <template v-else>
                                    Self Deposit
                                </template>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button
                                    v-if="canReverse(ledger)"
                                    variant="ghost"
                                    size="sm"
                                    @click="reverseTransaction(ledger.subledger.id)"
                                    title="Reverse Transaction"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="ledgers.data.length === 0">
                            <TableCell colspan="6" class="h-24 text-center">
                                No transactions found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Simple Pagination (just for show, actual logic would need more) -->
                <div class="mt-4 flex items-center justify-end space-x-2">
                    <Button
                        v-for="link in ledgers.links"
                        :key="link.label"
                        variant="outline"
                        size="sm"
                        :disabled="!link.url || link.active"
                        @click="link.url && router.get(link.url)"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
