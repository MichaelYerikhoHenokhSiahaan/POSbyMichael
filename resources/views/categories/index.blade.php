@extends('layouts.app')

@section('content')
    <div class="grid gap-8 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Add category</h2>
            <p class="mt-1 text-sm text-slate-500">Create item groups to organize your inventory.</p>

            <form action="{{ route('categories.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="name" class="text-sm font-medium text-slate-700">Category name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="description" class="text-sm font-medium text-slate-700">Description</label>
                    <textarea id="description" name="description" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('description') }}</textarea>
                </div>
                <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Save category</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Category list</h2>
                    <p class="mt-1 text-sm text-slate-500">Review and update your product groupings.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $categories->count() }} total
                </span>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($categories as $category)
                    <div class="rounded-3xl border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">{{ $category->name }}</h3>
                                <p class="mt-2 text-sm text-slate-500">{{ $category->description ?: 'No description provided.' }}</p>
                                <p class="mt-3 text-xs font-medium uppercase tracking-[0.25em] text-slate-400">{{ $category->products_count }} linked products</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('categories.edit', $category) }}" class="rounded-full bg-amber-100 px-4 py-2 text-sm font-medium text-amber-700">Edit</a>
                                <form action="{{ route('categories.destroy', $category) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        No categories have been created yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
