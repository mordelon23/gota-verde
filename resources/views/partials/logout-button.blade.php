<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button class="gv-top-logout" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión">
        @include('partials.icon', ['icon' => 'logout'])
    </button>
</form>
