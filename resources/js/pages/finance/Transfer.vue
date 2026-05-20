<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import * as finance from '@/routes/finance';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { SendHorizontal, Wallet, Eye, EyeOff } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Transfer',
                href: finance.showTransfer(),
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

const form = useForm({
    email: '',
    amount: '',
});

const submit = () => {
    form.post(finance.transfer(), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Transfer Money" />

    <div class="flex h-full flex-1 flex-col items-center justify-center gap-6 p-4">
        <Card class="w-full max-w-md">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Current Balance</CardTitle>
                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" @click="toggleBalance" class="h-8 w-8">
                        <Eye v-if="!isBalanceVisible" class="h-4 w-4" />
                        <EyeOff v-else class="h-4 w-4" />
                    </Button>
                    <Wallet class="h-4 w-4 text-muted-foreground" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatCurrency(user.balance) }}</div>
            </CardContent>
        </Card>

        <Card class="w-full max-w-md">
            <CardHeader>
                <CardTitle>Transfer Money</CardTitle>
                <CardDescription>Send funds to anyone in the system.</CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid w-full items-center gap-1.5">
                        <Label for="email">Recipient Email</Label>
                        <Input
                            id="email"
                            type="email"
                            placeholder="user@example.com"
                            v-model="form.email"
                            autofocus
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid w-full items-center gap-1.5">
                        <Label for="amount">Amount (R$)</Label>
                        <div class="relative">
                            <SendHorizontal class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="amount"
                                type="number"
                                step="0.01"
                                placeholder="0,00"
                                class="pl-9"
                                v-model="form.amount"
                            />
                        </div>
                        <InputError :message="form.errors.amount" />
                    </div>
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        Confirm Transfer
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
