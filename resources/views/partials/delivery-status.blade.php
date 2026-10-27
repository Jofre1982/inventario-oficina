@php([$label, $color] = $delivery->loanStatus())
<span class="badge badge-{{ $color }}">{{ $label }}</span>
