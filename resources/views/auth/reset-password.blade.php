<x-layout title="Reset Password">
    <div class="mx-auto max-w-sm px-6 py-16">
        <h1 class="text-2xl font-semibold">Reset your password</h1>
        <p class="mt-1 text-sm text-neutral-500">Choose a new password for your account.</p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">New password</label>
                <input type="password" id="password" name="password" required
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                @error('password')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
            </div>

            <button type="submit" class="w-full rounded-md bg-neutral-900 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                Reset password
            </button>
        </form>
    </div>
</x-layout>
