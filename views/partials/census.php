    <div id="app" class="shell" hidden>
        <header class="masthead">
            <div class="masthead-inner">
                <div class="brand-lockup">
                    <img src="/favicon.svg" width="36" height="36" alt="">
                    <div>
                        <p class="eyebrow">SIHOS</p>
                        <h1>Censo de pacientes</h1>
                    </div>
                </div>
                <div class="session">
                    <span id="user-name"></span>
                    <button id="logout" class="btn btn-ghost btn-light" type="button">Cerrar sesión</button>
                </div>
            </div>
        </header>
        <main class="content">
            <div class="toolbar">
                <form id="search-form" class="search" role="search">
                    <label class="sr-only" for="search-input">Buscar por nombre o correo</label>
                    <input id="search-input" type="search" placeholder="Buscar por nombre o correo" maxlength="100" autocomplete="off">
                </form>
                <button id="new-patient" class="btn btn-primary" type="button">Nuevo paciente</button>
            </div>
            <p id="summary" class="summary">Cargando pacientes…</p>
            <div id="list-status" class="status" hidden></div>
            <div id="empty" class="empty" hidden>
                <h2 id="empty-title"></h2>
                <p id="empty-text"></p>
            </div>
            <div id="patient-table-wrap" class="table-wrap" hidden>
                <table class="patients">
                    <thead>
                        <tr>
                            <th scope="col">Paciente</th>
                            <th scope="col">Documento</th>
                            <th scope="col">Ubicación</th>
                            <th scope="col">Correo</th>
                            <th scope="col"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody id="patient-tbody"></tbody>
                </table>
            </div>
            <nav id="pager" class="pager" hidden aria-label="Paginación"></nav>
        </main>
    </div>
