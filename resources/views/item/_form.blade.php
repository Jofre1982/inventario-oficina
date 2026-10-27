<div class="row">
    <div class="col-md-8 form-group">
        <label for="name">Nombre *</label>
        <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $item->name) }}" required autofocus placeholder="Ej.: Resma papel carta, Portátil Lenovo ThinkPad">
    </div>
    <div class="col-md-4 form-group">
        <label for="code">Código de barras / código interno</label>
        <input type="text" class="form-control" name="code" id="code" value="{{ old('code', $item->code) }}" maxlength="60" placeholder="Escanéelo aquí">
        <small class="form-text text-muted">Si lo deja vacío se genera uno (EL-00001...).</small>
    </div>
    <div class="col-md-12 form-group">
        <label class="d-block">Tipo *</label>
        @foreach (\App\Models\Item::TYPES as $value => $label)
            <div class="form-check form-check-radio form-check-inline">
                <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="type" value="{{ $value }}" @checked(old('type', $item->type) === $value)>
                    <span class="form-check-sign"></span>
                    <b>{{ $label }}</b>
                    <small class="text-muted">{{ $value === 'CONSUMABLE' ? '— se entrega y no regresa (papel, tóner, aseo)' : '— se presta y debe regresar (portátiles, herramientas)' }}</small>
                </label>
            </div>
        @endforeach
    </div>
    <div class="col-md-4 form-group">
        <label for="category_id">Categoría *</label>
        <select class="form-control" name="category_id" id="category_id" required>
            <option value="">Seleccione...</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @if ($categories->isEmpty())
            <small class="form-text text-warning">Primero cree una <a href="{{ route('categories.create') }}">categoría</a>.</small>
        @endif
    </div>
    <div class="col-md-4 form-group">
        <label for="unit">Unidad de medida *</label>
        <input type="text" class="form-control" name="unit" id="unit" value="{{ old('unit', $item->unit ?: 'Unidad') }}" list="units" maxlength="30" required>
        <datalist id="units">@foreach (\App\Models\Item::UNITS as $unit)<option value="{{ $unit }}">@endforeach</datalist>
    </div>
    <div class="col-md-4 form-group">
        <label for="min_stock">Stock mínimo</label>
        <input type="number" class="form-control" name="min_stock" id="min_stock" value="{{ old('min_stock', $item->min_stock ?? 0) }}" min="0" step="1">
        <small class="form-text text-muted">Al llegar a esta cantidad aparece como “stock bajo” (0 = sin alerta).</small>
    </div>
    <div class="col-md-6 form-group">
        <label for="serial">Serial / placa de inventario</label>
        <input type="text" class="form-control" name="serial" id="serial" value="{{ old('serial', $item->serial) }}" maxlength="100">
    </div>
    <div class="col-md-6 form-group">
        <label for="location">Ubicación en el almacén</label>
        <input type="text" class="form-control" name="location" id="location" value="{{ old('location', $item->location) }}" maxlength="120" placeholder="Ej.: Estante A, bodega 2">
    </div>
    <div class="col-md-8 form-group">
        <label for="description">Descripción / características</label>
        <textarea class="form-control" name="description" id="description" rows="3" maxlength="2000">{{ old('description', $item->description) }}</textarea>
    </div>
    <div class="col-md-4 form-group">
        <label for="picture" class="d-block">Foto</label>
        @if ($item->image_url)
            <img src="{{ $item->image_url }}" alt="" class="item-thumb mb-2 d-block" style="width:90px;height:90px">
        @endif
        <input type="file" id="picture" name="picture" accept="image/*">
    </div>
    @unless ($item->exists)
        <div class="col-md-4 form-group">
            <label for="initial_quantity">Cantidad inicial en el almacén</label>
            <input type="number" class="form-control" name="initial_quantity" id="initial_quantity" value="{{ old('initial_quantity', 0) }}" min="0" step="1">
            <small class="form-text text-muted">Se registra como una entrada de “Saldo inicial”.</small>
        </div>
    @else
        <div class="col-md-4 form-group">
            <label>Disponible ahora</label>
            <input type="text" class="form-control" value="{{ $item->stock }}" disabled>
            <small class="form-text text-muted">Cambia solo con entradas, salidas y devoluciones.</small>
        </div>
    @endunless
</div>
