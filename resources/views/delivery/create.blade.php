@extends('layouts.app', ['page' => 'Nueva salida / préstamo', 'pageSlug' => 'deliveries_create'])

@section('content')
@include('partials.page-header', ['title' => 'Nueva salida / préstamo'])
<form method="POST" action="{{ route('deliveries.store') }}" autocomplete="off" id="delivery-form">
@csrf
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">1. ¿Quién recibe?</h4></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 form-group">
                        <label for="person-filter">Buscar por nombre o cédula</label>
                        <input type="text" id="person-filter" class="form-control" placeholder="Escriba para filtrar...">
                    </div>
                    <div class="col-md-7 form-group">
                        <label for="responsible_id">Responsable que recibe *</label>
                        <select class="form-control" name="responsible_id" id="responsible_id" required>
                            <option value="">Seleccione...</option>
                            @foreach ($responsibles as $person)
                                <option value="{{ $person->id }}" data-document="{{ $person->document }}" data-position="{{ $person->position }}" data-dependency="{{ $person->dependency_id }}" @selected($selectedResponsible == $person->id)>
                                    {{ $person->name }} — CC {{ $person->document }}
                                </option>
                            @endforeach
                        </select>
                        @can('responsibles.create')
                            <small><a href="#" data-toggle="modal" data-target="#new-person">+ La persona no está en la lista: registrarla</a></small>
                        @endcan
                    </div>
                    <div class="col-md-7 form-group">
                        <label for="dependency_id">Dependencia u oficina *</label>
                        <select class="form-control" name="dependency_id" id="dependency_id" required>
                            <option value="">Seleccione...</option>
                            @foreach ($dependencies as $dependency)
                                <option value="{{ $dependency->id }}" @selected(old('dependency_id') == $dependency->id)>{{ $dependency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 form-group">
                        <label>&nbsp;</label>
                        <div class="selected-person" id="person-info"><span class="text-muted">Seleccione el responsable.</span></div>
                    </div>
                </div>
            </div>
        </div>
        <h4 class="mb-2">2. ¿Qué se entrega?</h4>
        @include('partials.scan-box')
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">3. Fechas</h4></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="date">Fecha de salida *</label>
                    <input type="date" class="form-control" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div id="due-box">
                    <div class="form-group mb-1">
                        <label for="due_date">Fecha en que debe devolver <span id="due-required" class="text-danger" style="display:none">*</span></label>
                        <input type="date" class="form-control" name="due_date" id="due_date" value="{{ old('due_date') }}">
                    </div>
                    <div class="mb-3" id="due-shortcuts">
                        <small class="text-muted">Rápido:</small>
                        @foreach ([1 => '1 día', 8 => '8 días', 15 => '15 días', 30 => '1 mes', 90 => '3 meses'] as $days => $label)
                            <button type="button" class="btn btn-link btn-sm px-1" data-days="{{ $days }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <p class="text-muted small" id="due-help">Solo se pide si entrega elementos <b>devolutivos</b>.</p>
                </div>
                <div class="form-group">
                    <label for="notes">Observaciones</label>
                    <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Ej.: para el evento del sábado, con cargador y maletín">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" id="save" class="btn btn-primary btn-block btn-lg" disabled>Registrar entrega</button>
                <a href="{{ route('deliveries.index') }}" class="btn btn-default btn-block">Cancelar</a>
                <p class="text-muted small mt-2 mb-0">Después de registrar se abre el <b>acta de entrega</b> en PDF para imprimir y firmar.</p>
            </div>
        </div>
    </div>
</div>
</form>

@can('responsibles.create')
<div class="modal fade" id="new-person" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="new-person-form">
            <div class="modal-header">
                <h5 class="modal-title">Registrar responsable</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><i class="tim-icons icon-simple-remove"></i></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" id="new-person-errors" style="display:none"></div>
                <div class="form-group"><label>Cédula *</label><input type="text" name="document" class="form-control" maxlength="30" required></div>
                <div class="form-group"><label>Nombre completo *</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Cargo</label><input type="text" name="position" class="form-control"></div>
                <div class="form-group"><label>Dependencia *</label>
                    <select name="dependency_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach ($dependencies as $dependency)<option value="{{ $dependency->id }}">{{ $dependency->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('js')
<script>
    $(function () {
        var $person = $('#responsible_id'), $dependency = $('#dependency_id'), $due = $('#due_date');
        var dependencyFromOld = @json(old('dependency_id'));

        function showPerson(keepDependency) {
            var $opt = $person.find('option:selected');
            if (!$person.val()) {
                $('#person-info').html('<span class="text-muted">Seleccione el responsable.</span>');
                return;
            }
            $('#person-info').html('<b>' + Inventario.escapeHtml($opt.text().split(' — ')[0]) + '</b><br><small>CC ' + Inventario.escapeHtml($opt.data('document'))
                + ($opt.data('position') ? ' · ' + Inventario.escapeHtml($opt.data('position')) : '') + '</small>');
            if (!keepDependency) $dependency.val(String($opt.data('dependency')));
        }
        $person.on('change', function () { showPerson(false); });
        showPerson(!!dependencyFromOld);
        if (!dependencyFromOld && $person.val()) showPerson(false);
        Inventario.filterSelect('#person-filter', '#responsible_id');

        // La fecha de devolución solo se exige si hay devolutivos.
        function toggleDue(lines) {
            var needs = lines.some(function (l) { return l.item.type === 'RETURNABLE'; });
            $due.prop('required', needs);
            $('#due-required').toggle(needs);
            $('#due-help').html(needs
                ? '<span class="text-warning">Hay elementos <b>devolutivos</b>: indique cuándo deben regresar.</span>'
                : 'Solo se pide si entrega elementos <b>devolutivos</b>.');
            if (needs && !$due.val()) {
                var d = new Date($('#date').val() + 'T00:00:00'); d.setDate(d.getDate() + 30);
                $due.val(d.toISOString().slice(0, 10));
            }
            if (!needs) $due.val('');
        }
        $('#due-shortcuts [data-days]').on('click', function () {
            var d = new Date(($('#date').val() || new Date().toISOString().slice(0, 10)) + 'T00:00:00');
            d.setDate(d.getDate() + parseInt($(this).data('days'), 10));
            $due.val(d.toISOString().slice(0, 10));
        });

        new Inventario.LineEditor({
            mode: 'delivery',
            lookupUrl: @json(route('items.lookup')),
            scan: '#scan', results: '#scan-results', body: '#lines', submit: '#save', summary: '#lines-summary',
            initial: @json($oldLines),
            onChange: toggleDue
        });
        var oldDue = @json(old('due_date'));
        if (oldDue) $due.val(oldDue);

        $('#delivery-form').on('submit', function (e) {
            if (!$person.val() || !$dependency.val()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                Swal.fire({ icon: 'error', text: 'Seleccione el responsable que recibe y su dependencia.' });
                $('#save').prop('disabled', false).text('Registrar entrega');
            }
        });

        // Registrar una persona nueva sin salir de la pantalla.
        $('#new-person-form').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            $.ajax({
                url: @json(route('responsibles.store')), method: 'POST', dataType: 'json',
                data: $form.serialize() + '&_token=' + encodeURIComponent($('meta[name=csrf-token]').attr('content'))
            }).done(function (p) {
                var $opt = $('<option>').val(p.id).text(p.name + ' — CC ' + p.document)
                    .attr('data-document', p.document).attr('data-position', p.position || '').attr('data-dependency', p.dependency_id);
                $person.append($opt).val(p.id).trigger('change');
                $('#new-person').modal('hide');
                $form[0].reset();
                $('#new-person-errors').hide();
                $('#scan').focus();
            }).fail(function (xhr) {
                var errors = (xhr.responseJSON && xhr.responseJSON.errors) || { x: ['No se pudo guardar.'] };
                $('#new-person-errors').html(Object.values(errors).map(function (m) { return Inventario.escapeHtml(m[0]); }).join('<br>')).show();
            });
        });
    });
</script>
@endpush
