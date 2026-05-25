<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - LuvShop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-6 py-12">
        <section class="w-full rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold">Sign in</h1>
                <p class="mt-1 text-sm text-slate-500">Use your LuvShop account.</p>
            </div>

            <form method="post" action="{{ route('login.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('email')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="password">Password</label>
                    <input id="password" name="password" type="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('password')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input name="remember" type="checkbox" value="1" class="rounded border-slate-300">
                    Remember me
                </label>
                <button class="w-full rounded-lg bg-rose-700 px-4 py-2 font-semibold text-white hover:bg-rose-800" type="submit">Login</button>
            </form>

            <div class="mt-6 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                <p><strong>Email:</strong> test@example.com</p>
                <p><strong>Password:</strong> password</p>
            </div>
        </section>
    </main>
</body>
</html>
