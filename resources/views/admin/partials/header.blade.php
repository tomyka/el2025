<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="{{route('admin.index')}}" id="logo">
            <img src="{{URL::to('img/logo.png')}}" height="40">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar"  aria-controls="collapsibleNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Links -->
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav">
                @if(session('admin') > 5)
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('admin.teams')}}">Komandos</a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" href="{{route('admin.games')}}">Rungtynės</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{route('admin.resultsAll')}}">Rezultatai</a>
                </li>
                @if(session('admin') > 5)
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('admin.users')}}">Dalyviai</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('admin.groups')}}">Grupės</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('admin.userGroups')}}">Dalyviu Grupės</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('admin.messages')}}">Pranešimai</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('admin.events')}}">Įvykiai</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('admin.settings')}}">Nustatymai</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="bi bi-calculator h4"></i></a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{route('admin.updateStandingPoints')}}">Taškai už eigą</a>
                        </div>
                    </li>
                @endif
            </ul>



            <ul class="nav navbar-nav flex-row justify-content-between ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{route('main')}}"> <i class="bi bi-person-circle h4"></i></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('logout') }}"
                       onclick="event.preventDefault();
                                                                 document.getElementById('logout-form').submit();">
                        {{ __('Atsijungti') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>



