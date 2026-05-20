<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { SendHorizontal, Wallet, Eye, EyeOff, CheckCircle2, Loader2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as finance from '@/routes/finance';

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
const user = computed(() => page.props.auth.user);
const isBalanceVisible = ref(true);
const showSuccess = ref(false);

const toggleBalance = () => {
    isBalanceVisible.value = !isBalanceVisible.value;
};

const formatCurrency = (value: number) => {
    if (!isBalanceVisible.value) {
        return 'R$ ••••••';
    }

    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
};

const form = useForm({
    email: '',
    amount: '',
});

const submit = () => {
    form.post(finance.transfer(), {
        onSuccess: () => {
            form.reset();
            showSuccess.value = true;
            setTimeout(() => {
                showSuccess.value = false;
            }, 5000);
        },
    });
};
</script>

<template>
    <Head title="Transfer Money" />

    <div class="flex h-full flex-1 flex-col items-center justify-center gap-6 p-4">
        <Card class="w-full max-w-md transition-all duration-300" :class="{ 'opacity-50 scale-95': showSuccess }">
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
                <Transition name="balance-bounce" mode="out-in">
                    <div :key="user.balance" class="text-2xl font-bold">{{ formatCurrency(user.balance) }}</div>
                </Transition>
            </CardContent>
        </Card>

        <Card class="relative w-full max-w-md overflow-hidden transition-all duration-300">
            <!-- Success Overlay -->
            <div 
                v-if="showSuccess" 
                class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/90 text-center backdrop-blur-sm dark:bg-black/90"
            >
                <CheckCircle2 class="mb-4 h-16 w-16 text-green-500 animate-in zoom-in duration-300" />
                <h3 class="text-xl font-bold">Transfer Requested!</h3>
                <p class="mt-2 text-sm text-muted-foreground">Your transaction is being processed.</p>
                <Button variant="outline" class="mt-6" @click="showSuccess = false">
                    Make another transfer
                </Button>
            </div>

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
                            :disabled="form.processing"
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
                                :disabled="form.processing"
                            />
                        </div>
                        <InputError :message="form.errors.amount" />
                    </div>
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <template v-if="form.processing">
                            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                            Processing...
                        </template>
                        <template v-else>
                            Confirm Transfer
                        </template>
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

<style scoped>
.balance-bounce-enter-active {
    animation: bounce-in 0.5s;
}

@keyframes bounce-in {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); color: #16a34a; }
    100% { transform: scale(1); }
}
</style>
