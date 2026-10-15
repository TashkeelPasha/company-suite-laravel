{{--
    One ERP form input. JS reads/writes it via resources/js/erp.js (collectFields / fillFields).
      $attr  — "data-pax" (passenger manifest field) or "data-card" (PRC/PIF field)
      $name  — API camelCase key          $label — visible label
      $type  — text|date|email|textarea|yesno|paxType   (default text)
      $req   — required (marked * like the paper form)
      $col   — Bootstrap column class (default col-md-6)
      $index — for array fields (contact numbers 1..3)
      $idp   — element-id prefix, when the same form appears twice on a page
--}}
@php
    $type = $type ?? 'text';
    $fieldId = 'cs-'.($idp ?? '').str_replace('data-', '', $attr).'-'.$name.(isset($index) ? '-'.$index : '');
@endphp
<div class="{{ $col ?? 'col-md-6' }}">
    <label class="form-label small mb-1" for="{{ $fieldId }}">{{ $label }}@if(!empty($req)) <span class="text-danger">*</span>@endif</label>
    @if($type === 'textarea')
        <textarea id="{{ $fieldId }}" class="form-control form-control-sm" rows="{{ $rows ?? 2 }}" {{ $attr }}="{{ $name }}" @isset($index) data-index="{{ $index }}" @endisset @required(!empty($req))></textarea>
    @elseif($type === 'yesno')
        <select id="{{ $fieldId }}" class="form-select form-select-sm" {{ $attr }}="{{ $name }}" data-type="bool" @required(!empty($req))>
            <option value="">&mdash;</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
        </select>
    @elseif($type === 'paxType')
        <select id="{{ $fieldId }}" class="form-select form-select-sm" {{ $attr }}="{{ $name }}" @required(!empty($req))>
            <option value="">&mdash;</option>
            <option value="M">M &mdash; Male</option>
            <option value="F">F &mdash; Female</option>
            <option value="C">C &mdash; Child</option>
            <option value="I">I &mdash; Infant</option>
        </select>
    @else
        <input id="{{ $fieldId }}" type="{{ $type }}" class="form-control form-control-sm" {{ $attr }}="{{ $name }}" @isset($index) data-index="{{ $index }}" @endisset @required(!empty($req))
            @if($type === 'tel') placeholder="+92 300 1234567" @endif>
    @endif
</div>
