<x-layouts::app title="Beranda">
    <div class="title-row"><h1>Beranda</h1></div>
    <p class="muted">Toko Anugerah</p>
    <div class="actions" style="margin-top:24px"><a class="btn" href="{{ route('users.index') }}">Kelola pengguna</a><a class="btn" href="{{ route('profile.edit') }}">Ganti password</a></div>
</x-layouts::app>
