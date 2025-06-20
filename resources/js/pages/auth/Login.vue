<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Lock } from 'lucide-vue-next';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login Page" />
    
    <!-- Show status messages -->
    <div v-if="status" class="mb-4 text-green-600 bg-green-50 border border-green-200 rounded-md p-3">
        {{ status }}
    </div>
    
    <div class="min-h-screen flex items-center justify-center bg-[#f7f7f7]">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-10 flex flex-col items-center">
            <!-- Logo and Brand -->
            <img src="/images/logo.svg" alt="PurpleBug Logo" class="h-16 w-auto mb-10" />
            
            <form @submit.prevent="submit" class="w-full flex flex-col gap-6 text-black">
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <div class="flex items-center border border-gray-400 rounded-md px-3 py-2 bg-white">
                        <Mail class="w-5 h-5 text-gray-700 mr-2" />
                        <input
                            id="email"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            v-model="form.email"
                            class="flex-1 bg-transparent outline-none text-base"
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <div class="flex items-center border border-gray-400 rounded-md px-3 py-2 bg-white">
                        <Lock class="w-5 h-5 text-gray-700 mr-2" />
                        <input
                            id="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            v-model="form.password"
                            class="flex-1 bg-transparent outline-none text-base"
                        />
                    </div>
                    <InputError :message="form.errors.password" />
                </div>
                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox class="data-[state=checked]:bg-[#8B3F93]" id="remember" v-model="form.remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                </div>
                <div class="flex justify-center w-full">
                    <button type="submit" :disabled="form.processing" class="w-full max-w-3xs mt-4 bg-[#8B3F93] text-white font-semibold rounded-lg py-3 shadow-md hover:bg-[#7a3680] transition disabled:opacity-60">
                        LOGIN
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
