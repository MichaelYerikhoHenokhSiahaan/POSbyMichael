@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Edit user</h2>
                <p class="mt-1 text-sm text-slate-500">Update account details for {{ $user->username }}.</p>
            </div>
            <a href="{{ route('users.index') }}" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">Back</a>
        </div>

        <form action="{{ route('users.update', $user) }}" method="POST" class="mt-6 grid gap-4 md:grid-cols-2">
            @csrf
            @method('PUT')
            <div class="md:col-span-2">
                <label for="name" class="text-sm font-medium text-slate-700">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div>
                <label for="username" class="text-sm font-medium text-slate-700">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div>
                <label for="email" class="text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div class="md:col-span-2">
                <label for="role" class="text-sm font-medium text-slate-700">Privilege</label>
                <select id="role" name="role" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ str($role)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="password" class="text-sm font-medium text-slate-700">New password</label>
                <input id="password" name="password" type="password" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                <p class="mt-2 text-sm text-slate-500">Leave blank to keep the current password.</p>
            </div>
            <button type="submit" class="md:col-span-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Update user</button>
        </form>
    </div>
@endsection
