@extends('layouts.app', ['page' => 'Usuarios', 'pageSlug' => 'users'])

@section('content')
@include('partials.page-header', ['title' => 'Usuarios', 'action' => route('users.create'), 'can' => 'users.create'])
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Roles</th><th>Creado</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>@can('users.show')<a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>@else{{ $user->name }}@endcan</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @forelse ($user->roles as $role)
                                <span class="badge badge-info">{{ $role->name }}</span>
                            @empty
                                <span class="text-warning">Sin rol (no puede usar el sistema)</span>
                            @endforelse
                        </td>
                        <td>{{ optional($user->created_at)->format('d/m/Y') }}</td>
                        <td class="text-right">
                            @can('users.edit')
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-link btn-info btn-sm btn-icon" title="Editar"><i class="tim-icons icon-pencil"></i></a>
                            @endcan
                            @can('users.destroy')
                                @if ($user->id !== auth()->id())
                                    @include('partials.delete', ['action' => route('users.destroy', $user)])
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</div>
@endsection
