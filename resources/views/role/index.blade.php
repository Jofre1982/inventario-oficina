@extends('layouts.app', ['page' => 'Roles', 'pageSlug' => 'roles'])

@section('content')
@include('partials.page-header', ['title' => 'Roles', 'action' => route('roles.create'), 'can' => 'roles.create'])
<div class="card">
    <div class="card-body">
        <p class="text-muted">Un rol agrupa permisos. A cada usuario se le asignan uno o más roles.</p>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Nombre</th><th>Descripción</th><th>Acceso</th><th class="text-right">Usuarios</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td>@can('roles.show')<a href="{{ route('roles.show', $role) }}">{{ $role->name }}</a>@else{{ $role->name }}@endcan</td>
                        <td>{{ $role->description }}</td>
                        <td>
                            @if ($role->special === 'all-access')<span class="badge badge-success">Acceso total</span>
                            @elseif ($role->special === 'no-access')<span class="badge badge-danger">Sin acceso</span>
                            @else {{ $role->permissions_count }} permisos @endif
                        </td>
                        <td class="text-right">{{ $role->users_count }}</td>
                        <td class="text-right">
                            @can('roles.edit')
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-link btn-info btn-sm btn-icon" title="Editar"><i class="tim-icons icon-pencil"></i></a>
                            @endcan
                            @can('roles.destroy')
                                @include('partials.delete', ['action' => route('roles.destroy', $role), 'confirm' => '¿Eliminar el rol? Los usuarios que lo tengan perderán sus permisos.'])
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
