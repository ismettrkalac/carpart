<x-layout title="Forgot Password">
    <div class="mx-auto max-w-sm px-6 py-16">
        <h1 class="text-2xl font-semibold">Forgot your password?</h1>
        <p class="mt-1 text-sm text-neutral-500">Enter your email and we'll send you a link to reset it.</p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full rounded-md bg-neutral-900 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                Send reset link
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-neutral-500">
            <a href="{{ route('login.create') }}" class="font-medium text-neutral-900 hover:underline dark:text-white">Back to sign in</a>
        </p>
    </div>
</x-layout>
