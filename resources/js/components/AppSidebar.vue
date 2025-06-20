<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Sidebar, SidebarContent, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { MonitorIcon, ShoppingCartIcon, UsersIcon, LogOutIcon } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useToast } from '@/composables/useToast';
import type { User } from '@/types';

const page = usePage();
const currentPath = computed(() => page.url);
const user = computed(() => page.props.auth.user as User);

// Toast notifications
const { showSuccess, showError } = useToast();

// Logout functionality
const handleLogout = () => {
    if (confirm('Are you sure you want to logout?')) {
        router.post(route('logout'), {}, {
            onSuccess: () => {
                router.flushAll();
                showSuccess('Logout Successful', 'You have been successfully logged out.');
            },
            onError: () => {
                showError('Logout Failed', 'An error occurred while logging out. Please try again.');
            }
        });
    }
};

const adminNavigation = [
    {
        title: 'Products Management',
        href: route('dashboard'),
        icon: MonitorIcon,
    },
    {
        title: 'Orders',
        href: route('admin.dashboard.orders'),
        icon: ShoppingCartIcon,
    },
    {
        title: 'Users Management',
        href: route('admin.dashboard.users'),
        icon: UsersIcon,
    },
];
</script>

<template>
    <Sidebar class="border-r border-gray-200 rounded-2xl">
        <SidebarContent class="flex flex-col h-full">
            <!-- Logo section -->
            <div class="p-4 border-b border-gray-200">
                <AppLogoIcon class="w-32" />
            </div>

            <!-- Navigation section -->
            <SidebarMenu class="flex-1">
                <SidebarMenuItem 
                    v-for="item in adminNavigation" 
                    :key="item.href" 
                    :active="currentPath === item.href"
                    class="px-4 py-3"
                    :class="{ 'bg-purple-100': currentPath === item.href }"
                >
                    <Link :href="item.href" class="flex items-center gap-3 text-gray-700">
                        <component :is="item.icon" class="h-5 w-5" :class="{ 'text-purple-600': currentPath === item.href }" />
                        <span :class="{ 'text-purple-600 font-medium': currentPath === item.href }">
                            {{ item.title }}
                        </span>
                    </Link>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- User section -->
            <div class="mt-auto border-t border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                            <span class="text-purple-600 text-sm font-semibold">
                                {{ user?.name?.charAt(0).toUpperCase() || 'A' }}
                            </span>
                        </div>
                        <div class="text-sm">
                            <p class="font-medium">Hi, {{ user?.name || 'Admin' }}!</p>
                            <p v-if="user?.email" class="text-gray-500 text-xs truncate max-w-24">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                    <button 
                        @click="handleLogout"
                        class="p-2 rounded-md text-gray-500 hover:text-red-600 hover:bg-red-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50"
                        title="Logout"
                    >
                        <LogOutIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </SidebarContent>
    </Sidebar>
</template>

<style scoped>
.sidebar-item {
    transition: all 0.2s ease;
}
</style>
