@extends('layouts.app')
@section('title', 'Users')
@section('crumb-parent', 'Settings')
@section('crumb-parent-url', route('settings.edit'))
@section('crumb', 'Users & Access')

@section('content')
<div class="mx-auto max-w-[1000px]">
    <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><x-icon name="users" class="h-3 w-3" /> Access Control</span>
    <h1 class="mt-2 font-display text-[32px] font-bold leading-tight text-ink">Users</h1>
    <p class="mt-1 text-sm text-body">Only an administrator can create accounts. Admins manage products and settings; cashiers can record sales and view products.</p>

    {{-- Add user --}}
    <form method="POST" action="{{ route('users.store') }}" class="card mt-6 space-y-4 p-6">
        @csrf
        <h2 class="font-display text-lg font-bold text-ink">Add Account</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="u_name" class="label">Full Name</label>
                <input id="u_name" name="name" value="{{ old('name') }}" required class="input">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="u_email" class="label">Email</label>
                <input id="u_email" name="email" type="email" value="{{ old('email') }}" required class="input">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="u_role" class="label">Role</label>
                <select id="u_role" name="role" class="input">
                    @foreach ($roles as $value => $label) <option value="{{ $value }}" @selected(old('role', 'cashier') === $value)>{{ $label }}</option> @endforeach
                </select>
                @error('role') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="u_password" class="label">Temporary Password</label>
                <x-password-input id="u_password" name="password" autocomplete="new-password" :icon="false" />
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
                <p class="hint">Min. 10 characters, upper/lowercase, number &amp; symbol. The user can change it in Settings.</p>
            </div>
        </div>
        <div class="flex justify-end"><button class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Create Account</button></div>
    </form>

    {{-- List --}}
    <div class="card mt-6 overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
            <thead class="bg-mint-3"><tr><th class="th">Name</th><th class="th">Email</th><th class="th">Role</th><th class="th text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-mint-2">
                @foreach ($users as $u)
                    <tr>
                        <td class="px-4 py-3.5 font-semibold text-ink">{{ $u->name }} @if ($u->is(auth()->user())) <span class="pill-green ml-1">You</span> @endif</td>
                        <td class="px-4 py-3.5 text-body">{{ $u->email }}</td>
                        <td class="px-4 py-3.5">
                            <form method="POST" action="{{ route('users.update', $u) }}">
                                @csrf @method('PUT')
                                <select name="role" onchange="this.form.submit()" class="input w-32 py-1.5 text-sm" @disabled($u->is(auth()->user())) aria-label="Role of {{ $u->name }}">
                                    @foreach ($roles as $value => $label) <option value="{{ $value }}" @selected($u->role === $value)>{{ $label }}</option> @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            @unless ($u->is(auth()->user()))
                                <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Delete the account of {{ addslashes($u->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger btn-sm"><x-icon name="trash" class="h-3.5 w-3.5" /> Delete</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
