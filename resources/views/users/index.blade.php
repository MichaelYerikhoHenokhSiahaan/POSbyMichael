@extends('layouts.app')

@section('content')
    <div class="grid gap-8 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Add user</h2>
            <p class="mt-1 text-sm text-slate-500">Create login accounts with their own usernames and passwords.</p>

            <form action="{{ route('users.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="name" class="text-sm font-medium text-slate-700">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="username" class="text-sm font-medium text-slate-700">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="email" class="text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                    <input id="password" name="password" type="password" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="role" class="text-sm font-medium text-slate-700">Privilege</label>
                    <select id="role" name="role" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', \App\Models\User::ROLE_ADMIN) === $role)>{{ str($role)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Save user</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">User accounts</h2>
                    <p class="mt-1 text-sm text-slate-500">Manage usernames, emails, and account access.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $users->total() }} total
                </span>
            </div>

            <form action="{{ route('users.index') }}" method="GET" class="mt-6 flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <label for="user-search" class="text-sm font-medium text-slate-700">Search users</label>
                    <input id="user-search" name="search" type="text" value="{{ $search }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Search by name, username, or email">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Search</button>
                    @if ($search)
                        <a href="{{ route('users.index') }}" class="rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">Clear</a>
                    @endif
                </div>
            </form>

            <div class="mt-6 space-y-4">
                @forelse ($users as $user)
                    <div class="rounded-3xl border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">{{ $user->name }}</h3>
                                <p class="mt-2 text-sm text-slate-500">{{ $user->email }}</p>
                                <p class="mt-3 text-xs font-medium uppercase tracking-[0.25em] text-slate-400">{{ $user->username }}</p>
                                <p class="mt-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">{{ $user->role }}</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('users.edit', $user) }}" class="rounded-full bg-amber-100 px-4 py-2 text-sm font-medium text-amber-700">Edit</a>
                                <form action="{{ route('users.destroy', $user) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        No users found.
                    </div>
                @endforelse
            </div>

            @if ($users->hasPages())
                <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-slate-500">
                        Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }} users
                    </p>
                    <div>
                        {{ $users->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
