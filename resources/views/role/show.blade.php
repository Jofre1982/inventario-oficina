@extends('layouts.app', ['page' => 'Roles', 'pageSlug' => 'roles'])

@section('content')
@include('partials.page-header', ['title' => 'Rol: ' . $role->name])
<div class="card">
    <div class="card-body">
        <p>{{ $role->description }}</p>
        @if ($role->special === 'all-access')
            <p class="text-success">Este rol tiene <b>acceso total</b> a todo el sistema.</p>
        @elseif ($role->special === 'no-access')
            <p class="text-danger">Este rol <b>bloquea</b> el acceso al sistema.</p>
        @else
            <ul>
                @forelse ($role->permissions as $permission)
                    <li>{{ $permission->name }} <small class="text-muted">({{ $permission->slug }})</small></li>
                @empty
                    <li class="text-muted">Sin permisos.</li>
                @endforelse
            </ul>
        @endif
    </div>
    <div class="card-footer">
        @can('roles.edit')<a href="{{ route('roles.edit', $role) }}" class="btn btn-primary btn-sm">Editar</a>@endcan
        <a href="{{ route('roles.index') }}" class="btn btn-default btn-sm">Regresar</a>
    </div>
</div>
@endsection
