<x-layouts::auth title="Masuk">
    <form class="card" method="POST" action="{{ route('login.store') }}" novalidate x-data="{ show: false }">
        @csrf
        <x-auth-header title="Masuk" description="Aplikasi servis Toko Anugerah" />
        <div class="field">
            <label for="username">Username</label>
            <input class="input" id="username" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" required maxlength="100" value="{{ old('username') }}" @if($errors->has('username')) aria-invalid="true" aria-describedby="username-error" autofocus @elseif(!$errors->any()) autofocus @endif>
            @error('username')<p class="error-text" id="username-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <div class="pw">
                <input class="input" id="password" name="password" type="password" :type="show ? 'text' : 'password'" autocomplete="current-password" required @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @unless($errors->has('username')) autofocus @endunless @endif>
                <button class="icon-btn" type="button" aria-label="Tampilkan password" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'" @click="show = !show"><x-ui.icon name="eye" /></button>
            </div>
            @error('password')<p class="error-text" id="password-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ingat saya di HP ini</span></label>
        <button class="btn primary block" type="submit" data-test="login-button">Masuk</button>
    </form>
</x-layouts::auth>
