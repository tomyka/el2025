<nav class="navbar navbar-expand bg-primary navbar-dark">
    <div class="container-fluid">
             <a class="navbar-brand" @auth href="{{route('main')}}" @else href="{{route('/')}}" @endauth id="logo">
                <img src="{{URL::to('img/logo.png')}}" height="30" alt="SportBet">
            </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar" aria-controls="collapsibleNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>



            <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('prediction.results')}}">
                            <span class="d-md-none"><i class="bi bi-dribbble h4"></i></span>
                            <span class="d-none d-md-block"><i class="bi bi-dribbble h4"></i> Spėjimai</span></a>
                    </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('prediction.standings')}}">
                                <span class="d-md-none"><i class="bi bi-table h4"></i></span>
                                <span class="d-none d-md-block"><i class="bi bi-table h4"></i> Eiga</span></a>
                        </li>

                    @if (session('eventSurvival')==1 && session('survivalGame')==1)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('prediction.survival')}}">
                                <span class="d-md-none"><i class="bi bi-bullseye h4"></i></span>
                                <span class="d-none d-md-block"><i class="bi bi-bullseye h4"></i> Išlikimas</span></a>
                        </li>
                    @endif






                    @if(session('disabled')!="")
                        <li class="nav-item dropdown">
                            <a class="nav-link " href="#" id="navbardrop" role="button" data-bs-toggle="dropdown">
                                <span class="d-md-none dropdown-toggle"><i class="bi bi-file-earmark-bar-graph-fill h4"></i></span>
                                <span class="d-none d-md-block dropdown-toggle"><i class="bi bi-file-earmark-bar-graph-fill h4"></i> Suvestinė</span>
                            </a>
                            <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('summary.history')}}" >Įvykę varžybos</a>
                                    <a class="dropdown-item" target="_blank" href="{{ route('summary.prediction.results')}}" >Spėjimai</a>
                                    <a class="dropdown-item" target="_blank" href="{{ route('summary.prediction.standings')}} ">Eiga</a>

                                @if(session('survivalGame')!=0)
                                    <a class="dropdown-item" href="{{ route('summary.prediction.survivals')}}" >Išlikimas</a>
                                @endif
                                <a class="dropdown-item" href="{{ route('summary.chart')}}" >Grafikas</a>
                            </div>
                        </li>
                    @endif


                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('rules')}}">Taisyklės</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('charity')}}">Jaunimo linija</a>
                    </li>
                @endauth

            </ul>




            <ul class="navbar-nav ms-auto">
            @guest
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <span class="d-lg-none"><i class="bi bi-person-fill h4"></i></span>
                        <span class="d-none d-lg-block"> Prisijungti</span>
                    </a>
                </li>
            @else
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="bi bi-info-circle h4"></i></a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{route('users')}}">Dalyviai</a>
                        <a class="dropdown-item" href="{{route('rules')}}">Taisyklės</a>
                        <a class="dropdown-item" href="{{route('help')}}">Pagalba </a>
                        <a class="dropdown-item" href="{{route('charity')}}">Jaunimo linija</a>
                        <span class="d-md-none"><i class="bi bi-people-fill h4"></i></span>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="bi bi-person-circle h4"></i></a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{route('userProfile')}}"><i class="bi bi-person-fill"></i> Profilis </a>
                        <a class="dropdown-item" href="{{route('userSettings')}}"><i class="bi bi-gear"></i> Nustatymai</a>
                        <a class="dropdown-item" href="{{route('userGroup')}}"><i class="bi bi-person-lines-fill"></i> Grupės</a>
                        @if(session('admin') > 1)
                            <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{route('admin')}}"><i class="bi bi-database-gear"></i> Admin</a>
                        @endif
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <span class="d-lg-none"><i class="bi bi-box-arrow-right h4"></i></span>
                        <span class="d-none d-lg-block"><i class="bi bi-box-arrow-right h4"></i> Atsijungti</span>
                    </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                </li>
             @endguest
            </ul>
        </div>
    </div>
</nav>
<BR>

<!-- Registration Modals -->
@guest
    @include('modals.main')
@endguest
