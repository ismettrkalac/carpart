<x-layout title="Create Account">
    <div class="mx-auto max-w-sm px-6 py-16">
        <h1 class="text-2xl font-semibold">Create an account</h1>
        <p class="mt-1 text-sm text-neutral-500">Track your orders and check out faster next time.</p>

        <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium">Full name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input type="password" id="password" name="password" required
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                @error('password')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
            </div>

            <button type="submit" class="w-full rounded-md bg-neutral-900 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                Create account
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-neutral-500">
            Already have an account?
            <a href="{{ route('login.create') }}" class="font-medium text-neutral-900 hover:underline dark:text-white">Sign in</a>
        </p>
    </div>
</x-layout>
