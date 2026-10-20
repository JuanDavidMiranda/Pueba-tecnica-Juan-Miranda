    <div id="login" class="login">
        <section class="login-aside" aria-labelledby="brand-title">
            <div>
                <p class="eyebrow">Sistema de información hospitalaria</p>
                <h1 id="brand-title">SIHOS</h1>
                <p class="lede">Censo de admisión. Consulte, registre y actualice pacientes sin recargar la página.</p>
            </div>
            <ul class="aside-list">
                <li>Listado con búsqueda por nombre o correo</li>
                <li>Alta, edición y retiro del censo</li>
                <li>Departamento y municipio enlazados</li>
            </ul>
        </section>
        <main class="login-main">
            <form id="login-form" class="panel" novalidate>
                <h2>Ingresar</h2>
                <p class="hint">Use la cuenta de administración de la prueba.</p>
                <div id="login-error" class="banner" hidden></div>
                <div class="field">
                    <label for="login-email">Correo</label>
                    <input id="login-email" name="email" type="email" autocomplete="username" inputmode="email" required>
                    <span class="field-error" data-error-for="login-email"></span>
                </div>
                <div class="field">
                    <label for="login-password">Contraseña</label>
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                    <span class="field-error" data-error-for="login-password"></span>
                </div>
                <button id="login-submit" class="btn btn-primary" type="submit">Entrar al censo</button>
                <dl class="credentials">
                    <div>
                        <dt>Correo</dt>
                        <dd>admin@sihos.local</dd>
                    </div>
                    <div>
                        <dt>Contraseña</dt>
                        <dd>1234567890</dd>
                    </div>
                </dl>
            </form>
        </main>
    </div>
