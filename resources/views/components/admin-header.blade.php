@php
    $kicker = $kicker ?? '';
    $judul = $judul ?? '';
    $deskripsi = $deskripsi ?? '';
@endphp

<header class="d-flex justify-content-between align-items-center mb-4" style="color:#0d1b2a">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <div style="width: 16px; height: 3px; background-color: #ff7a3d;"></div>
            <span class="text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px; color:cornflowerblue">{{ $kicker }}</span>
        </div>
        <h2 class="fw-bold m-0 text-dark">{{ $judul }}</h2>
        <p class="text-muted m-0 small">{{ $deskripsi }}</p>
    </div>

    <div class="card border-0 shadow-sm px-3 py-2 rounded-pill" style="background-color: #0d1b2a;">
        <div class="d-flex align-items-center gap-2 text-white small fw-medium">
            <div class="rounded-circle bg-danger" style="width: 8px; height: 8px;"></div>
            <span style="color: white">OPERASIONAL ARMADA</span>
        </div>
    </div>
</header>
