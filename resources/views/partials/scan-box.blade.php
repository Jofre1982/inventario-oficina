<div class="card">
    <div class="card-body">
        <div class="form-group position-relative mb-0">
            <label for="scan">Elemento <small class="text-muted">— escanee el código de barras o escriba el nombre o la placa y presione Enter</small></label>
            <input type="text" id="scan" class="form-control scan-box" placeholder="Código de barras, nombre o placa..." autocomplete="off">
            <div id="scan-results" class="list-group scan-results" style="display:none"></div>
            @can('items.create')
                <small><a href="{{ route('items.create') }}" target="_blank">+ Crear elemento nuevo</a> (se abre en otra ventana; luego búsquelo aquí)</small>
            @endcan
        </div>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table line-items">
                <thead><tr><th>Elemento</th><th>Tipo</th><th class="text-right">Disponible</th><th style="width:120px">Cantidad</th><th></th></tr></thead>
                <tbody id="lines"></tbody>
            </table>
        </div>
        <p class="text-muted mb-0" id="lines-summary"></p>
    </div>
</div>
