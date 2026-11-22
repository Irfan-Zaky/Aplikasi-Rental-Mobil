<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Katalog Mobil</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f5f7; color: #222; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .filter { background: #fff; padding: 16px; border-radius: 10px; display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px; }
        .filter input, .filter select { padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        .filter button, .filter a.reset { padding: 8px 16px; border: 0; border-radius: 6px; background: #1d4ed8; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
        .filter a.reset { background: #6b7280; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
        .card { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .card img { width: 100%; height: 170px; object-fit: cover; background: #ddd; }
        .card .body { padding: 14px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; color: #fff; }
        .Tersedia { background: #16a34a; } .Disewa { background: #dc2626; } .Perawatan { background: #d97706; }
        .harga { font-weight: bold; color: #1d4ed8; margin-top: 8px; }
        .kosong { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Katalog Mobil</h1>

    <form method="GET" action="{{ route('katalog') }}" class="filter">
        <input type="text" name="keyword" placeholder="Cari nama atau merk..." value="{{ request('keyword') }}">

        <select name="merk">
            <option value="">Semua merk</option>
            @foreach ($daftarMerk as $merk)
                <option value="{{ $merk }}" @selected(request('merk') === $merk)>{{ $merk }}</option>
            @endforeach
        </select>

        <select name="jenis">
            <option value="">Semua jenis</option>
            @foreach ($daftarJenis as $jenis)
                <option value="{{ $jenis }}" @selected(request('jenis') === $jenis)>{{ $jenis }}</option>
            @endforeach
        </select>

        <select name="status">
            <option value="">Semua status</option>
            @foreach (['Tersedia', 'Disewa', 'Perawatan'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
            @endforeach
        </select>

        <input type="number" name="harga_min" placeholder="Harga min" value="{{ request('harga_min') }}">
        <input type="number" name="harga_max" placeholder="Harga maks" value="{{ request('harga_max') }}">

        <button type="submit">Cari</button>
        <a href="{{ route('katalog') }}" class="reset">Reset</a>
    </form>

    @if ($mobils->count())
        <div class="grid">
            @foreach ($mobils as $mobil)
                <div class="card">
                    <img src="{{ $mobil->fotoUtama ? asset('storage/'.$mobil->fotoUtama->path_foto) : '' }}" alt="{{ $mobil->nama_mobil }}">
                    <div class="body">
                        <strong>{{ $mobil->merk }} {{ $mobil->nama_mobil }}</strong><br>
                        <small>{{ $mobil->jenis }}</small><br>
                        <span class="badge {{ $mobil->status }}">{{ $mobil->status }}</span>
                        <div class="harga">Rp {{ number_format($mobil->harga_sewa, 0, ',', '.') }} / hari</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:24px">{{ $mobils->links() }}</div>
    @else
        <div class="kosong">Tidak ada mobil yang cocok dengan pencarianmu.</div>
    @endif
</div>
</body>
</html>