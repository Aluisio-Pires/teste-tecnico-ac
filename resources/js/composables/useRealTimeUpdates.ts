import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, computed, watch } from 'vue';

export function useRealTimeUpdates() {
    const page = usePage();
    const userId = computed(() => page.props.auth.user?.id);

    const setupListener = (id: number) => {
        console.log(`[Echo] Setting up private channel for user: ${id}`);
        
        window.Echo.private(`App.Models.User.${id}`)
            .listen('.financial-operation.completed', (e: any) => {
                console.log('[Echo] Financial operation event received:', e);
                
                // Smooth reload of essential data
                router.reload({
                    only: ['auth', 'recentLedgers', 'ledgers'],
                    preserveScroll: true,
                    onSuccess: () => console.log('[Inertia] Data reloaded successfully'),
                });
            });
    };

    const stopListener = (id: number) => {
        console.log(`[Echo] Leaving private channel for user: ${id}`);
        window.Echo.leave(`App.Models.User.${id}`);
    };

    onMounted(() => {
        if (userId.value) {
            setupListener(userId.value);
        }
    });

    onUnmounted(() => {
        if (userId.value) {
            stopListener(userId.value);
        }
    });

    // Handle user changes (e.g. login/logout or profile updates that might change ID)
    watch(userId, (newId, oldId) => {
        if (oldId) {
stopListener(oldId);
}

        if (newId) {
setupListener(newId);
}
    });
}
