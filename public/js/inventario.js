/* Utilidades compartidas: editor de líneas con lector de código de barras (entradas y salidas). */
(function (window, $) {
    'use strict';

    var fmt = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });

    function number(value) {
        return fmt.format(Number(value) || 0);
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function typeBadge(item) {
        return '<span class="badge badge-type-' + item.type + ' type-pill">' + escapeHtml(item.type_label) + '</span>';
    }

    /**
     * Editor de líneas con búsqueda por código de barras, serial o nombre.
     *
     * opts.mode: 'entry' (suma stock) o 'delivery' (valida stock disponible)
     * opts.lookupUrl: URL de items.lookup
     * opts.initial: líneas a mostrar al cargar (después de un error)
     * opts.onChange(lines): se llama cada vez que cambian las líneas
     */
    function LineEditor(opts) {
        this.mode = opts.mode;
        this.lookupUrl = opts.lookupUrl;
        this.$scan = $(opts.scan);
        this.$results = $(opts.results);
        this.$body = $(opts.body);
        this.$submit = $(opts.submit);
        this.$summary = $(opts.summary);
        this.onChange = opts.onChange || function () {};
        this.lines = {};
        this.index = 0;
        this.found = [];
        this.selected = -1;
        this.timer = null;
        this.bind();

        var self = this;
        (opts.initial || []).forEach(function (l) { self.add(l.item, l.quantity, true); });
        this.render();
    }

    LineEditor.prototype.bind = function () {
        var self = this;

        // Los lectores de código de barras "escriben" el código y envían Enter.
        this.$scan.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (self.selected >= 0 && self.found[self.selected] && $.trim(self.$scan.val()) === self.lastTerm) {
                    self.add(self.found[self.selected]);
                } else {
                    self.search(true);
                }
            } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!self.found.length) return;
                self.selected = (self.selected + (e.key === 'ArrowDown' ? 1 : -1) + self.found.length) % self.found.length;
                self.showResults();
            } else if (e.key === 'Escape') {
                self.hideResults();
            }
        });

        this.$scan.on('input', function () {
            clearTimeout(self.timer);
            self.timer = setTimeout(function () { self.search(false); }, 250);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest(self.$results.add(self.$scan)).length) self.hideResults();
        });

        this.$results.on('click', '[data-i]', function () {
            self.add(self.found[$(this).data('i')]);
        });

        this.$body.on('input change', 'input[data-field]', function () {
            var key = $(this).closest('tr').data('key');
            var value = parseInt($(this).val(), 10);
            self.lines[key].quantity = isNaN(value) ? 0 : value;
            self.updateSummary();
        });

        this.$body.on('click', '[data-remove]', function () {
            delete self.lines[$(this).closest('tr').data('key')];
            self.render();
            self.$scan.focus();
        });

        this.$submit.closest('form').on('submit', function (e) {
            var error = self.validate();
            if (error) {
                e.preventDefault();
                Swal.fire({ icon: 'error', text: error });
            } else {
                self.$submit.prop('disabled', true).text('Guardando...');
            }
        });
    };

    LineEditor.prototype.search = function (exact) {
        var self = this;
        var term = $.trim(this.$scan.val());
        if (!term) { this.hideResults(); return; }

        $.getJSON(this.lookupUrl, { q: term }).done(function (items) {
            if ($.trim(self.$scan.val()) !== term) return; // llegó tarde
            self.found = items;
            self.lastTerm = term;
            self.selected = items.length ? 0 : -1;

            // Código de barras exacto: se agrega directo.
            if (exact && items.length === 1) {
                self.add(items[0]);
                return;
            }
            if (exact && !items.length) {
                Swal.fire({ icon: 'warning', text: 'No se encontró ningún elemento con "' + term + '".', timer: 2500 });
            }
            self.showResults();
        });
    };

    LineEditor.prototype.showResults = function () {
        var self = this;
        if (!this.found.length) { this.hideResults(); return; }
        this.$results.html(this.found.map(function (it, i) {
            var extra = escapeHtml(it.code) + ' · ' + escapeHtml(it.type_label) + ' · disponibles ' + number(it.stock) + (it.active ? '' : ' · INACTIVO');
            return '<a class="list-group-item' + (i === self.selected ? ' active' : '') + '" data-i="' + i + '">'
                + '<b>' + escapeHtml(it.name) + '</b>' + (it.serial ? ' <small>(' + escapeHtml(it.serial) + ')</small>' : '')
                + ' <small class="float-right">' + extra + '</small></a>';
        }).join('')).show();
    };

    LineEditor.prototype.hideResults = function () {
        this.$results.hide().empty();
        this.found = [];
        this.selected = -1;
    };

    LineEditor.prototype.add = function (item, quantity, silent) {
        if (!item) return;
        if (this.mode === 'delivery' && !silent) {
            if (!item.active) {
                Swal.fire({ icon: 'error', text: '"' + item.name + '" está inactivo.' });
                return;
            }
            if (item.stock <= 0) {
                Swal.fire({ icon: 'error', text: 'No hay "' + item.name + '" disponibles en el almacén.' });
                return;
            }
        }

        var key = 'i' + item.id;
        if (this.lines[key]) {
            this.lines[key].quantity += quantity || 1;
        } else {
            this.lines[key] = { index: this.index++, item: item, quantity: quantity || 1 };
        }

        this.$scan.val('');
        this.hideResults();
        if (!silent) {
            this.render();
            this.$scan.focus();
        }
    };

    LineEditor.prototype.render = function () {
        var self = this;
        var keys = Object.keys(this.lines);

        if (!keys.length) {
            this.$body.html('<tr class="empty"><td colspan="5" class="text-center text-muted py-4">Escanee un código de barras o escriba el nombre del elemento.</td></tr>');
            this.updateSummary();
            return;
        }

        this.$body.html(keys.map(function (key) {
            var l = self.lines[key];
            var n = 'items[' + l.index + ']';
            return '<tr data-key="' + key + '">'
                + '<td><input type="hidden" name="' + n + '[item_id]" value="' + l.item.id + '">'
                + '<b>' + escapeHtml(l.item.name) + '</b><br><small class="text-muted">' + escapeHtml(l.item.code)
                + (l.item.serial ? ' · ' + escapeHtml(l.item.serial) : '') + '</small></td>'
                + '<td>' + typeBadge(l.item) + '</td>'
                + '<td class="text-right">' + number(l.item.stock) + ' <small class="text-muted">' + escapeHtml(l.item.unit || '') + '</small></td>'
                + '<td><input type="number" class="form-control" min="1" step="1" name="' + n + '[quantity]" data-field="quantity" value="' + l.quantity + '"></td>'
                + '<td class="text-right"><button type="button" class="btn btn-link btn-danger btn-sm" data-remove title="Quitar"><i class="tim-icons icon-simple-remove"></i></button></td></tr>';
        }).join(''));

        this.updateSummary();
    };

    LineEditor.prototype.list = function () {
        var self = this;
        return Object.keys(this.lines).map(function (k) { return self.lines[k]; });
    };

    LineEditor.prototype.updateSummary = function () {
        var lines = this.list();
        var units = lines.reduce(function (s, l) { return s + (l.quantity > 0 ? l.quantity : 0); }, 0);
        this.$summary.text(lines.length + (lines.length === 1 ? ' elemento · ' : ' elementos · ') + number(units) + (units === 1 ? ' unidad' : ' unidades'));
        this.$submit.prop('disabled', !lines.length);
        this.onChange(lines);
    };

    LineEditor.prototype.validate = function () {
        var lines = this.list();
        if (!lines.length) return 'Agregue al menos un elemento.';

        for (var i = 0; i < lines.length; i++) {
            var l = lines[i];
            if (!(l.quantity >= 1) || Math.floor(l.quantity) !== l.quantity) return 'La cantidad de "' + l.item.name + '" debe ser un número entero mayor que cero.';
            if (this.mode === 'delivery' && l.quantity > l.item.stock) return 'Solo hay ' + l.item.stock + ' "' + l.item.name + '" disponibles.';
        }
        return null;
    };

    /** Filtra las opciones de un <select> mientras se escribe (nombre o cédula). */
    function filterSelect(input, select) {
        var $select = $(select);
        $(input).on('input', function () {
            var term = $.trim($(this).val()).toLowerCase();
            var first = null;
            $select.find('option').each(function () {
                if (!this.value) return;
                var match = !term || $(this).text().toLowerCase().indexOf(term) !== -1 || String($(this).data('document')).indexOf(term) !== -1;
                $(this).prop('hidden', !match).prop('disabled', !match);
                if (match && !first) first = this;
            });
            if (term && first) {
                $select.val(first.value).trigger('change');
            }
        });
    }

    window.Inventario = { number: number, escapeHtml: escapeHtml, LineEditor: LineEditor, filterSelect: filterSelect };
})(window, jQuery);
