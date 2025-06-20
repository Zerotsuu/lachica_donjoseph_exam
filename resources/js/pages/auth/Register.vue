<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Lock, User } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registration Page" />
    <div class="min-h-screen flex items-center justify-center bg-[#f7f7f7]">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-lg p-10 flex flex-col items-center">
            <!-- Logo -->
                <img src="/images/logo.svg" alt="PurpleBug Logo" class="h-16 w-auto mb-10" />

            
            <form @submit.prevent="submit" class="w-full flex flex-col gap-6 text-black">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium mb-1">Full Name</label>
                        <div class="flex items-center border border-gray-400 rounded-md px-3 py-2 bg-white">
                            <User class="w-5 h-5 text-gray-700 mr-2" />
                            <input
                                id="name"
                                type="text"
                                required
                                autofocus
                                autocomplete="name"
                                v-model="form.name"
                                class="flex-1 bg-transparent outline-none text-base"
                            />
                        </div>
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium mb-1">Email</label>
                        <div class="flex items-center border border-gray-400 rounded-md px-3 py-2 bg-white">
                            <Mail class="w-5 h-5 text-gray-700 mr-2" />
                            <input
                                id="email"
                                type="email"
                                required
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
                                autocomplete="new-password"
                                v-model="form.password"
                                class="flex-1 bg-transparent outline-none text-base"
                            />
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm Password</label>
                        <div class="flex items-center border border-gray-400 rounded-md px-3 py-2 bg-white">
                            <Lock class="w-5 h-5 text-gray-700 mr-2" />
                            <input
                                id="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                v-model="form.password_confirmation"
                                class="flex-1 bg-transparent outline-none text-base"
                            />
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>
                </div>

                <!-- Centered Register Button -->
                <div class="flex justify-center w-full">
                    <button type="submit" :disabled="form.processing" class="w-full max-w-3xs mt-4 bg-[#8B3F93] text-white font-semibold rounded-lg py-3 shadow-md hover:bg-[#7a3680] transition disabled:opacity-60">
                        REGISTER
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>
