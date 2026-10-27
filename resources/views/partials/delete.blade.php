<form action="{{ $action }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $confirm ?? '¿Seguro que desea eliminar este registro?' }}')">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-link btn-danger btn-sm btn-icon" title="Eliminar"><i class="tim-icons icon-trash-simple"></i></button>
</form>
