@php
    $groupNames = [
        'items' => 'Elementos', 'categories' => 'Categorías', 'dependencies' => 'Dependencias',
        'responsibles' => 'Responsables', 'providers' => 'Proveedores', 'users' => 'Usuarios', 'roles' => 'Roles',
        'change' => 'Anular / activar', 'entries' => 'Entradas', 'deliveries' => 'Salidas y préstamos',
        'returns' => 'Devoluciones', 'loans' => 'Préstamos', 'reports' => 'Reportes', 'organization' => 'Entidad',
    ];
    $selected = old('permissions', $role->exists ? $role->permissions->pluck('id')->all() : []);
@endphp
<div class="row">
    <div class="col-md-6 form-group">
        <label for="name">Nombre *</label>
        <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $role->name) }}" required autofocus>
    </div>
    <div class="col-md-6 form-group">
        <label for="special">Acceso especial</label>
        <select class="form-control" name="special" id="special">
            <option value="">Ninguno (usar los permisos marcados)</option>
            <option value="all-access" @selected(old('special', $role->special) === 'all-access')>Acceso total (administrador)</option>
            <option value="no-access" @selected(old('special', $role->special) === 'no-access')>Sin acceso (bloqueado)</option>
        </select>
    </div>
    <div class="col-12 form-group">
        <label for="description">Descripción</label>
        <input type="text" class="form-control" name="description" id="description" value="{{ old('description', $role->description) }}">
    </div>
</div>
<div id="permissions">
    <div class="d-flex justify-content-between align-items-center">
        <h4 class="mb-2">Permisos</h4>
        <div>
            <button type="button" class="btn btn-link btn-sm" data-check="1">Marcar todos</button>
            <button type="button" class="btn btn-link btn-sm" data-check="0">Desmarcar todos</button>
        </div>
    </div>
    <div class="row">
    @foreach ($permissionGroups as $group => $permissions)
        <div class="col-md-6 col-lg-4 mb-3">
            <h5 class="text-primary mb-1">{{ $groupNames[$group] ?? ucfirst($group) }}</h5>
            @foreach ($permissions as $permission)
                <div class="form-check">
                    <label class="form-check-label" title="{{ $permission->description }}">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selected))>
                        <span class="form-check-sign"><span class="check"></span></span>
                        {{ $permission->name }}
                    </label>
                </div>
            @endforeach
        </div>
    @endforeach
    </div>
</div>

@push('js')
<script>
    $(function () {
        $('[data-check]').on('click', function () {
            $('#permissions input[type=checkbox]').prop('checked', $(this).data('check') == 1);
        });
        function toggle() { $('#permissions').toggle(!$('#special').val()); }
        $('#special').on('change', toggle);
        toggle();
    });
</script>
@endpush
