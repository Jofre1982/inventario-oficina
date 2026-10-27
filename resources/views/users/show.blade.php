@extends('layouts.app', ['page' => 'Usuarios', 'pageSlug' => 'users'])

@section('content')
@include('partials.page-header', ['title' => $user->name])
<div class="card">
    <div class="card-body">
        <p><span class="text-muted">Correo</span><br>{{ $user->email }}</p>
        <p><span class="text-muted">Roles</span><br>
            @forelse ($user->roles as $role)<span class="badge badge-info">{{ $role->name }}</span> @empty — @endforelse
        </p>
        <p><span class="text-muted">Salidas registradas</span><br>{{ $deliveries }}</p>
        <p><span class="text-muted">Entradas registradas</span><br>{{ $entries }}</p>
    </div>
    <div class="card-footer">
        @can('users.edit')<a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm">Editar</a>@endcan
        <a href="{{ route('users.index') }}" class="btn btn-default btn-sm">Regresar</a>
    </div>
</div>
@endsection
