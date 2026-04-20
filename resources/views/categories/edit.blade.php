@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Edit category</h2>
                <p class="mt-1 text-sm text-slate-500">Update the details for {{ $category->name }}.</p>
            </div>
            <a href="{{ route('categories.index') }}" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">Back</a>
        </div>

        <form action="{{ route('categories.update', $category) }}" method="POST" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="text-sm font-medium text-slate-700">Category name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div>
                <label for="description" class="text-sm font-medium text-slate-700">Description</label>
                <textarea id="description" name="description" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('description', $category->description) }}</textarea>
            </div>
            <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Update category</button>
        </form>
    </div>
@endsection
